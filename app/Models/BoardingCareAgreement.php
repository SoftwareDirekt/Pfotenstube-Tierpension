<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardingCareAgreement extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_INTAKE_SIGNED = 'intake_signed';

    public const STATUS_COMPLETED = 'completed';

    protected $guarded = [];

    protected $casts = [
        'care_options' => 'array',
        'intake_signed_at' => 'datetime',
        'checkout_signed_at' => 'datetime',
        'email_sent_at' => 'datetime',
    ];

    /**
     * Default §4 structure: all boxes visible on PDF; selected = checked.
     */
    public static function defaultCareOptions(): array
    {
        $emptyFutter = ['on' => false, 'freq' => null];

        return [
            'futter' => [
                'dosenfutter' => $emptyFutter,
                'trockenfutter' => $emptyFutter,
                'fleisch' => $emptyFutter,
                'diaet' => $emptyFutter,
            ],
            'bad' => [
                'bei_abholung' => false,
                'einmal_woche' => false,
                'schur' => false,
            ],
            'medikamente' => [
                'on' => false,
                'freq' => null,
                'note' => '',
            ],
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function canEditForm(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canSignIntake(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canSignCheckout(): bool
    {
        return $this->status === self::STATUS_INTAKE_SIGNED;
    }
}
