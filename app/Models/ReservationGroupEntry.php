<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservationGroupEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reservation_group_id',
        'amount',
        'overpaid_amount',
        'type',
        'method',
        'note',
        'transaction_date',
        'status',
    ];

    protected $casts = [
        'amount'           => 'float',
        'overpaid_amount'  => 'float',
        'transaction_date' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(ReservationGroup::class, 'reservation_group_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'reservation_group_entry_id');
    }
}
