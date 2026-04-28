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
                'items' => [],
            ],
        ];
    }

    /**
     * Normalized rows for PDF: each row has note, freq (1–3 or null), on (row has data).
     * Supports legacy care_options with only note/freq (no items).
     *
     * @param  array<string, mixed>|null  $care
     * @return list<array{note: string, freq: int|null, on: bool}>
     */
    public static function medikamenteRowsForPdf(?array $care): array
    {
        $med = (array) (($care ?? [])['medikamente'] ?? []);
        $items = $med['items'] ?? null;
        if (is_array($items) && $items !== []) {
            $out = [];
            foreach ($items as $it) {
                if (! is_array($it)) {
                    continue;
                }
                $note = trim((string) ($it['note'] ?? ''));
                $f = (int) ($it['freq'] ?? 0);
                $f = in_array($f, [1, 2, 3], true) ? $f : null;
                if ($note === '' && $f === null) {
                    continue;
                }
                $out[] = [
                    'note' => $note,
                    'freq' => $f,
                    'on' => true,
                ];
            }

            return $out;
        }

        $note = trim((string) ($med['note'] ?? ''));
        $f = isset($med['freq']) ? (int) $med['freq'] : 0;
        $f = in_array($f, [1, 2, 3], true) ? $f : null;
        if ($note === '' && $f === null) {
            return [];
        }
        $parentOn = (bool) ($med['on'] ?? false);

        return [
            [
                'note' => $note,
                'freq' => $f,
                'on' => $parentOn && ($f !== null || $note !== ''),
            ],
        ];
    }

    /**
     * Prefill the admin form. Uses validation old() when provided.
     *
     * @param  array<string, mixed>|null  $care
     * @param  mixed  $oldMedItems
     * @return list<array{note: string, freq: string}>
     */
    public static function medikamenteRowsForForm(?array $care, $oldMedItems = null): array
    {
        if (is_array($oldMedItems) && $oldMedItems !== []) {
            $out = [];
            foreach ($oldMedItems as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $out[] = [
                    'note' => (string) ($row['note'] ?? ''),
                    'freq' => (string) ($row['freq'] ?? ''),
                ];
            }
            if ($out !== []) {
                return $out;
            }
        }

        $fromPdf = self::medikamenteRowsForPdf($care);
        if ($fromPdf === []) {
            return [['note' => '', 'freq' => '']];
        }

        return array_map(static function (array $r): array {
            $f = $r['freq'] ?? null;

            return [
                'note' => (string) ($r['note'] ?? ''),
                'freq' => $f !== null ? (string) $f : '',
            ];
        }, $fromPdf);
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
