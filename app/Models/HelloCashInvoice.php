<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelloCashInvoice extends Model
{
    use HasFactory;

    protected $table = 'hellocash_invoices';
    protected $guarded = [];
    
    protected $casts = [
        'invoice_number' => 'integer',
        'is_grouped' => 'boolean',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Payments linked to this invoice (for grouped invoices)
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    /**
     * Get reservation_ids as array
     */
    public function getReservationIdsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set reservation_ids as JSON
     */
    public function setReservationIdsAttribute($value)
    {
        $this->attributes['reservation_ids'] = $value ? json_encode($value) : null;
    }

    /**
     * Get formatted invoice number for display
     * Returns UEB-01, UEB-02, etc. for local invoices
     * Returns HelloCash invoice ID for cashier invoices
     */
    public function getFormattedInvoiceNumberAttribute(): string
    {
        if ($this->invoice_type === 'local' && $this->invoice_number) {
            $number = (int)$this->invoice_number;
            if ($number < 100) {
                return 'UEB-' . str_pad($number, 2, '0', STR_PAD_LEFT);
            }
            return 'UEB-' . $number;
        }
        
        // Cashier (HelloCash) invoices
        return (string)($this->hellocash_invoice_id ?? 'N/A');
    }
}

