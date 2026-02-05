<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;
    
    // Payment status constants
    const STATUS_NOT_PAID = 0;
    const STATUS_PAID = 1;
    const STATUS_PARTIAL = 2;
    
    protected $guarded = [];
    protected $table = 'payments';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'res_id', 'id');
    }

    /**
     * Settlements where this payment settled other payments' debts
     */
    public function settlementsMade()
    {
        return $this->hasMany(PaymentSettlement::class, 'settling_payment_id');
    }

    /**
     * Settlements where other payments settled this payment's debt
     */
    public function settlementsReceived()
    {
        return $this->hasMany(PaymentSettlement::class, 'settled_payment_id');
    }

    /**
     * Get effective remaining amount (original remaining - settlements received)
     * Uses loaded relationship if available, otherwise queries
     */
    public function getEffectiveRemainingAmountAttribute(): float
    {
        $originalRemaining = (float) ($this->remaining_amount ?? 0);
        
        // Use loaded relationship if available (more efficient)
        if ($this->relationLoaded('settlementsReceived')) {
            $settledAmount = (float) $this->settlementsReceived->sum('amount_settled');
        } else {
            $settledAmount = (float) $this->settlementsReceived()->sum('amount_settled');
        }
        
        return max(0, round($originalRemaining - $settledAmount, 2));
    }
}
