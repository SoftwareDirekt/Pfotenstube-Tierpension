<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoices';

    protected $fillable = [
        'invoice_number',
        'hellocash_invoice_id',
        'reservation_id',
        'reservation_group_id',
        'reservation_group_entry_id',
        'res_payment_entry_id',
        'customer_id',
        'type',
        'file_path',
        'status',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function paymentEntry()
    {
        return $this->belongsTo(ReservationPaymentEntry::class, 'res_payment_entry_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function reservationGroup()
    {
        return $this->belongsTo(ReservationGroup::class, 'reservation_group_id');
    }

    public function groupEntry()
    {
        return $this->belongsTo(ReservationGroupEntry::class, 'reservation_group_entry_id');
    }

    public function getFormattedInvoiceNumberAttribute(): string
    {
        return $this->invoice_number ?? 'N/A';
    }
}
