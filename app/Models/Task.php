<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    protected $table = 'tasks';

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
