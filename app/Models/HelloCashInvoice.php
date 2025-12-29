<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelloCashInvoice extends Model
{
    use HasFactory;

    protected $table = 'hellocash_invoices';
    protected $guarded = [];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}

