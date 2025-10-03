<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'read' => 'boolean'
    ];

    public function dog()
    {
        return $this->belongsTo(Dog::class);
    }

    public function vaccination()
    {
        return $this->belongsTo(Vaccination::class);
    }
}
