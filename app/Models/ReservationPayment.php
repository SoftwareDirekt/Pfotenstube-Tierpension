<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservationPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'res_id');
    }

    public function entries()
    {
        return $this->hasMany(ReservationPaymentEntry::class, 'res_payment_id');
    }

    public function getTotalPaidAttribute()
    {
        return $this->entries()->where('status', 'active')->sum('amount');
    }

    public function getBalanceAttribute()
    {
        return (float)$this->total_due - (float)$this->total_paid;
    }
}
