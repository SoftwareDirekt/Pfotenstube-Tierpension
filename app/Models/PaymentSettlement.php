<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSettlement extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    protected $table = 'payment_settlements';

    /**
     * The payment that is settling the debt
     */
    public function settlingPayment()
    {
        return $this->belongsTo(Payment::class, 'settling_payment_id');
    }

    /**
     * The old payment whose debt is being settled
     */
    public function settledPayment()
    {
        return $this->belongsTo(Payment::class, 'settled_payment_id');
    }
}
