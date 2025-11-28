<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentSettlement;
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
        $payments = Payment::whereHas('reservation.dog', function($query) use ($customerId) {
                $query->where('customer_id', $customerId);
            })
            ->with('settlementsReceived')
            ->get();

        $calculatedBalance = 0;

        foreach ($payments as $payment) {
            $advance = $payment->advance_payment ?? 0;
            $originalRemaining = $payment->remaining_amount ?? 0;
            $settledAmount = (float) $payment->settlementsReceived->sum('amount_settled');
            $effectiveRemaining = max(0, $originalRemaining - $settledAmount);
            $wallet = $payment->wallet_amount ?? 0;
            
            $calculatedBalance += ($advance - $effectiveRemaining - $wallet);
        }

        // Update customer balance
        $customer = Customer::find($customerId);
        if ($customer) {
            $customer->balance = round($calculatedBalance, 2);
            $customer->save();
        }

        return round($calculatedBalance, 2);
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
        return $customer ? (float)($customer->balance ?? 0) : 0;
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
        // Get all payments with remaining amounts for this customer, ordered chronologically
        // Calculate effective remaining (original - settlements already received)
        $oldPayments = Payment::whereHas('reservation.dog', function($query) use ($customerId) {
                $query->where('customer_id', $customerId);
            })
            ->where('id', '!=', $settlingPaymentId) // Don't settle the payment with itself
            ->orderBy('created_at', 'asc')
            ->get();

        $settledPayments = [];
        $remainingSettlement = $amountToSettle;

        foreach ($oldPayments as $payment) {
            if ($remainingSettlement <= 0.01) {
                break; // No more money to settle
            }

            $originalRemaining = (float) ($payment->remaining_amount ?? 0);
            if (!$payment->relationLoaded('settlementsReceived')) {
                $payment->load('settlementsReceived');
            }
            $effectiveRemaining = max(0, $originalRemaining - (float) $payment->settlementsReceived->sum('amount_settled'));

            if ($effectiveRemaining > 0.01) {
                $settledAmount = round(min($effectiveRemaining, $remainingSettlement), 2);
                
                if (!PaymentSettlement::where('settling_payment_id', $settlingPaymentId)
                    ->where('settled_payment_id', $payment->id)
                    ->exists()) {
                    PaymentSettlement::create([
                        'settling_payment_id' => $settlingPaymentId,
                        'settled_payment_id' => $payment->id,
                        'amount_settled' => $settledAmount,
                    ]);
                } else {
                    continue;
                }

                $newEffectiveRemaining = round($effectiveRemaining - $settledAmount, 2);
                
                // Update status if fully settled - use lockForUpdate to prevent race conditions
                if ($newEffectiveRemaining < 0.01 && in_array($payment->status, [0, 2])) {
                    $payment->lockForUpdate()->update(['status' => 1]);
                }

                $settledPayments[] = [
                    'payment_id' => $payment->id,
                    'settled_amount' => round($settledAmount, 2),
                    'original_remaining' => $originalRemaining,
                    'effective_remaining_before' => $effectiveRemaining,
                    'effective_remaining_after' => $newEffectiveRemaining,
                ];

                $remainingSettlement -= $settledAmount;
            }
        }

        return [
            'settled_payments' => $settledPayments,
            'remaining_settlement' => round($remainingSettlement, 2),
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
                $oldPaymentsSettled = $this->settleOldPaymentRecords($customerId, $settlingPaymentId, $debtSettled)['settled_payments'];
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

    /**
     * Update balance when payment is deleted
     * Handles settlements: reverses settlements made and received
     * 
     * @param Payment $payment
     * @return void
     */
    public function handlePaymentDeletion(Payment $payment): void
    {
        $reservation = $payment->reservation;
        if (!$reservation || !$reservation->dog) {
            return;
        }

        $customerId = $reservation->dog->customer_id;
        $advance = $payment->advance_payment ?? 0;
        $remaining = $payment->remaining_amount ?? 0;
        $wallet = $payment->wallet_amount ?? 0;

        $payment->load(['settlementsMade', 'settlementsReceived']);

        foreach ($payment->settlementsMade as $settlement) {
            DB::table('customers')
                ->where('id', $customerId)
                ->decrement('balance', (float) $settlement->amount_settled);
        }

        foreach ($payment->settlementsReceived as $settlement) {
            DB::table('customers')
                ->where('id', $customerId)
                ->decrement('balance', (float) $settlement->amount_settled);
        }

        DB::table('customers')
            ->where('id', $customerId)
            ->decrement('balance', $advance - $remaining - $wallet);
    }
}

