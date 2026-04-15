<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalCost extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'additional_costs';
}
