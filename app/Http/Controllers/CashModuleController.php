<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Models\ReservationPaymentEntry;
use App\Models\ReservationGroup;
use App\Models\ReservationGroupEntry;
use App\Helpers\General;
use App\Services\InvoiceService;
use App\Services\CustomerBalanceService;
use Carbon\Carbon;
use Session;
use Exception;

class CashModuleController extends Controller
{
    public function index(Request $request)
    {
        if (!General::permissions('Reservierung')) {
            return redirect()->route('admin.settings');
        }

        // Ungrouped reservations (shown per-reservation as before)
        $ungroupedQuery = Reservation::with(['dog.customer', 'reservationPayment.entries', 'plan'])
            ->whereHas('dog')
            ->whereNull('reservation_group_id')
            ->whereNotIn('status', [
                Reservation::STATUS_PENDING_CONFIRMATION,
                Reservation::STATUS_CANCELLED,
            ])
            ->orderBy('checkin_date', 'desc');

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $ungroupedQuery->whereHas('dog', function ($q) use ($keyword) {
                $q->where('name', 'like', "%$keyword%")
                    ->orWhereHas('customer', function ($q2) use ($keyword) {
                        $q2->where('name', 'like', "%$keyword%");
                    });
            });
        }

        $reservations = $ungroupedQuery->paginate(20);

        // Grouped reservations (one row per group)
        $groupQuery = ReservationGroup::with(['customer', 'reservations.dog', 'reservations.plan', 'entries'])
            ->whereHas('reservations', function ($q) {
                $q->whereNotIn('status', [
                    Reservation::STATUS_PENDING_CONFIRMATION,
                    Reservation::STATUS_CANCELLED,
                ]);
            });

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $groupQuery->where(function ($q) use ($keyword) {
                $q->whereHas('customer', function ($q2) use ($keyword) {
                    $q2->where('name', 'like', "%$keyword%");
                })->orWhereHas('reservations.dog', function ($q2) use ($keyword) {
                    $q2->where('name', 'like', "%$keyword%");
                });
            });
        }

        $groups = $groupQuery->orderByDesc('created_at')->get();

        return view('admin.payment.index', compact('reservations', 'groups'));
    }

    public function addPayment(Request $request)
    {
        $request->validate([
            'res_id' => 'required|exists:reservations,id',
            'type'   => 'required|in:advance',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string',
            'note'   => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $reservation = Reservation::with('reservationPayment')->lockForUpdate()->findOrFail($request->res_id);
            $paymentHeader = $reservation->reservationPayment;

            if (!$paymentHeader) {
                $paymentHeader = ReservationPayment::create([
                    'res_id' => $reservation->id,
                    'total_due' => $reservation->gross_total,
                    'status' => 'unpaid',
                ]);
            }

            $alreadyPaid = $paymentHeader->entries()->where('status', 'active')->sum('amount');
            $newAmount = (float)$request->amount;

            if (($alreadyPaid + $newAmount) > ($paymentHeader->total_due + 0.01)) {
                $remaining = $paymentHeader->total_due - $alreadyPaid;
                throw new Exception('Der Zahlungsbetrag (' . number_format($newAmount, 2) . ' EUR) uebersteigt den Restbetrag (' . number_format($remaining, 2) . ' EUR).');
            }

            // Cash module page is strictly for advance payments (partial/full advance).
            $resolvedType = 'advance';

            $invoiceService = new InvoiceService();
            $allSuccess = true;

            $entry = ReservationPaymentEntry::create([
                'res_payment_id'   => $paymentHeader->id,
                'amount'           => $request->amount,
                'method'           => $request->method,
                'type'             => $resolvedType,
                'transaction_date' => Carbon::now(),
                'note'             => $request->note,
            ]);

            $invoiceResult = $invoiceService->generateInvoice($reservation, $entry);
            if (!($invoiceResult['success'] ?? false)) {
                $allSuccess = false;
            }

            $totalPaid = $paymentHeader->entries()->where('status', 'active')->sum('amount');
            if ($totalPaid >= $paymentHeader->total_due - 0.01) {
                $paymentHeader->update(['status' => 'paid']);
            } elseif ($totalPaid > 0) {
                $paymentHeader->update(['status' => 'partial']);
            }

            DB::commit();

            $msg = 'Zahlungen (' . $entry->method . ') erfolgreich erfasst';
            if ($allSuccess) {
                $msg .= ' und Rechnung erstellt.';
            } else {
                $msg .= '. Achtung: Rechnung konnte nicht erstellt werden.';
            }

            Session::flash('success', $msg);
            return back();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Payment processing error: ' . $e->getMessage());
            Session::flash('error', 'Fehler bei der Zahlung: ' . $e->getMessage());
            return back();
        }
    }

    /**
     * Record an advance payment against a reservation group (all dogs in the group).
     */
    public function addGroupPayment(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:reservation_groups,id',
            'amount'   => 'required|numeric|min:0.01',
            'method'   => 'required|string',
            'note'     => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $group = ReservationGroup::lockForUpdate()->findOrFail($request->group_id);

            $alreadyPaid = (float) $group->activeEntries()->sum('amount');
            $newAmount   = (float) $request->amount;

            if (($alreadyPaid + $newAmount) > ((float) $group->total_due + 0.01)) {
                $remaining = (float) $group->total_due - $alreadyPaid;
                throw new Exception('Der Zahlungsbetrag (' . number_format($newAmount, 2) . ' EUR) uebersteigt den Restbetrag (' . number_format(max(0, $remaining), 2) . ' EUR).');
            }

            $entry = ReservationGroupEntry::create([
                'reservation_group_id' => $group->id,
                'amount'               => $newAmount,
                'method'               => $request->method,
                'type'                 => 'advance',
                'transaction_date'     => Carbon::now(),
                'note'                 => $request->note,
                'status'               => 'active',
            ]);

            // Generate one group invoice for this advance
            $invoiceService = new InvoiceService();
            $invoiceResult  = $invoiceService->generateGroupInvoice($group, $entry);

            $group->refreshStatus();

            // Propagate status to per-reservation payment headers
            $this->syncPerReservationPaymentStatus($group);

            DB::commit();

            // Refresh customer balance (includes group payments now)
            if ($group->customer_id) {
                (new CustomerBalanceService())->getBalance($group->customer_id);
            }

            $msg = 'Gruppenanzahlung (' . $entry->method . ') erfolgreich erfasst';
            if ($invoiceResult['success'] ?? false) {
                $msg .= ' und Rechnung erstellt.';
            } else {
                $msg .= '. Achtung: Rechnung konnte nicht erstellt werden.';
            }

            Session::flash('success', $msg);
            return back();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Group payment error: ' . $e->getMessage());
            Session::flash('error', 'Fehler bei der Gruppenzahlung: ' . $e->getMessage());
            return back();
        }
    }

    /**
     * After a group-level payment, update each child reservation's payment header status
     * so the per-reservation view stays consistent.
     */
    private function syncPerReservationPaymentStatus(ReservationGroup $group): void
    {
        $groupPaid  = (float) $group->activeEntries()->sum('amount');
        $groupTotal = (float) $group->total_due;
        $tol        = 0.01;

        foreach ($group->reservations as $res) {
            $header = $res->reservationPayment;
            if (! $header) {
                continue;
            }
            if ($groupPaid >= $groupTotal - $tol) {
                $header->update(['status' => 'paid']);
            } elseif ($groupPaid > $tol) {
                $header->update(['status' => 'partial']);
            } else {
                $header->update(['status' => 'unpaid']);
            }
        }
    }

    /**
     * Return payment details + entry history for a reservation (JSON).
     * Used by the "View details" eye-button on the payment index page.
     */
    public function paymentDetails(int $id)
    {
        if (!General::permissions('Reservierung')) {
            return response()->json(['error' => 'Keine Berechtigung'], 403);
        }

        $reservation = Reservation::with([
            'dog.customer',
            'plan',
            'reservationPayment.entries.invoice',
        ])->findOrFail($id);

        $paymentHeader = $reservation->reservationPayment;

        if (!$paymentHeader) {
            return response()->json([
                'payment' => [
                    'dog_name'               => $reservation->dog?->name ?? 'N/A',
                    'customer_name'          => $reservation->dog?->customer?->name ?? 'N/A',
                    'invoice_total'          => '0.00',
                    'total_paid'             => '0.00',
                    'balance'                => '0.00',
                    'credit_overpayment'     => '0.00',
                    'credit_overpayment_raw' => 0.0,
                    'status'                 => 'unpaid',
                    'has_cancelled_entries'  => false,
                    'cancelled_entries_count' => 0,
                ],
                'entries' => [],
            ]);
        }

        $allEntries = $paymentHeader->entries()
            ->withTrashed()
            ->orderBy('created_at', 'asc')
            ->get();

        $cancelledCount = $allEntries->where('status', 'cancelled')->count();

        $entries = $allEntries->map(function ($entry) {
            return [
                'date'           => $entry->transaction_date?->format('d.m.Y H:i') ?? $entry->created_at?->format('d.m.Y H:i') ?? '-',
                'type'           => $entry->type,
                'method'         => $entry->method,
                'amount'         => number_format((float) $entry->amount, 2),
                'overpaid_amount' => $entry->overpaid_amount ? number_format((float) $entry->overpaid_amount, 2) : null,
                'note'           => $entry->note,
                'status'         => $entry->status ?? 'active',
                'invoice_number' => $entry->invoice?->invoice_number,
                'invoice_status' => $entry->invoice?->status,
            ];
        })->values()->all();

        $totalPaid = $paymentHeader->entries()->where('status', 'active')->sum('amount');
        $totalDueF = (float) $paymentHeader->total_due;
        $creditOver = round(max(0.0, (float) $totalPaid - $totalDueF), 2);

        return response()->json([
            'payment' => [
                'dog_name'               => $reservation->dog?->name ?? 'N/A',
                'customer_name'          => $reservation->dog?->customer?->name ?? 'N/A',
                'checkin'                => $reservation->checkin_date?->format('d.m.Y') ?? '-',
                'checkout'               => $reservation->checkout_date?->format('d.m.Y') ?? '-',
                'plan'                   => $reservation->plan?->title ?? '-',
                'invoice_total'          => number_format($totalDueF, 2),
                'total_paid'             => number_format((float) $totalPaid, 2),
                'balance'                => number_format(max(0, $totalDueF - (float) $totalPaid), 2),
                'credit_overpayment'     => number_format($creditOver, 2),
                'credit_overpayment_raw' => $creditOver,
                'status'                 => $paymentHeader->status,
                'has_cancelled_entries'  => $cancelledCount > 0,
                'cancelled_entries_count' => $cancelledCount,
            ],
            'entries' => $entries,
        ]);
    }

    /**
     * Return payment details for a reservation group (JSON).
     */
    public function groupPaymentDetails(int $id)
    {
        if (!General::permissions('Reservierung')) {
            return response()->json(['error' => 'Keine Berechtigung'], 403);
        }

        $group = ReservationGroup::with(['customer', 'reservations.dog', 'reservations.plan', 'entries.invoice'])
            ->findOrFail($id);

        $dogNames = $group->reservations->map(fn ($r) => $r->dog?->name ?? 'N/A')->implode(', ');
        $customerName = $group->customer?->name ?? 'N/A';

        $firstRes = $group->reservations->first();
        $lastRes  = $group->reservations->last();
        $checkin  = $firstRes?->checkin_date?->format('d.m.Y') ?? '-';
        $checkout = $lastRes?->checkout_date?->format('d.m.Y') ?? '-';
        $planNames = $group->reservations->map(fn ($r) => $r->plan?->title ?? '-')->unique()->implode(', ');

        $reservationRows = $group->reservations->map(function ($r) {
            return [
                'reservation_id' => $r->id,
                'dog_id'         => $r->dog?->id ?? '-',
                'dog_name'       => $r->dog?->name ?? 'N/A',
                'plan'           => $r->plan?->title ?? '-',
                'checkin'        => $r->checkin_date?->format('d.m.Y') ?? '-',
                'checkout'       => $r->checkout_date?->format('d.m.Y') ?? '-',
                'gross_total'    => number_format((float) $r->gross_total, 2),
            ];
        })->values()->all();

        $totalPaid  = (float) $group->activeEntries()->sum('amount');
        $totalDueF  = (float) $group->total_due;
        $creditOver = round(max(0.0, $totalPaid - $totalDueF), 2);

        $allEntries     = $group->entries()->withTrashed()->orderBy('created_at', 'asc')->get();
        $cancelledCount = $allEntries->where('status', 'cancelled')->count();

        $entries = $allEntries->map(function ($entry) {
            return [
                'id'              => $entry->id,
                'date'            => $entry->transaction_date?->format('d.m.Y H:i') ?? $entry->created_at?->format('d.m.Y H:i') ?? '-',
                'type'            => $entry->type,
                'method'          => $entry->method,
                'amount'          => number_format((float) $entry->amount, 2),
                'overpaid_amount' => $entry->overpaid_amount ? number_format((float) $entry->overpaid_amount, 2) : null,
                'note'            => $entry->note,
                'status'          => $entry->status ?? 'active',
                'invoice_number'  => $entry->invoice?->invoice_number,
                'invoice_status'  => $entry->invoice?->status,
            ];
        })->values()->all();

        return response()->json([
            'payment' => [
                'group_id'               => $group->id,
                'dog_name'               => $dogNames,
                'customer_name'          => $customerName,
                'checkin'                => $checkin,
                'checkout'               => $checkout,
                'plan'                   => $planNames,
                'invoice_total'          => number_format($totalDueF, 2),
                'total_paid'             => number_format($totalPaid, 2),
                'balance'                => number_format(max(0, $totalDueF - $totalPaid), 2),
                'credit_overpayment'     => number_format($creditOver, 2),
                'credit_overpayment_raw' => $creditOver,
                'status'                 => $group->status,
                'has_cancelled_entries'  => $cancelledCount > 0,
                'cancelled_entries_count' => $cancelledCount,
                'is_group'               => true,
                'dog_count'              => $group->reservations->count(),
            ],
            'reservations' => $reservationRows,
            'entries' => $entries,
        ]);
    }
}
