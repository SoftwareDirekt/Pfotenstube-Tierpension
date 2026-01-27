<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rechnung - {{ $invoice_number }}</title>
    <style>
        @page {
            margin: 35px 40px 45px 40px;
        }
        html, body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8pt;
            color: #000;
            line-height: 1.4;
            background: #fff;
        }
        
        /* Main content wrapper */
        .invoice-wrapper {
            width: auto;
            max-width: 100%;
            padding: 30px;
            box-sizing: border-box;
            margin: 0 auto;
        }

        .header-table,
        .invoice-meta-table,
        .items-table,
        .totals-table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
        }

        
        /* ==================== HEADER SECTION ==================== */
        .header-section {
            width: 100%;
            margin-bottom: 25px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
            padding: 0;
        }
        .logo-cell {
            width: 50%;
        }
        .company-cell {
            width: 50%;
            text-align: right;
        }
        .company-logo img {
            max-width: 120px;
            max-height: 60px;
        }
        .company-name {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .company-details {
            font-size: 8pt;
            line-height: 1.6;
            color: #333;
        }
        
        /* ==================== CUSTOMER SECTION ==================== */
        .customer-section {
            margin-bottom: 20px;
        }
        .customer-info {
            font-size: 8pt;
            line-height: 1.6;
        }
        .customer-type {
            font-size: 8pt;
            color: #666;
            margin-bottom: 2px;
        }
        .customer-name {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        /* ==================== INVOICE TITLE ==================== */
        .invoice-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 15px;
            letter-spacing: 2px;
        }
        
        /* ==================== INVOICE META TABLE ==================== */
        .invoice-meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 8pt;
        }
        .invoice-meta-table th {
            text-align: left;
            font-weight: normal;
            padding: 4px 8px;
            border-bottom: 1px solid #ccc;
            color: #666;
        }
        .invoice-meta-table td {
            padding: 5px 8px;
            font-weight: bold;
        }
        
        /* ==================== ITEMS TABLE ==================== */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8pt;
        }
        .items-table th {
            text-align: left;
            font-weight: bold;
            padding: 6px 6px;
            border-bottom: 2px solid #333;
            font-size: 8pt;
        }
        .items-table th:nth-child(3),
        .items-table th:nth-child(4) {
            text-align: right;
        }
        .items-table td {
            padding: 6px 6px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        .items-table td:first-child {
            text-align: center;
            width: 15%;
        }
        .items-table td:nth-child(2) {
            text-align: left;
            width: 45%;
        }
        .items-table td:nth-child(3) {
            text-align: right;
            width: 20%;
        }
        .items-table td:nth-child(4) {
            text-align: right;
            width: 20%;
        }
        
        /* ==================== TOTAL SECTION ==================== */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 15px;
        }
        .totals-table td {
            padding: 4px 6px;
            font-size: 9pt;
        }
        .totals-table .label {
            text-align: left;
            width: 70%;
        }
        .totals-table .value {
            text-align: right;
            width: 30%;
        }
        
        .totals-table .discount-row td {
            color: #c00;
        }
        .totals-table .total-row td {
            font-weight: bold;
            font-size: 10pt;
            border-top: 2px solid #333;
            padding-top: 6px;
        }
        
        /* ==================== FOOTER ==================== */
        .footer-section {
            margin-top: 25px;
            font-size: 8pt;
            color: #666;
        }
        .footer-thanks {
            font-style: italic;
        }
      
    </style>
</head>
<body>
    <div class="invoice-wrapper">
        <!-- Header Section -->
        <div class="header-section">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        @if(!empty($company['picture_base64']))
                        <div class="company-logo">
                            <img src="{{ $company['picture_base64'] }}" alt="Logo">
                        </div>
                        @endif
                    </td>
                    <td class="company-cell">
                        <div class="company-name">{{ $company['name'] }}</div>
                        <div class="company-details">
                            @if(!empty($company['address']))
                            {{ $company['address'] }}<br>
                            @endif
                            @if(!empty($company['phone']))
                            Telefon: {{ $company['phone'] }}<br>
                            @endif
                            @if(!empty($company['email']))
                            E-Mail: {{ $company['email'] }}<br>
                            @endif
                            @if(!empty($company['iban']))
                            IBAN: {{ $company['iban'] }}<br>
                            @endif
                            @if(!empty($company['bic']))
                            BIC: {{ $company['bic'] }}
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Customer Section -->
        <div class="customer-section">
            <div class="customer-info">
                @if(!empty($customer['type']))
                <div class="customer-type">{{ $customer['type'] }}</div>
                @endif
                <div class="customer-name">{{ $customer['name'] }}</div>
                @if(!empty($customer['address']))
                <div>{{ $customer['address'] }}</div>
                @endif
                @if(!empty($customer['country']))
                <div>{{ $customer['country'] }}</div>
                @endif
            </div>
        </div>

        <!-- Invoice Title -->
        <div class="invoice-title">RECHNUNG</div>

        <!-- Invoice Meta Information -->
        <table class="invoice-meta-table">
            <tr>
                <th style="width: 33%;">Rechnungs-Nr.</th>
                <th style="width: 34%;">Zahlungsart</th>
                <th style="width: 33%; text-align: right;">Datum</th>
            </tr>
            <tr>
                <td>{{ $invoice_number }}</td>
                <td>{{ $payment_method }}</td>
                <td style="text-align: right;">{{ $invoice_date }}</td>
            </tr>
        </table>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Anzahl</th>
                    <th>Beschreibung</th>
                    <th style="text-align: right;">Einzelpreis €</th>
                    <th style="text-align: right;">Gesamt €</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ number_format($item['quantity'], 0) }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td style="text-align: right;">{{ number_format($item['unit_price'], 2, ',', '.') }}</td>
                    <td style="text-align: right;">{{ number_format($item['total_price'], 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Section -->
        <table class="totals-table">
            @if(isset($totals['discount_percentage']) && $totals['discount_percentage'] > 0)
            @php
                $subtotalBeforeDiscount = $totals['net'] + ($totals['discount_amount'] ?? 0);
            @endphp
            <tr class="subtotal-row">
                <td class="label">Zwischensumme</td>
                <td class="value">€{{ number_format($subtotalBeforeDiscount, 2, ',', '.') }}</td>
            </tr>
            <tr class="discount-row">
                <td class="label">Rabatt ({{ number_format($totals['discount_percentage'], 0) }}%)</td>
                <td class="value">-€{{ number_format($totals['discount_amount'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            @endif
            @if(isset($totals['vat']) && $totals['vat'] > 0)
            <tr>
                <td class="label">Netto</td>
                <td class="value">€{{ number_format($totals['net'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">MwSt ({{ number_format($vat_breakdown[0]['vat_percentage'] ?? 0, 0) }}% von €{{ number_format($totals['net'], 2, ',', '.') }})</td>
                <td class="value">€{{ number_format($totals['vat'], 2, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td class="label">Summe</td>
                <td class="value">€{{ number_format($totals['gross'], 2, ',', '.') }}</td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="footer-section">
            <p class="footer-thanks">Vielen Dank für Ihren Besuch!</p>
        </div>
    </div>

</body>
</html>
