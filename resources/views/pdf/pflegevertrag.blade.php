<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 12mm; }
        /* Logos sit out of normal flow so they do not push the title/address downward (DomPDF: position:absolute). */
        body { position: relative; font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #111; line-height: 1.25; }
        .pdf-header-float {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 10;
        }
        .pdf-header-float .header-row { margin-bottom: 0; }
        .pdf-main {
            position: relative;
            z-index: 1;
            padding-top: 90px;
        }
        h1 { font-size: 13pt; text-align: center; margin: 0 0 6px; letter-spacing: 0.02em; }
        .muted { color: #444; font-size: 8pt; }
        .header-row { width: 100%; margin-bottom: 8px; }
        .logo-box { width: 80px; height: 36px; border: 1px dashed #999; text-align: center; font-size: 7pt; color: #666; vertical-align: middle; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.meta th, table.meta td { border: 1px solid #ccc; padding: 3px 5px; vertical-align: top; }
        table.meta th { background: #f3f3f3; width: 28%; font-weight: normal; text-align: left; }
        .section-title { font-weight: bold; margin: 8px 0 4px; font-size: 9.5pt; border-bottom: 1px solid #333; }
        .section-title2 { font-weight: bold; margin: 8px 0 4px; font-size: 9.5pt; }
        .care-grid { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 8pt; }
        .care-grid th, .care-grid td { border: 1px solid #bbb; padding: 3px 4px; }
        .care-grid th { background: #eee; text-align: left; }
        .care-grid .bad-row td { white-space: nowrap; vertical-align: middle; width: 33.33%; }
        .care-center { text-align: center; }
        .cb { font-family: DejaVu Sans, sans-serif; }
        .page-break { page-break-after: always; }
        .legal { font-size: 7.8pt; text-align: justify; }
        .legal h3 { font-size: 9pt; margin: 6px 0 3px; }
        .sig-block { margin-top: 10px; border: 1px solid #ccc; padding: 6px; min-height: 52px; }
        .sig-img { max-height: 44px; max-width: 180px; }
        .price-page-title { font-size: 11pt; text-align: center; margin: 0 0 10px; font-weight: bold; }
        table.price-lines { width: 100%; border-collapse: collapse; font-size: 7.6pt; margin: 0px; }
        table.price-lines td { vertical-align: bottom; }
        table.price-lines .pl-desc { width: 100%; line-height: 1.3; }
        table.price-lines .pl-slot { width: 26%; text-align: right; white-space: nowrap; }
        table.price-lines .pl-dots { border-bottom: 1px dotted #333; display: inline-block; min-width: 52mm; margin: 0 2px 1px 0; }
        .price-footnote { font-size: 7.6pt; margin-top: 10px; text-align: justify; line-height: 1.3; }
        .header-logo1 { max-height: 100px; max-width: 270px; width: auto; height: auto; vertical-align: top; }
        .header-logo2 { max-height: 200px; max-width: 200px; width: auto; height: auto; vertical-align: top; }
    </style>
</head>
<body>

{{-- Page 1 --}}
@php
    $logoLeftPath = public_path('assets/img/pfotenstube2.png');
    $logoRightPath = public_path('assets/img/pfotenstube1.png');
@endphp
<div class="pdf-header-float">
    <table class="header-row" cellspacing="0">
        <tr>
            <td style="width:50%; vertical-align: top;">
                <img src="{{ $logoLeftPath }}" alt="" class="header-logo1">
            </td>
            <td style="width:50%; text-align:right; vertical-align: top;">
                <img src="{{ $logoRightPath }}" alt="" class="header-logo2">
            </td>
        </tr>
    </table>
</div>

<div class="pdf-main">
<h1>PFLEGEVEREINBARUNG</h1>
<p style="text-align:center; margin:0 0 2px; font-size:9pt;"></p>

<p><strong>{{ $org['name'] !== '' ? $org['name'] : '—' }}</strong><br>
    {{ $org['address_line'] !== '' ? $org['address_line'] : '—' }}<br>
    Tel.: {{ $org['phone'] !== '' ? $org['phone'] : '—' }}<br>
    <a href="https://pfotenstube.at" target="_blank">www.pfotenstube.at</a><br>
    <em>{{ $org['role_line'] }}</em>
</p>

@php
    $custZipCity = trim(($customer->zipcode ?? '').' '.($customer->city ?? ''));
    $g = mb_strtolower(rtrim((string) ($dog->gender ?? ''), " \t\n\r\0\x0B\$"));
    $genderLabel = str_contains($g, 'weiblich') ? 'weiblich' : (str_contains($g, 'männlich') || str_contains($g, 'maennlich') ? 'männlich' : '');
    $neuteredRaw = $dog->neutered;
    $neuteredLabel = ($neuteredRaw === null || $neuteredRaw === '') ? '' : (((int) $neuteredRaw === 1) ? 'Ja' : 'Nein');
    $zbNr = trim((string) data_get($dog, 'zb_number', ''));
@endphp

<div class="section-title">1. Eigentümer und Halter</div>
<table class="meta">
    <tr><th>Name (Herr/Frau/Firma)</th><td>{{ $customer->name ?? '' }}</td></tr>
    <tr><th>Straße</th><td>{{ $customer->street ?? '' }}</td></tr>
    <tr><th>PLZ / Ort</th><td>{{ $custZipCity }}</td></tr>
    <tr><th>Tel.</th><td>{{ $customer->phone ?? '' }}</td></tr>
    <tr><th>E-Mail</th><td>{{ $customer->email ?? '' }}</td></tr>
</table>

<p style="font-size:7.8pt;">als Eigentümer und Halter des Tieres wie folgt:</p>
<p style="font-size:7.8pt;">Der Pfleger übernimmt die Betreuung, Versorgung und Pflege des unter 2. Bezeichneten Tieres für den unter 3. bezeichneten Zeitraum zu den im Folgenden vereinbarten Bedingungen.</p>

<div class="section-title">2. Daten des Tieres</div>
<table class="meta">
    <tr><th>Name</th><td>{{ $dog->name ?? '' }}</td></tr>
    <tr><th>Geschlecht</th><td>{{ $genderLabel }}</td></tr>
    <tr><th>Tierart und Rasse</th><td>{{ $dog->compatible_breed ?? '' }}</td></tr>
    <tr><th>ZB-Nr.</th><td>{{ $zbNr }}</td></tr>
    <tr><th>Chip-Nr.</th><td>@if(!empty($dog->chip_not_applicable)) nicht zutreffend @else{{ $dog->chip_number ?? '' }}@endif</td></tr>
    <tr><th>Geburtsdatum / Alter</th><td>{{ $dog->age ?? '' }}</td></tr>
    <tr><th>Kastriert</th><td>{{ $neuteredLabel }}</td></tr>
</table>

<p><strong>Besonderheiten</strong> <span class="muted" style="font-size:7.8pt;">(zB. Problematische Wesenseigenschaften wie Bissigkeit, Krankheiten, beakannte Mängel, Verträglichkeit
mit Artgenossen, Erwachsenen und Kindern, Sicherheit im Straßenverkehr, Wesenstest, Stubenreinheit, Hundeschule):</span></p>
<table class="meta"><tr><td style="min-height:36px;">@if($agreement->besonderheiten){!! nl2br(e($agreement->besonderheiten)) !!}@endif</td></tr></table>

<p style="font-size:7.8pt;">Dem Halter des Tieres sind keine Besonderheiten, Krankheiten oder speziell zu berücksichtigende Befunde des Tieres bekannt.</p>

<div class="section-title">3. Dauer der Pflege</div>
<table class="meta">
    <tr><th>Aufnahmedatum</th><td>{{ $checkin_formatted }}</td></tr>
    <tr><th>Abholdatum (voraussichtlich)</th><td>{{ $checkout_formatted }}</td></tr>
    <tr><th>Voraussichtliche Dauer</th><td>{{ $duration_days }} {{ $duration_days === 1 ? 'Tag' : 'Tage' }}</td></tr>
</table>

<div class="page-break"></div>
<div class="section-title">4. Art der Pflege / Preise</div>
@php
    $f = $care['futter'] ?? [];
    $bad = $care['bad'] ?? [];
    $med = $care['medikamente'] ?? [];
@endphp
<table class="care-grid">
    <thead>
    <tr><th colspan="4">Futter</th></tr>
    <tr><th>Art</th><th>1 Mal/T.</th><th>2 Mal/T.</th><th>3 Mal/T.</th></tr>
    </thead>
    <tbody>
    @foreach ([
        'dosenfutter' => 'Dosenfutter',
        'trockenfutter' => 'Trockenfutter',
        'fleisch' => 'Fleisch',
        'diaet' => 'Diät (Aufpreis € 1,50/Tag)',
    ] as $key => $label)
        @php
            $row = $f[$key] ?? ['on' => false, 'freq' => null];
            $on = !empty($row['on']);
            $fq = isset($row['freq']) ? (int) $row['freq'] : null;
        @endphp
        <tr>
            <td>{{ $label }}</td>
            <td class="cb care-center">{{ ($on && $fq === 1) ? '☑' : '☐' }}</td>
            <td class="cb care-center">{{ ($on && $fq === 2) ? '☑' : '☐' }}</td>
            <td class="cb care-center">{{ ($on && $fq === 3) ? '☑' : '☐' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

@php
    $badChoice = '';
    if (!empty($bad['bei_abholung'])) {
        $badChoice = 'bei_abholung';
    } elseif (!empty($bad['einmal_woche'])) {
        $badChoice = 'einmal_woche';
    } elseif (!empty($bad['schur'])) {
        $badChoice = 'schur';
    }
@endphp
<table class="care-grid" style="margin-top:6px;">
    <thead><tr><th colspan="3">Bad</th></tr></thead>
    <tbody>
    <tr class="bad-row">
        <td><span class="cb">{{ $badChoice === 'bei_abholung' ? '☑' : '☐' }}</span> Bei Abholung</td>
        <td><span class="cb">{{ $badChoice === 'einmal_woche' ? '☑' : '☐' }}</span> 1 Mal / Woche</td>
        <td><span class="cb">{{ $badChoice === 'schur' ? '☑' : '☐' }}</span> Schur</td>
    </tr>
    </tbody>
</table>

@php $mf = isset($med['freq']) ? (int) $med['freq'] : null; $medOn = !empty($med['on']); @endphp
<table class="care-grid" style="margin-top:6px;">
    <thead>
    <tr><th colspan="4">Medikamente</th></tr>
    <tr><th>Notiz</th><th>1 Mal/T.</th><th>2 Mal/T.</th><th>3 Mal/T.</th></tr>
    </thead>
    <tbody>
    <tr>
        <td>{{ $med['note'] ?? '' }}</td>
        <td class="cb care-center">{{ ($medOn && $mf === 1) ? '☑' : '☐' }}</td>
        <td class="cb care-center">{{ ($medOn && $mf === 2) ? '☑' : '☐' }}</td>
        <td class="cb care-center">{{ ($medOn && $mf === 3) ? '☑' : '☐' }}</td>
    </tr>
    </tbody>
</table>

<div class="section-title2">Preise</div>

<table class="price-lines">
    @foreach ([
        'Kleintierpension: pro Käfig € 22,-- (ab 3 Käfige einer gratis)/Tag',
        'Katzenpension: pro Katze € 22,-- (ab 3 Katzen eine gratis)/Tag',
        'Hundepension: kleine Hunde € 22,-, mittelgroße Hunde € 25,-, große Hunde € 28,-- (ab 3 Hunde einer gratis)/Tag',
        'Bad (je nach Tier und Rasse): € 20,-- bis € 45,--',
        'Bad und Schur (je nach Tier und Rasse): € 20,-- bis € 85,--',
        'Einzelhaltung: € 5,--/ Tag',
        'Medikamente oder Diät: € 2,-- Aufpreis/ Tag',
        'Fütterung 2 Mal täglich im Preis inkludiert, 3 Mal/Tag Aufpreis € 2,-- pro Tag, 4 Mal/Tag Aufpreis € 3,-- pro Tag',
        'Heizkostenpauschale von 3,50/Tag (kalte Witterungsverhältnisse)',
        'Abhol- und Bringservice: pro km € 1,--',
        'Spezialservice: Gesundheitscheck und Impfung beim Tierarzt',
        'Spezialpreise bei längeren Aufenthalten (Monatspreis)',
    ] as $priceLine)
    <tr>
        <td class="pl-desc">{{ $priceLine }}</td>
    </tr>
    @endforeach
</table>

<p style="font-size:7.6pt; margin:8px 0 4px;">Rabatt ab 10 Tage Aufenthalt: 3 % auf den Gesamtpreis<br>
Rabatt ab 20 Tage Aufenthalt: 4 % auf den Gesamtpreis<br>
Rabatt ab 30 Tage Aufenthalt: 5 % auf den Gesamtpreis</p>

<table class="price-lines" style="margin-top:6px;">
    <tr>
        <td class="pl-desc"><strong>Gesamt incl. MwSt.:</strong></td>
        <td class="pl-slot"><span class="pl-dots">&nbsp;</span> €</td>
    </tr>
</table>

<p class="price-footnote">Die Hälfte der voraussichtlich anfallenden Kosten ist vor Beginn der Pflegevereinbarung zu bezahlen, die restlichen Kosten sind bei Abholung des Tieres, sohin nach Beendigung der Pflegevereinbarung zu bezahlen.</p>

<div class="legal">
    <h3>5. Rechte und Pflichten der Vertragsparteien / Haftung</h3>
    <p style="margin:0px;">Der Eigentümer des Tieres ist verpflichtet, den Pfleger von besonderen Eigenschaften oder vorhandenen Krankheiten seines Tieres zu unterrichten.</p>
    <p style="margin:0px;">Hunde müssen jährlich gegen Staupe, Hepatitis, Leptospirose, Parvo, Zwingerhusten und Tollwut geimpft werden. Der Eigentümer ist verpflichtet, zumindest 10 Tage vor Beginn der Pflegevereinbarung das Tier gegen dieses Krankheiten impfen zu lassen, das Impfzeugnis ist dem Pfleger vorzuweisen.</p>
    <p style="margin:0px;">Bei auftretender Krankheit ist der Pfleger berechtigt, einen Tierarzt aufzusuchen und erforderliche Maßnahmen auf Kosten des Eigentümers zu ergreifen. Der Eigentümer des Tieres ist verpflichtet, die Tierarztkosten sowie andere nicht vorhersehbare, zusätzliche Aufwendungen (zB Fahrt zum Tierarzt, Reinigungskosten bei Verunreinigungen), zusätzlich zu den vereinbarten Kosten bezahlen.</p>
    <p style="margin:0px;">Der Pfleger erklärt, dass er das notwendige Wissen besitzt um das Tier artgerecht unterzubringen.</p>
    <p style="margin:0px;">Der Eigentümer des Tieres ist verpflichtet, das Tier nach Ablauf des oben genannten Zeitraumes wieder zu sich zu nehmen bzw. bei einer Verlängerung des Zeitraumes sich rechtzeitig vor Ende der vereinbarten Pflegezeit beim Pfleger zu melden und die voraussichtliche Dauer der Pflege bekannt zu geben.</p>
    <p style="margin:0px;">Für den Fall, dass der Eigentümer das Tier nicht bis spätestens 14 Tage nach Ablauf der vereinbarten Pflegedauer abholt und bis zu diesem Zeitpunkt nicht schriftlich mitteilt, für welchen Zeitraum sich der Pflegevertrag verlängern soll, gilt dies als vollständiger und endgültiger Verzicht auf sämtliche rechtliche Ansprüche (insb. Eigentums- und Besitzansprüche) an dem Tier, sodass der Pfleger des Tieres neuer Eigentümer wird.</p>

    <h3>6. Nebenabreden</h3>
    <p style="margin:0px;">Mündliche Nebenabreden werden nicht getroffen. Jede Änderung oder Ergänzung des Vertrags bedarf der Schriftform.</p>
    <p style="margin:0px;">Der Vertrag wird zweifach ausgefertigt und unterzeichnet. Die Parteien erhalten je ein Exemplar. Für den Fall von Streitigkeiten aus diesem Vertrag oder über das Bestehen des Vertrages gilt österreichisches Recht. Gerichtsstand ist Bruck/Leitha.</p>

    <h3>Unterschrift bei Aufnahme</h3>
    <p>Ort: {{ $location_line }}, Datum: @if($agreement->intake_signed_at) {{ $agreement->intake_signed_at->format('d.m.Y') }} @else .................... @endif</p>
    <table style="width:100%;"><tr>
        <td style="width:48%; vertical-align:top;"><strong>Pfleger</strong><div class="sig-block">@if(!empty($org['signature_data_uri']))<img class="sig-img" src="{{ $org['signature_data_uri'] }}" alt="">@else&nbsp;<span class="muted">(Unterschrift vor Ort / Papier)</span>@endif</div></td>
        <td style="width:4%"></td>
        <td style="width:48%; vertical-align:top;"><strong>Eigentümer/Halter</strong><div class="sig-block">@if($intake_signature_data_uri)<img class="sig-img" src="{{ $intake_signature_data_uri }}" alt="">@endif</div></td>
    </tr></table>
    <p class="muted" style="font-size:7pt;">Die Unterschrift des Halters dokumentiert die Abgabe des Tieres an die Pension.</p>

    <h3>7. Abholung</h3>
    <p>Hiermit bestätigt der Eigentümer, das Tier ordnungsgemäß und ohne erkennbare Verletzungen bzw. wesentliche Auffälligkeiten übernommen zu haben.</p>
    <p>Ort: {{ $location_line }}, Datum: @if($agreement->checkout_signed_at) {{ $agreement->checkout_signed_at->format('d.m.Y') }} @else .................... @endif</p>
    <div class="sig-block" style="max-width:70%;"><strong>Unterschrift Eigentümer/Halter</strong><br>
        @if($checkout_signature_data_uri)
            <img class="sig-img" src="{{ $checkout_signature_data_uri }}" alt="">
        @endif
    </div>
</div>

</div>
</body>
</html>
