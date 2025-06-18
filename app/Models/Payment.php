<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
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
}
