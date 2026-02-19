<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Attestation de Revenus {{ $year }} — {{ $talent['stage_name'] }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #222;
            margin: 0;
            padding: 20px 40px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #111;
            padding-bottom: 12px;
            margin-bottom: 24px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header .subtitle {
            font-size: 13px;
            color: #555;
        }
        .section {
            margin-bottom: 20px;
        }
        .section h2 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
        .field-row {
            display: flex;
            margin-bottom: 4px;
        }
        .field-label {
            width: 160px;
            font-weight: bold;
            color: #555;
            flex-shrink: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 11px;
        }
        th {
            background-color: #f5f5f5;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #ddd;
            font-size: 10px;
            text-transform: uppercase;
        }
        td {
            padding: 5px 8px;
            border: 1px solid #eee;
        }
        tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .total-row td {
            font-weight: bold;
            background-color: #f0f0f0;
            border-top: 2px solid #ccc;
        }
        .amount {
            text-align: right;
        }
        .footer {
            margin-top: 32px;
            font-size: 10px;
            color: #888;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 12px;
        }
        .highlight-box {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 12px 16px;
            margin-bottom: 16px;
        }
        .highlight-box .amount-large {
            font-size: 22px;
            font-weight: bold;
            color: #111;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>BookMi — Attestation de Revenus</h1>
        <div class="subtitle">Année {{ $year }} — Générée le {{ $generated_at }}</div>
    </div>

    {{-- Talent identity --}}
    <div class="section">
        <h2>Identité du talent</h2>
        <div class="field-row">
            <span class="field-label">Nom de scène</span>
            <span>{{ $talent['stage_name'] }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Nom complet</span>
            <span>{{ $talent['full_name'] }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Email</span>
            <span>{{ $talent['email'] }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Téléphone</span>
            <span>{{ $talent['phone'] }}</span>
        </div>
    </div>

    {{-- Summary --}}
    <div class="section">
        <h2>Récapitulatif annuel</h2>
        <div class="highlight-box">
            <div style="color:#555; font-size:11px; margin-bottom:4px;">Revenus nets perçus en {{ $year }}</div>
            <div class="amount-large">{{ number_format($totals['net_amount_xof'], 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="field-row">
            <span class="field-label">Prestations effectuées</span>
            <span>{{ $totals['bookings_count'] }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Montant brut</span>
            <span>{{ number_format($totals['gross_amount_xof'], 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="field-row">
            <span class="field-label">Commission BookMi</span>
            <span>{{ number_format($totals['commission_xof'], 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="field-row">
            <span class="field-label">Montant net reçu</span>
            <span><strong>{{ number_format($totals['net_amount_xof'], 0, ',', ' ') }} FCFA</strong></span>
        </div>
    </div>

    {{-- Monthly breakdown --}}
    @if(count($monthly_breakdown) > 0)
    <div class="section">
        <h2>Détail mensuel</h2>
        <table>
            <thead>
                <tr>
                    <th>Mois</th>
                    <th class="amount">Prestations</th>
                    <th class="amount">Montant brut (FCFA)</th>
                    <th class="amount">Commission (FCFA)</th>
                    <th class="amount">Net perçu (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $monthNames = [
                        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
                    ];
                @endphp
                @foreach($monthly_breakdown as $row)
                <tr>
                    <td>{{ $monthNames[$row['month']] ?? $row['month'] }}</td>
                    <td class="amount">{{ $row['bookings_count'] }}</td>
                    <td class="amount">{{ number_format($row['gross_amount_xof'], 0, ',', ' ') }}</td>
                    <td class="amount">{{ number_format($row['commission_xof'], 0, ',', ' ') }}</td>
                    <td class="amount">{{ number_format($row['net_amount_xof'], 0, ',', ' ') }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td>Total {{ $year }}</td>
                    <td class="amount">{{ $totals['bookings_count'] }}</td>
                    <td class="amount">{{ number_format($totals['gross_amount_xof'], 0, ',', ' ') }}</td>
                    <td class="amount">{{ number_format($totals['commission_xof'], 0, ',', ' ') }}</td>
                    <td class="amount">{{ number_format($totals['net_amount_xof'], 0, ',', ' ') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @else
    <div class="section">
        <p style="color:#888; font-style:italic;">Aucune prestation complétée en {{ $year }}.</p>
    </div>
    @endif

    <div class="footer">
        Ce document est une attestation officielle générée automatiquement par la plateforme BookMi.<br>
        Pour toute question, contactez support@bookmi.app
    </div>

</body>
</html>
