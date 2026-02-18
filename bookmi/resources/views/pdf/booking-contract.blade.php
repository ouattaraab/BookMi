<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Contrat de Prestation — BookMi #{{ $booking->id }}</title>
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
        .header .ref {
            font-size: 11px;
            color: #555;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
            margin-bottom: 10px;
        }
        table.info {
            width: 100%;
            border-collapse: collapse;
        }
        table.info td {
            padding: 4px 8px;
            vertical-align: top;
        }
        table.info td.label {
            width: 40%;
            color: #555;
        }
        table.pricing {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.pricing th {
            background: #f0f0f0;
            padding: 6px 10px;
            text-align: left;
            border: 1px solid #ccc;
        }
        table.pricing td {
            padding: 6px 10px;
            border: 1px solid #ccc;
        }
        table.pricing tr.total td {
            font-weight: bold;
            background: #fafafa;
        }
        ul.inclusions {
            margin: 4px 0 0 18px;
            padding: 0;
        }
        ul.inclusions li {
            margin-bottom: 2px;
        }
        .cancellation-box {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px 14px;
            border-radius: 3px;
        }
        .cancellation-box ul {
            margin: 6px 0 0 16px;
            padding: 0;
        }
        .footer {
            margin-top: 40px;
            border-top: 1px solid #ccc;
            padding-top: 14px;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        .signatures {
            margin-top: 30px;
            width: 100%;
        }
        .sig-box {
            display: inline-block;
            width: 45%;
            vertical-align: top;
        }
        .sig-label {
            font-weight: bold;
            margin-bottom: 40px;
        }
        .sig-line {
            border-top: 1px solid #555;
            margin-top: 50px;
            padding-top: 4px;
            font-size: 10px;
            color: #555;
        }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="header">
        <h1>Contrat de Prestation de Services</h1>
        <div class="ref">
            Référence : BOOKMI-{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}
            &nbsp;|&nbsp;
            Émis le {{ now()->format('d/m/Y') }}
        </div>
    </div>

    {{-- PARTIES --}}
    <div class="section">
        <div class="section-title">1. Identification des Parties</div>
        <table class="info">
            <tr>
                <td colspan="2" style="font-weight:bold; padding-bottom:6px;">Le Client</td>
            </tr>
            <tr>
                <td class="label">Nom complet</td>
                <td>{{ $booking->client->name }}</td>
            </tr>
            <tr>
                <td class="label">Email</td>
                <td>{{ $booking->client->email }}</td>
            </tr>
            @if($booking->client->phone)
            <tr>
                <td class="label">Téléphone</td>
                <td>{{ $booking->client->phone }}</td>
            </tr>
            @endif
        </table>

        <br>

        <table class="info">
            <tr>
                <td colspan="2" style="font-weight:bold; padding-bottom:6px;">Le Prestataire (Talent)</td>
            </tr>
            <tr>
                <td class="label">Nom artistique</td>
                <td>{{ $booking->talentProfile->stage_name }}</td>
            </tr>
        </table>
    </div>

    {{-- PRESTATION --}}
    <div class="section">
        <div class="section-title">2. Description de la Prestation</div>
        <table class="info">
            <tr>
                <td class="label">Offre sélectionnée</td>
                <td>{{ $booking->servicePackage->name }}</td>
            </tr>
            <tr>
                <td class="label">Type</td>
                <td>{{ $booking->servicePackage->type->value }}</td>
            </tr>
            @if($booking->servicePackage->description)
            <tr>
                <td class="label">Description</td>
                <td>{{ $booking->servicePackage->description }}</td>
            </tr>
            @endif
            @if($booking->servicePackage->duration_minutes)
            <tr>
                <td class="label">Durée</td>
                <td>{{ $booking->servicePackage->duration_minutes }} minutes</td>
            </tr>
            @endif
            @if($booking->servicePackage->inclusions)
            <tr>
                <td class="label">Inclusions</td>
                <td>
                    <ul class="inclusions">
                        @foreach($booking->servicePackage->inclusions as $item)
                        <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </td>
            </tr>
            @endif
        </table>
    </div>

    {{-- DATE & LIEU --}}
    <div class="section">
        <div class="section-title">3. Date et Lieu de l'Événement</div>
        <table class="info">
            <tr>
                <td class="label">Date</td>
                <td>{{ $booking->event_date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Lieu</td>
                <td>{{ $booking->event_location }}</td>
            </tr>
            @if($booking->message)
            <tr>
                <td class="label">Message du client</td>
                <td>{{ $booking->message }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- PRIX --}}
    <div class="section">
        <div class="section-title">4. Prix et Conditions Financières</div>
        <table class="pricing">
            <tr>
                <th>Désignation</th>
                <th style="text-align:right;">Montant (FCFA)</th>
            </tr>
            <tr>
                <td>Cachet artiste (100% reversé au prestataire)</td>
                <td style="text-align:right;">{{ number_format($booking->cachet_amount, 0, ',', ' ') }}</td>
            </tr>
            <tr>
                <td>Frais de service BookMi ({{ (int) config('bookmi.commission_rate', 15) }}%)</td>
                <td style="text-align:right;">{{ number_format($booking->commission_amount, 0, ',', ' ') }}</td>
            </tr>
            <tr class="total">
                <td>Total dû par le client</td>
                <td style="text-align:right;">{{ number_format($booking->total_amount, 0, ',', ' ') }}</td>
            </tr>
        </table>
        <p style="font-size:10px; color:#555; margin-top:6px;">
            * Le cachet artiste est intégralement reversé au prestataire. Les frais BookMi couvrent la mise en relation, la sécurisation du paiement et le service client.
        </p>
    </div>

    {{-- POLITIQUE D'ANNULATION --}}
    <div class="section">
        <div class="section-title">5. Politique d'Annulation</div>
        <div class="cancellation-box">
            <p>En cas d'annulation par le client :</p>
            <ul>
                <li><strong>Plus de 14 jours avant l'événement</strong> — Remboursement intégral</li>
                <li><strong>Entre 7 et 14 jours avant l'événement</strong> — Remboursement à 50%</li>
                <li><strong>Moins de 7 jours avant l'événement</strong> — Médiation BookMi uniquement, aucun remboursement automatique</li>
            </ul>
        </div>
    </div>

    {{-- CONDITIONS GÉNÉRALES --}}
    <div class="section">
        <div class="section-title">6. Conditions Générales</div>
        <p>
            Le présent contrat est soumis aux dispositions de la loi n° 2013-546 du 30 juillet 2013
            relative aux transactions électroniques en Côte d'Ivoire. En acceptant la réservation,
            les deux parties reconnaissent avoir pris connaissance et accepté l'ensemble des conditions
            générales d'utilisation de la plateforme BookMi disponibles sur le site officiel.
        </p>
        <p>
            Toute contestation relative à l'exécution du présent contrat sera d'abord soumise à la
            médiation BookMi avant tout recours judiciaire.
        </p>
    </div>

    {{-- SIGNATURES --}}
    <table class="signatures">
        <tr>
            <td style="width:50%; vertical-align:top; padding-right:20px;">
                <div class="sig-label">Le Client</div>
                <div class="sig-line">{{ $booking->client->name }}</div>
            </td>
            <td style="width:50%; vertical-align:top; padding-left:20px;">
                <div class="sig-label">Le Prestataire</div>
                <div class="sig-line">{{ $booking->talentProfile->stage_name }}</div>
            </td>
        </tr>
    </table>

    {{-- FOOTER --}}
    <div class="footer">
        Ce document a été généré électroniquement par la plateforme BookMi.<br>
        Référence de réservation : BOOKMI-{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}
    </div>

</body>
</html>
