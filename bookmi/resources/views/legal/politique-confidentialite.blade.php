@extends('layouts.public')

@section('title', 'Politique de confidentialité — BookMi')
@section('meta_description', 'Découvrez comment BookMi collecte, utilise et protège vos données personnelles conformément à la réglementation en vigueur.')

@section('head')
<style>
.legal-hero {
    background: linear-gradient(180deg, #0D1117 0%, #111827 100%);
    padding: 5rem 1.5rem 3rem;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.legal-hero .badge {
    display: inline-block;
    background: rgba(33,150,243,0.10);
    border: 1px solid rgba(33,150,243,0.25);
    color: #64B5F6;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 5px 14px;
    border-radius: 100px;
    margin-bottom: 1.25rem;
}
.legal-hero h1 {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 900;
    color: white;
    margin: 0 0 0.75rem;
    letter-spacing: -0.03em;
}
.legal-hero .updated {
    color: rgba(255,255,255,0.35);
    font-size: 0.85rem;
    font-weight: 500;
}

.legal-body {
    max-width: 780px;
    margin: 0 auto;
    padding: 3.5rem 1.5rem 5rem;
    color: rgba(255,255,255,0.75);
}
.legal-body h2 {
    font-size: 1.15rem;
    font-weight: 900;
    color: white;
    margin: 2.5rem 0 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.legal-body h2:first-child { margin-top: 0; }
.legal-body p {
    line-height: 1.75;
    font-size: 0.9375rem;
    font-weight: 500;
    margin: 0 0 1rem;
}
.legal-body ul {
    padding-left: 1.5rem;
    margin: 0 0 1rem;
}
.legal-body ul li {
    line-height: 1.75;
    font-size: 0.9375rem;
    font-weight: 500;
    margin-bottom: 0.4rem;
}
.legal-body strong { color: rgba(255,255,255,0.9); font-weight: 700; }
.legal-body a { color: #2196F3; text-decoration: none; }
.legal-body a:hover { text-decoration: underline; }

.legal-toc {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 16px;
    padding: 1.5rem 1.75rem;
    margin-bottom: 2.5rem;
}
.legal-toc p {
    font-size: 0.75rem;
    font-weight: 800;
    color: rgba(255,255,255,0.4);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.75rem !important;
}
.legal-toc ol {
    padding-left: 1.25rem;
    margin: 0;
}
.legal-toc ol li {
    font-size: 0.875rem;
    font-weight: 600;
    color: rgba(255,255,255,0.55);
    margin-bottom: 0.3rem;
    line-height: 1.5;
}
.legal-toc ol li a {
    color: rgba(255,255,255,0.55);
    text-decoration: none;
    transition: color 0.15s;
}
.legal-toc ol li a:hover { color: #2196F3; }

.legal-highlight {
    background: rgba(33,150,243,0.07);
    border-left: 3px solid #2196F3;
    border-radius: 0 10px 10px 0;
    padding: 1rem 1.25rem;
    margin: 1.25rem 0;
}
.legal-highlight p { margin: 0; color: rgba(255,255,255,0.7); }

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
    font-size: 0.875rem;
}
.data-table th {
    background: rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.7);
    font-weight: 700;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.data-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.65);
    font-weight: 500;
    vertical-align: top;
}
</style>
@endsection

@section('content')

<div class="legal-hero">
    <div class="badge">Données personnelles</div>
    <h1>Politique de confidentialité</h1>
    <p class="updated">Dernière mise à jour : {{ date('d') }} {{ ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'][date('n')-1] }} {{ date('Y') }}</p>
</div>

<div class="legal-body">

    <div class="legal-toc">
        <p>Sommaire</p>
        <ol>
            <li><a href="#pc-1">Responsable du traitement</a></li>
            <li><a href="#pc-2">Données collectées</a></li>
            <li><a href="#pc-3">Finalités du traitement</a></li>
            <li><a href="#pc-4">Base légale des traitements</a></li>
            <li><a href="#pc-5">Partage des données</a></li>
            <li><a href="#pc-6">Conservation des données</a></li>
            <li><a href="#pc-7">Sécurité des données</a></li>
            <li><a href="#pc-8">Vos droits</a></li>
            <li><a href="#pc-9">Cookies et traceurs</a></li>
            <li><a href="#pc-10">Contact</a></li>
        </ol>
    </div>

    <div class="legal-highlight">
        <p>BookMi s'engage à protéger votre vie privée. Cette politique décrit quelles données nous collectons, pourquoi nous les collectons et comment vous pouvez les contrôler.</p>
    </div>

    <h2 id="pc-1">1. Responsable du traitement</h2>
    <p>Le responsable du traitement de vos données personnelles est <strong>BookMi SAS</strong>, dont le siège social est situé à Abidjan, Côte d'Ivoire.</p>
    <p>Pour toute question relative à la protection de vos données, vous pouvez nous contacter à : <a href="mailto:contact@bookmi.ci">contact@bookmi.ci</a></p>

    <h2 id="pc-2">2. Données collectées</h2>
    <p>Dans le cadre de votre utilisation de BookMi, nous collectons les catégories de données suivantes :</p>

    <table class="data-table">
        <thead>
            <tr>
                <th>Catégorie</th>
                <th>Données collectées</th>
                <th>Moment de collecte</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Identité</strong></td>
                <td>Prénom, nom, photo de profil</td>
                <td>Inscription / modification profil</td>
            </tr>
            <tr>
                <td><strong>Contact</strong></td>
                <td>Adresse email, numéro de téléphone</td>
                <td>Inscription</td>
            </tr>
            <tr>
                <td><strong>Vérification</strong></td>
                <td>Document d'identité (CNI, passeport)</td>
                <td>Vérification de compte Talent</td>
            </tr>
            <tr>
                <td><strong>Transactions</strong></td>
                <td>Historique des réservations, montants</td>
                <td>Utilisation du service</td>
            </tr>
            <tr>
                <td><strong>Communications</strong></td>
                <td>Messages échangés entre utilisateurs</td>
                <td>Messagerie in-app</td>
            </tr>
            <tr>
                <td><strong>Technique</strong></td>
                <td>Adresse IP, type d'appareil, token FCM</td>
                <td>Navigation et notifications</td>
            </tr>
        </tbody>
    </table>

    <p>Nous ne collectons que les données strictement nécessaires à la fourniture de nos services. Nous ne collectons jamais de données sensibles (origine raciale, opinions politiques, données biométriques) à l'exception des documents d'identité requis pour la vérification des Talents.</p>

    <h2 id="pc-3">3. Finalités du traitement</h2>
    <p>Vos données sont traitées pour les finalités suivantes :</p>
    <ul>
        <li><strong>Gestion du compte utilisateur :</strong> création, authentification, sécurisation.</li>
        <li><strong>Fourniture du service :</strong> mise en relation Clients/Talents, gestion des réservations, traitement des paiements.</li>
        <li><strong>Vérification d'identité des Talents :</strong> conformité et sécurité de la plateforme.</li>
        <li><strong>Communication :</strong> notifications push, emails transactionnels liés aux réservations.</li>
        <li><strong>Support client :</strong> traitement des demandes et litiges.</li>
        <li><strong>Amélioration du service :</strong> analyses statistiques anonymisées sur l'utilisation de la plateforme.</li>
        <li><strong>Obligations légales :</strong> conformité à la réglementation fiscale et comptable ivoirienne.</li>
    </ul>

    <h2 id="pc-4">4. Base légale des traitements</h2>
    <p>Chaque traitement de données repose sur l'une des bases légales suivantes :</p>
    <ul>
        <li><strong>Exécution du contrat :</strong> traitement nécessaire à la fourniture des services BookMi auxquels vous êtes inscrit(e).</li>
        <li><strong>Consentement :</strong> pour les communications marketing optionnelles. Vous pouvez retirer votre consentement à tout moment.</li>
        <li><strong>Intérêt légitime :</strong> pour la sécurisation de la plateforme, la prévention des fraudes et l'amélioration des services.</li>
        <li><strong>Obligation légale :</strong> pour la conservation des données comptables et fiscales.</li>
    </ul>

    <h2 id="pc-5">5. Partage des données</h2>
    <p>Vos données peuvent être partagées dans les situations suivantes :</p>
    <ul>
        <li><strong>Entre utilisateurs de la plateforme :</strong> les Clients voient les informations publiques du profil du Talent (nom de scène, photos, catégorie, tarifs). Le nom complet et le numéro de téléphone ne sont visibles qu'après réservation confirmée.</li>
        <li><strong>Prestataires de services :</strong> nos partenaires de paiement Mobile Money (opérateurs agréés), hébergeur (serveurs sécurisés), service de notification push (Firebase/Google).</li>
        <li><strong>Obligations légales :</strong> sur réquisition judiciaire ou obligation légale, nous pouvons communiquer vos données aux autorités compétentes.</li>
    </ul>
    <p>Nous ne vendons jamais vos données personnelles à des tiers à des fins commerciales.</p>

    <h2 id="pc-6">6. Conservation des données</h2>
    <p>Vos données sont conservées pour les durées suivantes :</p>
    <ul>
        <li><strong>Données de compte actif :</strong> pendant toute la durée de votre compte et jusqu'à 3 ans après la clôture.</li>
        <li><strong>Données de transaction :</strong> 10 ans conformément aux obligations comptables et fiscales.</li>
        <li><strong>Documents d'identité (Talents) :</strong> pendant la durée de la vérification et 1 an après.</li>
        <li><strong>Messages :</strong> pendant 2 ans après la clôture de la conversation.</li>
        <li><strong>Données techniques (logs) :</strong> 12 mois.</li>
    </ul>

    <h2 id="pc-7">7. Sécurité des données</h2>
    <p>BookMi met en œuvre des mesures techniques et organisationnelles appropriées pour protéger vos données :</p>
    <ul>
        <li>Chiffrement des communications via HTTPS/TLS.</li>
        <li>Hachage des mots de passe (bcrypt).</li>
        <li>Accès aux données restreint au personnel habilité.</li>
        <li>Sauvegardes régulières et plans de continuité.</li>
        <li>Surveillance des accès et détection d'anomalies.</li>
    </ul>
    <p>En cas de violation de données susceptible d'affecter vos droits, nous vous en informerons dans les meilleurs délais conformément à la réglementation applicable.</p>

    <h2 id="pc-8">8. Vos droits</h2>
    <p>Conformément à la réglementation applicable en Côte d'Ivoire et aux meilleures pratiques internationales (RGPD), vous disposez des droits suivants :</p>
    <ul>
        <li><strong>Droit d'accès :</strong> obtenir une copie des données vous concernant.</li>
        <li><strong>Droit de rectification :</strong> corriger des données inexactes ou incomplètes.</li>
        <li><strong>Droit à l'effacement :</strong> demander la suppression de vos données (sous réserve des obligations légales de conservation).</li>
        <li><strong>Droit à la portabilité :</strong> recevoir vos données dans un format structuré et lisible par machine.</li>
        <li><strong>Droit d'opposition :</strong> vous opposer au traitement de vos données pour des raisons légitimes.</li>
        <li><strong>Droit de retrait du consentement :</strong> retirer à tout moment votre consentement aux traitements basés sur celui-ci.</li>
    </ul>
    <p>Pour exercer ces droits, contactez-nous à : <a href="mailto:contact@bookmi.ci">contact@bookmi.ci</a>. Nous répondrons dans un délai de 30 jours.</p>

    <h2 id="pc-9">9. Cookies et traceurs</h2>
    <p>BookMi utilise des cookies sur son site web pour :</p>
    <ul>
        <li><strong>Cookies essentiels :</strong> maintenir votre session authentifiée. Ces cookies sont indispensables au fonctionnement du service et ne peuvent pas être désactivés.</li>
        <li><strong>Cookies de préférence :</strong> mémoriser vos préférences d'affichage et de langue.</li>
    </ul>
    <p>Nous n'utilisons pas de cookies de tracking publicitaire tiers. L'application mobile BookMi n'utilise pas de cookies.</p>

    <h2 id="pc-10">10. Contact</h2>
    <p>Pour toute question, demande d'exercice de droits ou réclamation relative à la protection de vos données personnelles :</p>
    <ul>
        <li>Email : <a href="mailto:contact@bookmi.ci">contact@bookmi.ci</a></li>
        <li>Objet du message : « Protection des données personnelles »</li>
    </ul>
    <p>Si vous estimez que le traitement de vos données n'est pas conforme à la réglementation, vous avez le droit de saisir l'autorité de protection des données compétente en Côte d'Ivoire.</p>

    <div style="margin-top:3rem; padding-top:1.5rem; border-top:1px solid rgba(255,255,255,0.06); display:flex; gap:1rem; flex-wrap:wrap;">
        <a href="{{ route('legal.conditions') }}" style="color:#2196F3; font-size:0.875rem; font-weight:600; text-decoration:none;">Conditions d'utilisation →</a>
        <a href="{{ route('home') }}" style="color:rgba(255,255,255,0.4); font-size:0.875rem; font-weight:600; text-decoration:none;">← Retour à l'accueil</a>
    </div>
</div>

@endsection
