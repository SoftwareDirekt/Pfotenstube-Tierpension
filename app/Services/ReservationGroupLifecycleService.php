<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\ReservationGroup;
use App\Models\ReservationPayment;
use App\Models\ReservationPaymentEntry;
use Illuminate\Support\Facades\DB;

/**
 * Handles reservation_group changes when a member is cancelled/deleted:
 * - 0 members left: remove group, entries, cancel group invoices
 * - 1 member left: dissolve group, migrate group payments to that reservation, single-checkout flow
 * - 2+ members: recalculate group total_due and sync child payment headers
 */
class ReservationGroupLifecycleService
{
    public function afterMemberRemoved(int $groupId): void
    {
        DB::transaction(function () use ($groupId) {
            $group = ReservationGroup::lockForUpdate()->find($groupId);
            if (! $group) {
                return;
            }

            $customerId = $group->customer_id;

            $remainingCount = $group->reservations()->count();

            if ($remainingCount === 0) {
                $this->removeEmptyGroup($group);
            } elseif ($remainingCount === 1) {
                $remaining = $group->reservations()->first();
                $this->dissolveGroupToSingleReservation($group, $remaining);
            } else {
                $group->recalculateTotalDue();
                $group->refreshStatus();
                $this->syncChildReservationPaymentHeaders($group);
            }

            if ($customerId) {
                (new CustomerBalanceService())->getBalance($customerId);
            }
        });
    }

    private function removeEmptyGroup(ReservationGroup $group): void
    {
        $entryIds = $group->entries()->pluck('id');

        Invoice::query()
            ->where(function ($q) use ($group, $entryIds) {
                $q->where('reservation_group_id', $group->id);
                if ($entryIds->isNotEmpty()) {
                    $q->orWhereIn('reservation_group_entry_id', $entryIds);
                }
            })
            ->update(['status' => 'cancelled']);

        foreach ($group->entries()->get() as $entry) {
            $entry->delete();
        }

        $group->delete();
    }

    private function dissolveGroupToSingleReservation(ReservationGroup $group, Reservation $remaining): void
    {
        $remaining->loadMissing(['plan', 'additionalCosts', 'dog', 'reservationPayment']);

        $header = $remaining->reservationPayment;
        if (! $header) {
            $header = ReservationPayment::create([
                'res_id'    => $remaining->id,
                'total_due' => round((float) $remaining->gross_total, 2),
                'status'    => 'unpaid',
            ]);
            $remaining->setRelation('reservationPayment', $header);
        }

        foreach ($group->entries()->orderBy('id')->get() as $gEntry) {
            if (($gEntry->status ?? 'active') !== 'active') {
                continue;
            }

            $newEntry = ReservationPaymentEntry::create([
                'res_payment_id'   => $header->id,
                'amount'           => $gEntry->amount,
                'method'           => $gEntry->method,
                'type'             => $gEntry->type,
                'transaction_date' => $gEntry->transaction_date ?? now(),
                'note'             => trim(($gEntry->note ?? '') . ' [Gruppe G-' . $group->id . ']'),
                'status'           => 'active',
            ]);

            Invoice::where('reservation_group_entry_id', $gEntry->id)->update([
                'reservation_id'             => $remaining->id,
                'res_payment_entry_id'       => $newEntry->id,
                'reservation_group_id'       => null,
                'reservation_group_entry_id' => null,
                'customer_id'                => $remaining->dog?->customer_id ?? $group->customer_id,
            ]);

            $gEntry->delete();
        }

        $remaining->reservation_group_id = null;
        $remaining->save();

        $group->delete();

        $remaining->refresh();
        $remaining->loadMissing(['plan', 'additionalCosts']);
        $header->refresh();

        $newDue    = round((float) $remaining->gross_total, 2);
        $totalPaid = (float) $header->entries()->where('status', 'active')->sum('amount');
        $tol       = 0.01;
        $status    = 'unpaid';
        if ($totalPaid >= $newDue - $tol) {
            $status = 'paid';
        } elseif ($totalPaid > $tol) {
            $status = 'partial';
        }

        $header->update([
            'total_due' => $newDue,
            'status'    => $status,
        ]);
    }

    private function syncChildReservationPaymentHeaders(ReservationGroup $group): void
    {
        $group->loadMissing(['reservations.plan', 'reservations.additionalCosts', 'reservations.reservationPayment']);

        $groupPaid  = (float) $group->activeEntries()->sum('amount');
        $groupTotal = (float) $group->total_due;
        $tol        = 0.01;

        $payStatus = 'unpaid';
        if ($groupPaid >= $groupTotal - $tol) {
            $payStatus = 'paid';
        } elseif ($groupPaid > $tol) {
            $payStatus = 'partial';
        }

        foreach ($group->reservations as $res) {
            $header = $res->reservationPayment;
            if (! $header) {
                continue;
            }

            $perDue = round((float) $res->gross_total, 2);
            $header->update([
                'total_due' => $perDue,
                'status'    => $payStatus,
            ]);
        }
    }
}

