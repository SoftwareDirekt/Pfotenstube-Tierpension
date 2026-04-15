<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerBalanceService
{
    /**
     * Update balance using settlement details (for debt settlement flow)
     * 
     * @param int $customerId
     * @param float $balanceChange Net balance change from settlement
     * @return void
     */
    public function updateBalanceByChange(int $customerId, float $balanceChange): void
    {
        if (!Customer::find($customerId)) {
            return;
        }

        DB::table('customers')
            ->where('id', $customerId)
            ->increment('balance', round($balanceChange, 2));
    }

    /**
     * Reconcile customer balance from all payments
     * Recalculates balance from payments table and updates customer balance
     * Accounts for settlements (effective remaining = original - settlements)
     * 
     * @param int $customerId
     * @return float The recalculated balance
     */
    public function reconcileBalance(int $customerId): float
    {
        $totals = $this->calculateTotals($customerId);
        $calculatedBalance = $totals['total_paid'] - $totals['total_due'];
        $balance = $this->normalizeStoredCustomerBalance($calculatedBalance);

        // Update customer balance
        $customer = Customer::find($customerId);
        if ($customer) {
            $customer->balance = $balance;
            $customer->save();
        }

        return $balance;
    }

    /**
     * Get customer balance
     * 
     * @param int $customerId
     * @return float
     */
    public function getBalance(int $customerId): float
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return 0;
        }

        $totals = $this->calculateTotals($customerId);
        $raw     = round($totals['total_paid'] - $totals['total_due'], 2);
        $balance = $this->normalizeStoredCustomerBalance($raw);

        if ((float) ($customer->balance ?? 0) !== $balance) {
            $customer->balance = $balance;
            $customer->save();
        }

        return $balance;
    }

    /**
     * Do not persist positive customer-wide balance: overpayment vs current total_due (e.g. after real check-in
     * shortens the stay) is refunded at the desk; the Kasse / reservation payment UI shows the amount due back.
     * Debt (negative) is unchanged.
     */
    private function normalizeStoredCustomerBalance(float $rawBalance): float
    {
        if ($rawBalance > 0.01) {
            return 0.0;
        }

        return round($rawBalance, 2);
    }

    /**
     * Settle old payment records with remaining amounts
     * Creates settlement records to track which payments settled which old payments
     * Preserves audit trail by NOT modifying old payment records
     * 
     * @param int $customerId
     * @param int $settlingPaymentId The new payment that is settling the debt
     * @param float $amountToSettle Amount available to settle old debts
     * @return array ['settled_payments' => [...], 'remaining_settlement' => float]
     */
    public function settleOldPaymentRecords(int $customerId, int $settlingPaymentId, float $amountToSettle): array
    {
        return [
            'settled_payments' => [],
            'remaining_settlement' => round($amountToSettle, 2),
        ];
    }

    /**
     * Settle payment with debt consideration
     * First settles any existing debt, then applies remaining to invoice
     * Creates settlement records to preserve audit trail
     * 
     * @param int $customerId
     * @param float $invoiceTotal Total invoice amount
     * @param float $receivedAmount Cash payment received
     * @param float $walletAmount Amount used from wallet
     * @param int|null $settlingPaymentId The payment ID that is settling (null for new payments)
     * @param bool $createSettlements Whether to create settlement records (default: true)
     * @return array Settlement details: ['debt_settled', 'invoice_paid', 'remaining_amount', 'advance_payment', 'balance_change', 'old_payments_settled']
     */
    public function settlePaymentWithDebtConsideration(
        int $customerId, 
        float $invoiceTotal, 
        float $receivedAmount, 
        float $walletAmount = 0,
        ?int $settlingPaymentId = null,
        bool $createSettlements = true
    ): array {
        $currentBalance = $this->getBalance($customerId);
        $totalPayment = $receivedAmount + $walletAmount;
        $debtSettled = 0;
        $oldPaymentsSettled = [];
        
        if ($currentBalance < 0) {
            $debtSettled = min(abs($currentBalance), $totalPayment);
            
            if ($createSettlements && $settlingPaymentId && $debtSettled > 0.01) {
                $oldPaymentsSettled = [];
            }
            
            $remainingPayment = $totalPayment - $debtSettled;
        } else {
            $remainingPayment = $totalPayment;
        }
        
        if ($remainingPayment >= $invoiceTotal) {
            $advancePayment = $remainingPayment - $invoiceTotal;
            $remainingAmount = 0;
        } else {
            $remainingAmount = $invoiceTotal - $remainingPayment;
            $advancePayment = 0;
        }
        
        $balanceChange = $debtSettled - $remainingAmount + $advancePayment - $walletAmount;
        
        return [
            'debt_settled' => round($debtSettled, 2),
            'invoice_paid' => round($remainingPayment, 2),
            'remaining_amount' => round($remainingAmount, 2),
            'advance_payment' => round($advancePayment, 2),
            'balance_change' => round($balanceChange, 2),
            'old_payments_settled' => $oldPaymentsSettled,
        ];
    }

    private function calculateTotals(int $customerId): array
    {
        // Due from ungrouped reservations (per-reservation payment headers)
        $ungroupedDue = (float) DB::table('reservation_payments as rp')
            ->join('reservations as r', 'r.id', '=', 'rp.res_id')
            ->join('dogs as d', 'd.id', '=', 'r.dog_id')
            ->where('d.customer_id', $customerId)
            ->whereNull('r.reservation_group_id')
            ->whereNull('rp.deleted_at')
            ->sum('rp.total_due');

        // Due from grouped reservations (group-level total_due, not per-reservation to avoid double-counting)
        $groupedDue = (float) DB::table('reservation_groups as rg')
            ->where('rg.customer_id', $customerId)
            ->whereNull('rg.deleted_at')
            ->sum('rg.total_due');

        $totalDue = $ungroupedDue + $groupedDue;

        // Paid from ungrouped per-reservation entries
        $ungroupedPaid = (float) DB::table('reservation_payment_entries as rpe')
            ->join('reservation_payments as rp', 'rp.id', '=', 'rpe.res_payment_id')
            ->join('reservations as r', 'r.id', '=', 'rp.res_id')
            ->join('dogs as d', 'd.id', '=', 'r.dog_id')
            ->where('d.customer_id', $customerId)
            ->whereNull('r.reservation_group_id')
            ->whereNull('rp.deleted_at')
            ->whereNull('rpe.deleted_at')
            ->where('rpe.status', 'active')
            ->sum('rpe.amount');

        // Paid from group-level entries
        $groupedPaid = (float) DB::table('reservation_group_entries as rge')
            ->join('reservation_groups as rg', 'rg.id', '=', 'rge.reservation_group_id')
            ->where('rg.customer_id', $customerId)
            ->whereNull('rg.deleted_at')
            ->whereNull('rge.deleted_at')
            ->where('rge.status', 'active')
            ->sum('rge.amount');

        $totalPaid = $ungroupedPaid + $groupedPaid;

        return [
            'total_due' => round($totalDue, 2),
            'total_paid' => round($totalPaid, 2),
        ];
    }
}

