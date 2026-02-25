<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reçu de paiement — BookMi #{{ $booking->id }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #1a1a2e;
            background: #fff;
            padding: 32px 48px;
            line-height: 1.6;
        }

        /* ── Header ── */
        .header {
            border-bottom: 3px solid #1565C0;
            padding-bottom: 16px;
            margin-bottom: 28px;
        }
        .header-top {
            display: table;
            width: 100%;
        }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .brand {
            font-size: 26px;
            font-weight: bold;
            letter-spacing: -0.5px;
        }
        .brand span { color: #1565C0; }
        .brand-sub {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        .receipt-title {
            font-size: 22px;
            font-weight: bold;
            color: #1565C0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .receipt-ref {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
        }

        /* ── Status badge ── */
        .status-bar {
            background: #E8F5E9;
            border: 1px solid #4CAF50;
            border-radius: 4px;
            padding: 8px 14px;
            margin-bottom: 24px;
            font-size: 11px;
            color: #1B5E20;
        }
        .status-bar strong { font-size: 12px; }

        /* ── Parties ── */
        .parties { display: table; width: 100%; margin-bottom: 24px; }
        .party { display: table-cell; width: 50%; vertical-align: top; }
        .party:first-child { padding-right: 16px; }
        .party-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .party-name {
            font-size: 13px;
            font-weight: bold;
            color: #0A0F1E;
        }
        .party-info {
            font-size: 11px;
            color: #475569;
            margin-top: 2px;
        }

        /* ── Section title ── */
        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 12px;
            margin-top: 20px;
        }

        /* ── Details table ── */
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .details-table td { padding: 6px 0; font-size: 12px; vertical-align: top; }
        .details-table td:last-child { text-align: right; font-weight: bold; }
        .details-table tr.separator td { border-top: 1px dashed #e2e8f0; }

        /* ── Amount breakdown ── */
        .amount-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .amount-table td { padding: 5px 0; font-size: 12px; }
        .amount-table td:last-child { text-align: right; }
        .amount-table tr.total td {
            border-top: 2px solid #1565C0;
            padding-top: 10px;
            font-size: 14px;
            font-weight: bold;
            color: #1565C0;
        }

        /* ── Payment ref ── */
        .ref-box {
            background: #F8FAFC;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px 14px;
            margin-top: 20px;
            font-size: 11px;
        }
        .ref-box .ref-label { color: #94a3b8; font-size: 9px; text-transform: uppercase; letter-spacing: 0.8px; }
        .ref-box .ref-value { font-weight: bold; color: #0A0F1E; margin-top: 3px; font-size: 12px; }

        /* ── Footer ── */
        .footer {
            margin-top: 36px;
            border-top: 1px solid #e2e8f0;
            padding-top: 14px;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-top">
        <div class="header-left">
            <div class="brand">Book<span>Mi</span></div>
            <div class="brand-sub">La plateforme de réservation de talents en Afrique</div>
        </div>
        <div class="header-right">
            <div class="receipt-title">Reçu</div>
            <div class="receipt-ref">N° BKM-{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>
    </div>
</div>

<!-- Status -->
<div class="status-bar">
    ✅ <strong>Paiement confirmé</strong> — Ce document atteste que le paiement a été reçu et sécurisé par BookMi.
</div>

<!-- Parties -->
<div class="parties">
    <div class="party">
        <div class="party-label">Client</div>
        <div class="party-name">{{ trim(($booking->client->first_name ?? '') . ' ' . ($booking->client->last_name ?? '')) ?: 'Client' }}</div>
        <div class="party-info">{{ $booking->client->email ?? '' }}</div>
    </div>
    <div class="party">
        <div class="party-label">Prestataire</div>
        <div class="party-name">{{ $booking->talentProfile->stage_name ?? 'Talent' }}</div>
        <div class="party-info">{{ $booking->talentProfile->user->email ?? '' }}</div>
    </div>
</div>

<!-- Prestation -->
<div class="section-title">Détails de la prestation</div>
<table class="details-table">
    <tr>
        <td>Prestation</td>
        <td>{{ $booking->servicePackage->name ?? ($booking->package_snapshot['name'] ?? '—') }}</td>
    </tr>
    <tr>
        <td>Date de l'événement</td>
        <td>{{ $booking->event_date?->translatedFormat('d F Y') ?? '—' }}</td>
    </tr>
    @if($booking->event_location)
    <tr>
        <td>Lieu</td>
        <td>{{ $booking->event_location }}</td>
    </tr>
    @endif
    <tr class="separator">
        <td>Date de paiement</td>
        <td>{{ $paidAt }}</td>
    </tr>
</table>

<!-- Montants -->
<div class="section-title">Récapitulatif financier</div>
<table class="amount-table">
    <tr>
        <td>Cachet artiste</td>
        <td>{{ number_format($booking->cachet_amount, 0, ',', ' ') }} XOF</td>
    </tr>
    <tr>
        <td>Frais de service BookMi ({{ $commissionRate }}%)</td>
        <td>{{ number_format($booking->commission_amount, 0, ',', ' ') }} XOF</td>
    </tr>
    <tr class="total">
        <td>Total payé</td>
        <td>{{ number_format($booking->total_amount, 0, ',', ' ') }} XOF</td>
    </tr>
</table>

<!-- Référence paiement -->
<div class="ref-box">
    <div class="ref-label">Référence de transaction</div>
    <div class="ref-value">{{ $paymentReference }}</div>
</div>

<!-- Footer -->
<div class="footer">
    <p>BookMi — Reçu généré le {{ now()->translatedFormat('d F Y à H:i') }} UTC</p>
    <p>Ce document est une preuve de paiement électronique. Conservez-le pour vos archives.</p>
    <p style="margin-top:6px; color:#cbd5e1;">bookmi.click · support@bookmi.click</p>
</div>

</body>
</html>
