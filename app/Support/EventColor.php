<?php

namespace App\Support;

class EventColor
{
    private const DEFAULT = [
        'backgroundColor' => '#9E9E9E',
        'textColor' => '#FFFFFF',
    ];

    private const PALETTE = [
        'Arbeit' => [
            'backgroundColor' => '#2196F3',
            'textColor' => '#FFFFFF',
        ],
        'Urlaub' => [
            'backgroundColor' => '#4CAF50',
            'textColor' => '#FFFFFF',
        ],
        'Krankenstand' => [
            'backgroundColor' => '#F44336',
            'textColor' => '#FFFFFF',
        ],
        'Andere' => [
            'backgroundColor' => '#9E9E9E',
            'textColor' => '#FFFFFF',
        ],
    ];

    public static function forStatus(?string $status): array
    {
        return self::PALETTE[$status] ?? self::DEFAULT;
    }

    public static function statuses(): array
    {
        return array_keys(self::PALETTE);
    }
}

