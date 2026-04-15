<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservationGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'total_due',
        'status',
    ];

    protected $casts = [
        'total_due' => 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'reservation_group_id');
    }

    public function entries()
    {
        return $this->hasMany(ReservationGroupEntry::class, 'reservation_group_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'reservation_group_id');
    }

    public function activeEntries()
    {
        return $this->entries()->where('status', 'active');
    }

    public function totalPaid(): float
    {
        return (float) $this->activeEntries()->sum('amount');
    }

    public function remaining(): float
    {
        return round((float) $this->total_due - $this->totalPaid(), 2);
    }

    public function refreshStatus(): void
    {
        $paid = $this->totalPaid();
        $tol = 0.01;

        if ($paid >= (float) $this->total_due - $tol) {
            $this->update(['status' => 'paid']);
        } elseif ($paid > $tol) {
            $this->update(['status' => 'partial']);
        } else {
            $this->update(['status' => 'unpaid']);
        }
    }

    /**
     * Recalculate total_due from all child reservations' gross_total (excludes cancelled).
     */
    public function recalculateTotalDue(): float
    {
        $total = 0.0;
        foreach ($this->reservations()
            ->where('status', '!=', Reservation::STATUS_CANCELLED)
            ->with(['plan', 'additionalCosts'])
            ->get() as $res) {
            $total += (float) $res->gross_total;
        }
        $total = round($total, 2);
        $this->update(['total_due' => $total]);

        return $total;
    }
}
