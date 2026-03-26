@extends('emails.layouts.pfotenstube')

@section('title', 'Reservierungsanfrage – Pfotenstube')

@section('preheader')
    Information zu Ihrer Anfrage für {{ $reservation->dog->name ?? 'Ihren Hund' }}.
@endsection

@section('badge')
    <span style="display:inline-block;padding:6px 14px;background-color:#8a4a3a;color:#FEF5E8;font-family:'Segoe UI',Roboto,Arial,sans-serif;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;border-radius:4px;border:1px solid #6D381A;">
        Nicht angenommen
    </span>
@endsection

@section('heading', 'Guten Tag')

@section('content')
    <p style="margin:0 0 16px;">
        Ihre <strong style="color:#6D381A;">Reservierungsanfrage</strong> für die Tierpension wurde leider <strong style="color:#6D381A;">nicht angenommen</strong>.
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
                            <span style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;color:#CDA275;font-weight:600;">Gewünschter Zeitraum</span><br>
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
    <p style="margin:0;">Bei Fragen erreichen Sie uns gerne wie gewohnt.</p>
@endsection
