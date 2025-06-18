<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'friends';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function dog()
    {
        return $this->belongsTo(Dog::class);
    }
}
