<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Preference;
use App\Models\ReservationPayment;
use App\Models\ReservationPaymentEntry;
use App\Models\ReservationAdditionalCost;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    // Reservation status constants
    const STATUS_ACTIVE = 1;

    const STATUS_CHECKED_OUT = 2;

    const STATUS_RESERVED = 3;

    const STATUS_CANCELLED = 4;

    /** Awaiting manual confirmation (e.g. Pfotenstube homepage booking). */
    const STATUS_PENDING_CONFIRMATION = 5;

    protected $guarded = [];

    protected $casts = [
        'checkin_date' => 'datetime',
        'checkout_date' => 'datetime',
    ];

    public function dog()
    {
        return $this->belongsTo(Dog::class, 'dog_id', 'id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function payments()
    {
        return $this->hasManyThrough(
            ReservationPaymentEntry::class,
            ReservationPayment::class,
            'res_id',
            'res_payment_id',
            'id',
            'id'
        );
    }

    public function reservationPayment()
    {
        return $this->hasOne(ReservationPayment::class, 'res_id', 'id');
    }

    public function additionalCosts()
    {
        return $this->hasMany(ReservationAdditionalCost::class, 'reservation_id');
    }

    public function boardingCareAgreement()
    {
        return $this->hasOne(BoardingCareAgreement::class);
    }

    public function reservationGroup()
    {
        return $this->belongsTo(ReservationGroup::class, 'reservation_group_id');
    }

    public function isGrouped(): bool
    {
        return $this->reservation_group_id !== null;
    }

    public function getGrossTotalAttribute()
    {
        if (!$this->plan) {
            return 0;
        }

        $checkin = $this->checkin_date;
        $checkout = $this->checkout_date;
        if (!$checkin || !$checkout) {
            return 0;
        }

        $days = $checkin->diffInDays($checkout);
        if (config('app.days_calculation_mode', 'inclusive') === 'inclusive') {
            $days += 1;
        }
        $days = max(1, $days);

        $planCost = (float)$this->plan->price;
        if ((int)$this->plan->flat_rate !== 1) {
            $planCost *= $days;
        }

        $vatPercentage = Preference::get('vat_percentage', 20);
        $vatMode = config('app.vat_calculation_mode', 'exclusive');

        if ($vatMode === 'inclusive') {
            $grossTotal = $planCost;
        } else {
            $grossTotal = $planCost * (1 + ($vatPercentage / 100));
        }

        $extraTotal = 0;
        foreach ($this->additionalCosts as $cost) {
            $lineTotal = (float)($cost->price ?? 0) * (int)($cost->quantity ?? 1);
            if ($vatMode === 'inclusive') {
                $extraTotal += $lineTotal;
            } else {
                $extraTotal += $lineTotal * (1 + ($vatPercentage / 100));
            }
        }

        return round($grossTotal + $extraTotal, 2);
    }

    public function getTotalPaidAttribute()
    {
        return $this->reservationPayment ? $this->reservationPayment->total_paid : 0;
    }

    public function getAmountDueAttribute()
    {
        return $this->reservationPayment !== null
            ? (float) $this->reservationPayment->total_due
            : $this->gross_total;
    }

    public function getRemainingBalanceAttribute()
    {
        return $this->amount_due - $this->total_paid;
    }
}
