<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Customer extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    protected $table = 'customers';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function dogs()
    {
        return $this->hasMany(Dog::class);
    }

    public function account()
    {
        return $this->hasOne(CustomerAccount::class);
    }
}

