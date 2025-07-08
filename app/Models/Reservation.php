<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

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
}
