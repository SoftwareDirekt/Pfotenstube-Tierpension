<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservationPaymentEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'datetime',
    ];

    public function reservationPayment()
    {
        return $this->belongsTo(ReservationPayment::class, 'res_payment_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'res_payment_entry_id');
    }
}
