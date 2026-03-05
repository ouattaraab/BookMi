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
.legal-body h3 {
    font-size: 0.975rem;
    font-weight: 700;
    color: rgba(255,255,255,0.85);
    margin: 1.5rem 0 0.5rem;
}
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
    <p class="updated">Dernière mise à jour : 5 mars 2026</p>
</div>

<div class="legal-body">

    <div class="legal-toc">
        <p>Sommaire</p>
        <ol>
            <li><a href="#pc-1">Responsable du traitement</a></li>
            <li><a href="#pc-2">Cadre juridique applicable</a></li>
            <li><a href="#pc-3">Données collectées</a></li>
            <li><a href="#pc-4">Finalités du traitement</a></li>
            <li><a href="#pc-5">Bases légales du traitement</a></li>
            <li><a href="#pc-6">Partage des données</a></li>
            <li><a href="#pc-7">Durée de conservation</a></li>
            <li><a href="#pc-8">Sécurité des données</a></li>
            <li><a href="#pc-9">Droits des utilisateurs</a></li>
            <li><a href="#pc-10">Cookies et technologies similaires</a></li>
            <li><a href="#pc-11">Notifications push</a></li>
            <li><a href="#pc-12">Protection des mineurs</a></li>
            <li><a href="#pc-13">Modification de la politique</a></li>
            <li><a href="#pc-14">Violation de données</a></li>
            <li><a href="#pc-15">Clause de consentement éclairé</a></li>
        </ol>
    </div>

    <div class="legal-highlight">
        <p>La présente Politique de Confidentialité décrit la manière dont la Plateforme BookMi collecte, utilise, conserve, protège et partage les données personnelles de ses Utilisateurs. Cette politique fait partie intégrante des <a href="{{ route('legal.conditions') }}">Conditions Générales d'Utilisation</a> de BookMi.</p>
    </div>

    <h2 id="pc-1">Article 1 – Responsable du traitement</h2>
    <p>Le responsable du traitement des données à caractère personnel est la société éditrice de la Plateforme BookMi, dont le siège social est situé à Abidjan, Côte d'Ivoire.</p>
    <p>Pour toute question relative à la protection de vos données personnelles, vous pouvez nous contacter à l'adresse : <a href="mailto:support@bookmi.click">support@bookmi.click</a></p>

    <h2 id="pc-2">Article 2 – Cadre juridique applicable</h2>
    <p>La présente Politique de Confidentialité est établie en conformité avec :</p>
    <ul>
        <li>La loi n°2013-450 du 19 juin 2013 relative à la protection des données à caractère personnel en Côte d'Ivoire</li>
        <li>La loi n°2013-451 du 19 juin 2013 relative à la lutte contre la cybercriminalité en Côte d'Ivoire</li>
        <li>La loi n°2013-546 du 30 juillet 2013 relative aux transactions électroniques en Côte d'Ivoire</li>
        <li>L'Ordonnance n°2012-293 du 21 mars 2012 relative aux télécommunications et aux technologies de l'information</li>
        <li>Le Règlement Général sur la Protection des Données (RGPD – UE 2016/679) pour les Utilisateurs résidant dans l'Union Européenne</li>
        <li>La Directive de la CEDEAO C/DIR/1/08/11 sur la protection des données personnelles</li>
        <li>La Convention de l'Union Africaine sur la cybersécurité et la protection des données à caractère personnel (Convention de Malabo, 2014)</li>
        <li>Toute autre législation nationale ou régionale applicable en matière de protection des données personnelles</li>
    </ul>

    <h2 id="pc-3">Article 3 – Données collectées</h2>
    <h3>3.1 Données fournies directement par l'Utilisateur</h3>
    <p>Lors de l'inscription et de l'utilisation de la Plateforme, les données suivantes sont collectées :</p>
    <ul>
        <li><strong>Données d'identification</strong> : nom, prénom, adresse e-mail, numéro de téléphone, photo de profil (avatar)</li>
        <li><strong>Données de vérification d'identité</strong> : copie de pièce d'identité officielle (carte d'identité nationale, passeport), selfie de vérification</li>
        <li><strong>Données de profil professionnel (Talents)</strong> : biographie, catégories de services, compétences, niveau de talent, portfolio (photos, vidéos, échantillons de travail)</li>
        <li><strong>Données de localisation</strong> : ville, coordonnées GPS (latitude/longitude) lorsque l'Utilisateur active la géolocalisation</li>
        <li><strong>Données financières</strong> : méthode de paiement/versement, détails de compte Mobile Money ou bancaire pour les Talents</li>
        <li><strong>Données de communication</strong> : messages échangés via la messagerie interne (texte, audio, images, vidéos)</li>
        <li><strong>Données d'évaluation</strong> : notes, commentaires et avis publiés</li>
    </ul>
    <h3>3.2 Données collectées automatiquement</h3>
    <ul>
        <li><strong>Données de connexion</strong> : adresse IP, type de navigateur, système d'exploitation, dates et heures de connexion</li>
        <li><strong>Données d'utilisation</strong> : pages visitées, fonctionnalités utilisées, durée des sessions, historique de recherche</li>
        <li><strong>Données de l'appareil</strong> : identifiant unique de l'appareil, modèle, version du système d'exploitation</li>
        <li><strong>Cookies et technologies similaires</strong> : pour l'authentification, les préférences et l'analyse d'utilisation</li>
        <li><strong>Jetons de notification push</strong> (Firebase Cloud Messaging)</li>
    </ul>
    <h3>3.3 Données sensibles</h3>
    <p>BookMi ne collecte pas intentionnellement de données sensibles telles que l'origine ethnique, les convictions religieuses, les opinions politiques, les données de santé ou l'orientation sexuelle. Si de telles données sont volontairement communiquées par l'Utilisateur dans son profil ou ses communications, elles seront traitées avec le même niveau de protection que les autres données personnelles.</p>

    <h2 id="pc-4">Article 4 – Finalités du traitement</h2>
    <p>Les données personnelles sont traitées pour les finalités suivantes :</p>
    <ul>
        <li><strong>Gestion des comptes utilisateurs</strong> : création, authentification, vérification d'identité et maintenance des comptes</li>
        <li><strong>Fourniture des Services</strong> : mise en relation entre Clients et Talents, gestion des réservations, traitement des paiements et versements</li>
        <li><strong>Communication</strong> : messagerie interne, notifications push, alertes de réservation, rappels</li>
        <li><strong>Sécurité et prévention de la fraude</strong> : vérification d'identité, détection d'activités frauduleuses, protection des transactions</li>
        <li><strong>Amélioration des Services</strong> : analyse des comportements d'utilisation, statistiques agrégées, optimisation de l'expérience utilisateur</li>
        <li><strong>Support client</strong> : traitement des réclamations, médiation des litiges, assistance technique</li>
        <li><strong>Obligations légales et réglementaires</strong> : réponse aux réquisitions judiciaires, obligations fiscales, lutte contre le blanchiment d'argent</li>
        <li><strong>Marketing et promotion (avec consentement)</strong> : envoi d'offres promotionnelles, programme de parrainage, recommandations personnalisées</li>
    </ul>

    <h2 id="pc-5">Article 5 – Bases légales du traitement</h2>
    <p>Conformément à la loi n°2013-450 et au RGPD (le cas échéant), le traitement des données personnelles repose sur les bases légales suivantes :</p>
    <ul>
        <li><strong>Le consentement de l'Utilisateur</strong> : pour l'inscription, le traitement des données de profil, la géolocalisation, les communications marketing et l'utilisation des cookies non essentiels</li>
        <li><strong>L'exécution du contrat</strong> : pour la fourniture des Services, le traitement des réservations et des paiements, la vérification d'identité requise pour les transactions</li>
        <li><strong>L'intérêt légitime de l'Éditeur</strong> : pour la prévention de la fraude, la sécurité de la Plateforme, l'amélioration des Services et la modération des contenus</li>
        <li><strong>Les obligations légales</strong> : pour la conservation des données de transaction, la réponse aux réquisitions judiciaires et les obligations fiscales</li>
    </ul>

    <h2 id="pc-6">Article 6 – Partage des données</h2>
    <h3>6.1 Destinataires des données</h3>
    <p>Les données personnelles peuvent être partagées avec :</p>
    <ul>
        <li><strong>Les autres Utilisateurs de la Plateforme</strong> : dans le cadre de la mise en relation (profil public du Talent visible par les Clients, informations de réservation partagées entre Client et Talent)</li>
        <li><strong>Les prestataires de services de paiement</strong> : Paystack et FedaPay pour le traitement des transactions financières</li>
        <li><strong>Les fournisseurs de services techniques</strong> : hébergeurs, services d'analyse (Sentry pour le suivi des erreurs), Firebase pour les notifications push</li>
        <li><strong>Les autorités judiciaires et réglementaires</strong> : sur réquisition légale ou en cas d'obligation légale</li>
        <li><strong>Les conseillers professionnels</strong> : avocats, auditeurs et assureurs dans le cadre de litiges ou d'audits</li>
    </ul>
    <h3>6.2 Transferts internationaux</h3>
    <p>Certaines données peuvent être transférées vers des serveurs situés en dehors de la Côte d'Ivoire, notamment pour l'utilisation des services de paiement et d'hébergement. Ces transferts sont encadrés par des garanties appropriées conformément à la législation applicable, incluant des clauses contractuelles types et des certifications de sécurité des prestataires.</p>
    <h3>6.3 Interdiction de vente des données</h3>
    <p>BookMi ne vend, ne loue et ne commercialise en aucun cas les données personnelles de ses Utilisateurs à des tiers à des fins publicitaires ou commerciales.</p>

    <h2 id="pc-7">Article 7 – Durée de conservation</h2>
    <p>Les données personnelles sont conservées pendant les durées suivantes :</p>
    <ul>
        <li><strong>Données de compte actif</strong> : pendant toute la durée d'existence du compte, puis trois (3) ans après la dernière activité de l'Utilisateur</li>
        <li><strong>Données de vérification d'identité</strong> : pendant la durée d'existence du compte, puis un (1) an après la clôture du compte</li>
        <li><strong>Données de transactions financières</strong> : dix (10) ans conformément aux obligations comptables et fiscales ivoiriennes et aux dispositions OHADA</li>
        <li><strong>Données de communication (messages)</strong> : cinq (5) ans à compter de l'envoi du message</li>
        <li><strong>Données de connexion et de navigation</strong> : un (1) an à compter de la collecte</li>
        <li><strong>Données d'évaluation et d'avis</strong> : pendant la durée d'existence du compte, sauf suppression volontaire par l'Utilisateur</li>
        <li><strong>Données de litiges et réclamations</strong> : cinq (5) ans après la résolution du litige</li>
    </ul>
    <p>Au-delà de ces durées, les données sont supprimées ou anonymisées de manière irréversible.</p>

    <h2 id="pc-8">Article 8 – Sécurité des données</h2>
    <p>L'Éditeur met en œuvre des mesures techniques et organisationnelles appropriées pour protéger les données personnelles contre tout accès non autorisé, modification, divulgation ou destruction, notamment :</p>
    <ul>
        <li>Chiffrement des mots de passe par hachage sécurisé (bcrypt)</li>
        <li>Authentification à deux facteurs (2FA) disponible pour tous les Utilisateurs</li>
        <li>Utilisation de protocoles HTTPS/TLS pour la transmission des données</li>
        <li>Stockage sécurisé des documents d'identité sur des serveurs dédiés</li>
        <li>Tokens d'authentification via Laravel Sanctum avec expiration configurée</li>
        <li>Clés d'idempotence pour prévenir le traitement en double des transactions</li>
        <li>Vérification des signatures webhook pour les passerelles de paiement</li>
        <li>Surveillance continue des systèmes via Sentry pour la détection d'anomalies</li>
        <li>Contrôle d'accès basé sur les rôles (RBAC) pour les administrateurs</li>
        <li>Journalisation des activités et piste d'audit pour les opérations sensibles</li>
    </ul>
    <p>Malgré ces mesures, l'Éditeur ne peut garantir une sécurité absolue des données transmises via Internet. L'Utilisateur reconnaît et accepte les risques inhérents à la transmission de données en ligne.</p>

    <h2 id="pc-9">Article 9 – Droits des utilisateurs</h2>
    <p>Conformément à la législation applicable, chaque Utilisateur dispose des droits suivants concernant ses données personnelles :</p>
    <ul>
        <li><strong>Droit d'accès</strong> : obtenir la communication des données personnelles le concernant détenues par l'Éditeur</li>
        <li><strong>Droit de rectification</strong> : demander la correction de données inexactes ou incomplètes</li>
        <li><strong>Droit de suppression</strong> : demander l'effacement de ses données personnelles, sous réserve des obligations légales de conservation</li>
        <li><strong>Droit d'opposition</strong> : s'opposer au traitement de ses données à des fins de prospection commerciale</li>
        <li><strong>Droit à la limitation du traitement</strong> : demander la restriction du traitement de ses données dans certaines circonstances</li>
        <li><strong>Droit à la portabilité</strong> : recevoir ses données dans un format structuré, couramment utilisé et lisible par machine</li>
        <li><strong>Droit de retrait du consentement</strong> : retirer son consentement à tout moment, sans affecter la licéité du traitement antérieur</li>
        <li><strong>Droit de réclamation</strong> : introduire une réclamation auprès de l'Autorité de Régulation des Télécommunications/TIC de Côte d'Ivoire (ARTCI)</li>
    </ul>
    <p>Ces droits peuvent être exercés en contactant l'Éditeur à l'adresse <a href="mailto:support@bookmi.click">support@bookmi.click</a>. L'Éditeur répondra à toute demande dans un délai maximum de trente (30) jours.</p>

    <h2 id="pc-10">Article 10 – Cookies et technologies similaires</h2>
    <h3>10.1 Types de cookies utilisés</h3>
    <ul>
        <li><strong>Cookies essentiels</strong> : nécessaires au fonctionnement de la Plateforme (authentification, sécurité, préférences linguistiques)</li>
        <li><strong>Cookies analytiques</strong> : pour mesurer l'audience et analyser l'utilisation de la Plateforme</li>
        <li><strong>Cookies de performance</strong> : pour améliorer la rapidité et la fiabilité de la Plateforme</li>
    </ul>
    <h3>10.2 Gestion des cookies</h3>
    <p>L'Utilisateur peut configurer son navigateur ou son appareil pour refuser les cookies non essentiels. La désactivation de certains cookies peut affecter le fonctionnement de la Plateforme. L'application mobile BookMi n'utilise pas de cookies.</p>

    <h2 id="pc-11">Article 11 – Notifications push</h2>
    <p>La Plateforme utilise Firebase Cloud Messaging (FCM) pour envoyer des notifications push aux Utilisateurs. Les types de notifications incluent : nouveaux messages, mises à jour de réservation, nouvelles évaluations, alertes de disponibilité et diffusions administratives.</p>
    <p>L'Utilisateur peut à tout moment désactiver les notifications push via les paramètres de son appareil ou de son compte sur la Plateforme.</p>

    <div class="legal-highlight">
        <p><strong>Microphone (messages vocaux)</strong> : L'application mobile BookMi demande l'accès au microphone uniquement pour l'envoi de messages vocaux dans la messagerie. Le microphone n'est jamais activé sans action explicite de votre part. BookMi n'enregistre pas l'audio en arrière-plan.</p>
    </div>

    <h2 id="pc-12">Article 12 – Protection des mineurs</h2>
    <p>La Plateforme n'est pas destinée aux mineurs de moins de seize (16) ans. L'Éditeur ne collecte pas sciemment de données personnelles de mineurs de moins de 16 ans. Si l'Éditeur découvre que des données d'un mineur de moins de 16 ans ont été collectées sans le consentement du représentant légal, ces données seront supprimées dans les meilleurs délais.</p>

    <h2 id="pc-13">Article 13 – Modification de la politique</h2>
    <p>L'Éditeur se réserve le droit de modifier la présente Politique de Confidentialité à tout moment. Les modifications prennent effet dès leur publication sur la Plateforme. Les Utilisateurs seront informés des modifications substantielles par notification sur la Plateforme ou par e-mail. La poursuite de l'utilisation de la Plateforme après notification vaut acceptation de la Politique de Confidentialité modifiée.</p>

    <h2 id="pc-14">Article 14 – Violation de données</h2>
    <p>En cas de violation de données personnelles susceptible d'engendrer un risque élevé pour les droits et libertés des Utilisateurs, l'Éditeur s'engage à :</p>
    <ul>
        <li>Notifier l'Autorité de Régulation des Télécommunications/TIC de Côte d'Ivoire (ARTCI) dans un délai de soixante-douze (72) heures</li>
        <li>Informer les Utilisateurs concernés dans les meilleurs délais</li>
        <li>Mettre en œuvre les mesures correctives nécessaires pour remédier à la violation et prévenir sa récurrence</li>
    </ul>

    <h2 id="pc-15">Article 15 – Clause de consentement éclairé</h2>
    <p>En acceptant la présente Politique de Confidentialité, l'Utilisateur déclare et reconnaît :</p>
    <ul>
        <li>Avoir été informé de la nature des données collectées, des finalités du traitement, des destinataires des données et de ses droits</li>
        <li>Consentir librement, spécifiquement et de manière éclairée au traitement de ses données personnelles tel que décrit dans la présente Politique</li>
        <li>Comprendre que ce consentement constitue une manifestation de volonté libre, spécifique, éclairée et univoque au sens de la loi n°2013-450 et du RGPD</li>
        <li>Reconnaître que ce consentement électronique a la même valeur juridique qu'un consentement écrit en vertu de la loi n°2013-546 relative aux transactions électroniques</li>
        <li>Avoir été informé de la possibilité de retirer son consentement à tout moment sans affecter la licéité du traitement fondé sur le consentement donné avant le retrait</li>
        <li>Accepter que les données collectées soient utilisées pour le fonctionnement de la Plateforme, y compris le partage nécessaire avec les prestataires de services de paiement et d'hébergement</li>
    </ul>

    <div style="margin-top:3rem; padding-top:1.5rem; border-top:1px solid rgba(255,255,255,0.06); display:flex; gap:1rem; flex-wrap:wrap;">
        <a href="{{ route('legal.conditions') }}" style="color:#2196F3; font-size:0.875rem; font-weight:600; text-decoration:none;">Conditions d'utilisation →</a>
        <a href="{{ route('home') }}" style="color:rgba(255,255,255,0.4); font-size:0.875rem; font-weight:600; text-decoration:none;">← Retour à l'accueil</a>
    </div>
</div>

@endsection
