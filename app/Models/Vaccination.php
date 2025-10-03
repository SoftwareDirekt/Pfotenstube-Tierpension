<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vaccination extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'vaccination_date' => 'date',
        'next_vaccination_date' => 'date'
    ];

    public function dog()
    {
        return $this->belongsTo(Dog::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}