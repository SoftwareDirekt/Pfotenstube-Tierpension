@extends('emails.layouts.pfotenstube')

@section('title', 'Reservierung bestätigt – Pfotenstube')

@section('preheader')
    Ihre Reservierungsanfrage wurde bestätigt für {{ $reservation->dog->name ?? 'Ihren Hund' }}.
@endsection

@section('badge')
    <span style="display:inline-block;padding:6px 14px;background-color:#CDA275;color:#FEF5E8;font-family:'Segoe UI',Roboto,Arial,sans-serif;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;border-radius:4px;">
        Bestätigt
    </span>
@endsection

@section('heading', 'Guten Tag')

@section('content')
    <p style="margin:0 0 16px;">
        Ihre <strong style="color:#6D381A;">Reservierungsanfrage</strong> wurde <strong style="color:#6D381A;">angenommen und bestätigt</strong>.
    </p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background-color:#F8F8F8;border-radius:6px;border:1px solid #e8dfd0;">
        <tr>
            <td style="padding:18px 20px;font-family:'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;font-size:15px;color:#6D381A;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="padding:6px 0;border-bottom:1px solid #e8dfd0;">
                            <span style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;color:#CDA275;font-weight:600;">Hund</span><br>
                            <span style="font-size:16px;font-weight:600;color:#6D381A;">{{ $reservation->dog->name ?? '—' }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 0 6px;">
                            <span style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;color:#CDA275;font-weight:600;">Zeitraum</span><br>
                            <span style="font-size:16px;font-weight:600;color:#6D381A;">
                                {{ $reservation->checkin_date->format('d.m.Y') }}
                                <span style="font-weight:400;color:#8a7568;"> bis </span>
                                {{ $reservation->checkout_date->format('d.m.Y') }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <p style="margin:0 0 16px;">Wir freuen uns auf den Besuch Ihres Lieblings.</p>

    @php
        // Opening hours keyed by Carbon day-of-week integer (0 = Sunday … 6 = Saturday)
        $openingHours = [
            1 => ['label' => 'Montag',     'von' => '10:00', 'bis' => '18:00'],
            2 => ['label' => 'Dienstag',   'von' => '10:00', 'bis' => '18:00'],
            3 => ['label' => 'Mittwoch',   'von' => '10:00', 'bis' => '18:00'],
            4 => ['label' => 'Donnerstag', 'von' => '10:00', 'bis' => '12:00'],
            5 => ['label' => 'Freitag',    'von' => '10:00', 'bis' => '18:00'],
            6 => ['label' => 'Samstag',    'von' => '10:00', 'bis' => '12:00'],
            0 => ['label' => 'Sonntag',    'von' => null,    'bis' => null   ],
        ];

        $checkin    = $reservation->checkin_date;
        $dayOfWeek  = (int) $checkin->format('w'); // 0 = Sunday, 6 = Saturday
        $dayInfo    = $openingHours[$dayOfWeek];
        $dateLabel  = $checkin->format('d.m.Y');
        $dayLabel   = $dayInfo['label'];

        if ($dayInfo['von'] !== null) {
            $dropOffLine = "Bitte bringen Sie Ihr Tier am {$dateLabel} ({$dayLabel}) zwischen {$dayInfo['von']} Uhr und {$dayInfo['bis']} Uhr bei uns vorbei.";
        } else {
            $dropOffLine = "Ihr Check-in-Datum fällt auf einen {$dayLabel} ({$dateLabel}). Bitte kontaktieren Sie uns telefonisch, um einen Abgabetermin zu vereinbaren.";
        }
    @endphp

    <p style="margin:0;color:#6D381A;">{{ $dropOffLine }}</p>
@endsection
