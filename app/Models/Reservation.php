<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;
    
    // Reservation status constants
    const STATUS_ACTIVE = 1;
    const STATUS_CHECKED_OUT = 2;
    const STATUS_RESERVED = 3;
    const STATUS_CANCELLED = 4;
    /** Awaiting manual confirmation (e.g. Pfotenstube homepage booking). */
    const STATUS_PENDING_CONFIRMATION = 5;

    protected $guarded = [];

    protected $casts = [
        'checkin_date' => 'datetime',
        'checkout_date' => 'datetime',
    ];

    public function dog()
    {
        return $this->belongsTo(Dog::class, 'dog_id','id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'res_id', 'id');
    }
}
