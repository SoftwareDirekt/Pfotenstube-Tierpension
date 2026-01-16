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
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
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

