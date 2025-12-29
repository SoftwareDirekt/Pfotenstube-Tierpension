<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Session;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Services\CustomerBalanceService;
use App\Helpers\General;

class PaymentsController extends Controller
{
    public function payment(Request $request)
    {
        if(!General::permissions('Zahlung'))
        {
            return to_route('admin.settings');
        }

        // Only show payments that have valid, non-deleted reservations (filter out legacy orphaned payments)
        // This prevents showing payments from deleted reservations
        $paymentsQuery = Payment::with(['reservation.dog.customer'])
            ->whereHas('reservation', function($query) {
                $query->whereNull('deleted_at'); 
            });

        if ($request->filled('year') && $request->input('year') !== 'all') {
            $paymentsQuery->whereYear('created_at', $request->input('year'));
        }

        if ($request->filled('month') && $request->input('month') !== 'all') {
            $paymentsQuery->whereMonth('created_at', $request->input('month'));
        }

        if ($request->filled('id') || $request->filled('payment_id')) {
            $paymentId = $request->input('id') ?? $request->input('payment_id');
            $paymentsQuery->where('id', $paymentId);
        }

        $status = $request->input('st', $request->input('status'));
        if (!is_null($status) && $status !== 'alle') {
            $paymentsQuery->where('status', $status);
        }

        $payments = $paymentsQuery->orderByDesc('id')->paginate(30);
        $payments->appends($request->query());

        $payments->getCollection()->transform(function (Payment $payment) {
            // Calculate remaining_amount if null (legacy records)
            if (is_null($payment->remaining_amount)) {
                $cost = (float) ($payment->cost ?? 0);
                $receivedAmount = (float) ($payment->received_amount ?? 0);
                $payment->remaining_amount = max($cost - $receivedAmount, 0);
            }
            
            // Calculate advance_payment if null (legacy records)
            if (is_null($payment->advance_payment)) {
                $cost = (float) ($payment->cost ?? 0);
                $receivedAmount = (float) ($payment->received_amount ?? 0);
                $payment->advance_payment = max($receivedAmount - $cost, 0);
            }

            // Load settlements to calculate effective remaining amount
            $payment->load('settlementsReceived');
            
            // Update stored status if effective remaining is 0 but status is not "Bezahlt"
            // This ensures database status matches display logic
            $effectiveRemaining = $payment->effective_remaining_amount;
            $invoiceTotal = (float) ($payment->cost ?? 0);
            
            if (($invoiceTotal < 0.01 || $effectiveRemaining < 0.01) && in_array($payment->status, [0, 2])) {
                // Status should be "Bezahlt" (1) if invoice is 0 or fully settled
                // Use lockForUpdate to prevent race conditions
                $payment->lockForUpdate()->update(['status' => 1]);
                $payment->refresh(); // Refresh to get updated status
            }
            
            return $payment;
        });

        return view ("admin.payment.index", compact('payments'));
    }

    /**
     * Get settlement details for a payment
     * Shows ALL payments that settled this payment's debt (grouped together)
     */
    public function settlementDetails($id)
    {
        if(!General::permissions('Zahlung'))
        {
            return response()->json(['error' => 'Keine Berechtigung'], 403);
        }

        $payment = Payment::with([
            'settlementsReceived.settlingPayment.reservation.dog.customer',
            'reservation.dog.customer'
        ])->findOrFail($id);

        // Get ALL settlements received (all payments that settled this payment's debt)
        $settlements = $payment->settlementsReceived()->with([
            'settlingPayment.reservation.dog.customer'
        ])->orderBy('created_at', 'asc')->get();

        // Build settlement trail showing all payments that settled this debt
        $settlementTrail = [];
        $runningBalance = (float) $payment->remaining_amount;
        $totalSettled = 0;

        foreach ($settlements as $settlement) {
            $settlingPayment = $settlement->settlingPayment;
            if (!$settlingPayment) {
                continue; // Skip if settling payment was deleted
            }
            
            $amountSettled = (float) $settlement->amount_settled;
            $totalSettled += $amountSettled;
            
            $settlementTrail[] = [
                'settling_payment_id' => $settlingPayment->id,
                'settling_payment_date' => $settlingPayment->created_at->format('d.m.Y H:i'),
                'settling_payment_received' => number_format($settlingPayment->received_amount, 2),
                'settling_payment_invoice' => number_format($settlingPayment->cost, 2),
                'amount_settled' => number_format($amountSettled, 2),
                'dog_name' => ($settlingPayment->reservation && $settlingPayment->reservation->dog) ? $settlingPayment->reservation->dog->name : 'N/A',
                'customer_name' => ($settlingPayment->reservation && $settlingPayment->reservation->dog && $settlingPayment->reservation->dog->customer) ? $settlingPayment->reservation->dog->customer->name : 'N/A',
                'balance_before' => number_format($runningBalance, 2),
                'balance_after' => number_format(max(0, $runningBalance - $amountSettled), 2),
            ];
            
            $runningBalance = max(0, $runningBalance - $amountSettled);
        }

        return response()->json([
            'payment' => [
                'id' => $payment->id,
                'invoice_total' => number_format($payment->cost, 2),
                'original_remaining' => number_format($payment->remaining_amount, 2),
                'effective_remaining' => number_format($payment->effective_remaining_amount, 2),
                'total_settled' => number_format($totalSettled, 2),
                'created_at' => $payment->created_at->format('d.m.Y H:i'),
                'dog_name' => ($payment->reservation && $payment->reservation->dog) ? $payment->reservation->dog->name : 'N/A',
                'customer_name' => ($payment->reservation && $payment->reservation->dog && $payment->reservation->dog->customer) ? $payment->reservation->dog->customer->name : 'N/A',
            ],
            'settlement_trail' => $settlementTrail,
        ]);
    }
}
