@extends('layouts.public')

@section('title', "Conditions d'utilisation — BookMi")
@section('meta_description', "Consultez les conditions générales d'utilisation de la plateforme BookMi, la solution de réservation de talents en Côte d'Ivoire.")

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
    background: rgba(255,107,53,0.12);
    border: 1px solid rgba(255,107,53,0.25);
    color: #FF6B35;
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
</style>
@endsection

@section('content')

<div class="legal-hero">
    <div class="badge">Document légal</div>
    <h1>Conditions d'utilisation</h1>
    <p class="updated">Dernière mise à jour : {{ date('d') }} {{ ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'][date('n')-1] }} {{ date('Y') }}</p>
</div>

<div class="legal-body">

    <div class="legal-toc">
        <p>Sommaire</p>
        <ol>
            <li><a href="#article-1">Présentation de BookMi</a></li>
            <li><a href="#article-2">Acceptation des conditions</a></li>
            <li><a href="#article-3">Inscription et compte utilisateur</a></li>
            <li><a href="#article-4">Services proposés</a></li>
            <li><a href="#article-5">Obligations des utilisateurs</a></li>
            <li><a href="#article-6">Réservations et paiements</a></li>
            <li><a href="#article-7">Commission et frais</a></li>
            <li><a href="#article-8">Annulation et remboursements</a></li>
            <li><a href="#article-9">Responsabilités</a></li>
            <li><a href="#article-10">Propriété intellectuelle</a></li>
            <li><a href="#article-11">Résiliation</a></li>
            <li><a href="#article-12">Contact</a></li>
        </ol>
    </div>

    <div class="legal-highlight">
        <p>Veuillez lire attentivement ces conditions avant d'utiliser la plateforme BookMi. En créant un compte ou en utilisant nos services, vous reconnaissez avoir lu et accepté l'intégralité des présentes conditions.</p>
    </div>

    <h2 id="article-1">1. Présentation de BookMi</h2>
    <p><strong>BookMi</strong> est une plateforme numérique de mise en relation entre des organisateurs d'événements (« Clients ») et des artistes, prestataires événementiels et talents créatifs (« Talents »), opérant principalement en Côte d'Ivoire.</p>
    <p>La plateforme est éditée par <strong>BookMi SAS</strong>, société immatriculée en Côte d'Ivoire, dont le siège social est situé à Abidjan. Elle est accessible à l'adresse <a href="https://bookmi.click">bookmi.click</a> et via l'application mobile BookMi disponible sur iOS et Android.</p>

    <h2 id="article-2">2. Acceptation des conditions</h2>
    <p>L'accès et l'utilisation de la plateforme BookMi sont soumis à l'acceptation sans réserve des présentes conditions générales d'utilisation (CGU). En cochant la case « J'accepte les conditions d'utilisation » lors de votre inscription, ou en continuant à utiliser la plateforme après toute modification des présentes, vous acceptez être lié(e) par ces conditions.</p>
    <p>Si vous n'acceptez pas tout ou partie de ces conditions, vous devez cesser d'utiliser la plateforme.</p>
    <p>BookMi se réserve le droit de modifier ces conditions à tout moment. Les utilisateurs seront informés de toute modification substantielle par email ou notification in-app. L'utilisation continue de la plateforme après notification vaut acceptation des nouvelles conditions.</p>

    <h2 id="article-3">3. Inscription et compte utilisateur</h2>
    <p>Pour accéder aux fonctionnalités complètes de BookMi, vous devez créer un compte personnel. L'inscription est gratuite et ouverte à toute personne physique majeure ou morale.</p>
    <ul>
        <li>Vous vous engagez à fournir des informations exactes, complètes et à jour lors de votre inscription et à les maintenir ainsi.</li>
        <li>Chaque personne ou entité ne peut disposer que d'un seul compte actif par catégorie (Client ou Talent).</li>
        <li>Vous êtes seul(e) responsable de la confidentialité de vos identifiants de connexion.</li>
        <li>Tout accès à votre compte via vos identifiants est présumé effectué par vous.</li>
        <li>Vous devez nous notifier immédiatement en cas d'utilisation non autorisée de votre compte à l'adresse <a href="mailto:contact@bookmi.ci">contact@bookmi.ci</a>.</li>
    </ul>
    <p>Pour les Talents, un processus de vérification d'identité est requis avant de pouvoir recevoir des réservations payantes. Ce processus peut inclure la fourniture de documents officiels d'identité.</p>

    <h2 id="article-4">4. Services proposés</h2>
    <p>BookMi propose les services suivants :</p>
    <ul>
        <li><strong>Pour les Clients :</strong> recherche et découverte de talents, demande de réservation, paiement sécurisé via Mobile Money (Orange Money, MTN MoMo, Wave), messagerie avec les talents réservés, téléchargement de reçus et contrats.</li>
        <li><strong>Pour les Talents :</strong> création et gestion d'un profil public, gestion des demandes de réservation, réception des paiements (moins la commission BookMi), messagerie avec les clients, statistiques de performance, gestion du calendrier de disponibilité.</li>
    </ul>
    <p>BookMi agit en tant qu'intermédiaire de mise en relation et de paiement. BookMi n'est pas partie aux contrats conclus entre Clients et Talents, et ne garantit pas l'exécution des prestations.</p>

    <h2 id="article-5">5. Obligations des utilisateurs</h2>
    <p>En utilisant BookMi, vous vous engagez à :</p>
    <ul>
        <li>Respecter la législation ivoirienne et le droit international applicable.</li>
        <li>Ne pas usurper l'identité d'une autre personne ou entité.</li>
        <li>Ne pas utiliser la plateforme à des fins frauduleuses, illicites ou contraires aux bonnes mœurs.</li>
        <li>Ne pas tenter de contourner les mécanismes de paiement sécurisé de BookMi en réalisant des transactions hors plateforme.</li>
        <li>Respecter les autres utilisateurs et s'abstenir de tout comportement harcelant, discriminatoire ou abusif.</li>
        <li>Ne pas publier de contenu faux, trompeur ou portant atteinte aux droits de tiers.</li>
        <li>Ne pas tenter de perturber, compromettre ou accéder de manière non autorisée aux systèmes informatiques de BookMi.</li>
    </ul>
    <p>Le non-respect de ces obligations peut entraîner la suspension ou la résiliation immédiate de votre compte, sans préjudice de toute action légale.</p>

    <h2 id="article-6">6. Réservations et paiements</h2>
    <p>Le processus de réservation sur BookMi se déroule comme suit :</p>
    <ul>
        <li>Le Client soumet une demande de réservation en choisissant un package proposé par le Talent.</li>
        <li>Le Talent dispose d'un délai pour accepter ou refuser la demande.</li>
        <li>En cas d'acceptation, le Client procède au paiement sécurisé via Mobile Money.</li>
        <li>Les fonds sont conservés par BookMi en fiducie (escrow) jusqu'à la confirmation de la bonne exécution de la prestation.</li>
        <li>Après confirmation, BookMi reverse le montant au Talent, déduction faite de la commission.</li>
    </ul>
    <p>Les paiements sont traités de manière sécurisée par nos partenaires de paiement agréés. BookMi ne stocke pas les informations sensibles de paiement de ses utilisateurs.</p>

    <h2 id="article-7">7. Commission et frais</h2>
    <p>BookMi perçoit une commission sur chaque transaction réalisée via la plateforme. Le taux de commission applicable est indiqué clairement lors de la création du devis et avant la validation du paiement par le Client.</p>
    <p>Pour les Talents, le montant net reversé est le prix du package moins la commission BookMi. Aucun frais d'inscription ni abonnement mensuel n'est facturé aux Talents pendant la phase actuelle de la plateforme.</p>

    <h2 id="article-8">8. Annulation et remboursements</h2>
    <p>Les conditions d'annulation et de remboursement sont les suivantes :</p>
    <ul>
        <li><strong>Annulation par le Client :</strong> selon les conditions fixées dans le contrat de prestation. BookMi peut retenir des frais d'annulation selon le délai de préavis.</li>
        <li><strong>Annulation par le Talent :</strong> en cas d'annulation par le Talent après acceptation et paiement, le Client est intégralement remboursé dans un délai de 5 à 10 jours ouvrés.</li>
        <li><strong>Litiges :</strong> BookMi dispose d'un mécanisme de résolution des litiges. En cas de différend, les parties doivent contacter le support BookMi qui arbitrera la situation.</li>
    </ul>
    <p>Les remboursements sont effectués via le même canal de paiement que la transaction initiale.</p>

    <h2 id="article-9">9. Responsabilités</h2>
    <p>BookMi s'engage à mettre en œuvre tous les moyens raisonnables pour assurer la disponibilité et le bon fonctionnement de la plateforme. Cependant, BookMi ne saurait être tenu responsable :</p>
    <ul>
        <li>Du contenu publié par les utilisateurs sur leurs profils.</li>
        <li>De l'exécution ou la non-exécution des prestations entre Clients et Talents.</li>
        <li>Des interruptions de service dues à des cas de force majeure, défaillances d'opérateurs tiers ou opérations de maintenance.</li>
        <li>De tout dommage indirect, consécutif ou perte de données résultant de l'utilisation de la plateforme.</li>
    </ul>
    <p>La responsabilité de BookMi, lorsqu'elle est engagée, est limitée au montant total des commissions perçues au cours des 12 derniers mois précédant le fait générateur.</p>

    <h2 id="article-10">10. Propriété intellectuelle</h2>
    <p>La marque BookMi, le logo, le design de la plateforme, le code source, et tous les contenus créés par BookMi sont la propriété exclusive de BookMi SAS et sont protégés par le droit ivoirien et international de la propriété intellectuelle.</p>
    <p>Les Talents conservent la propriété de leurs contenus (photos, vidéos, textes) publiés sur la plateforme, mais accordent à BookMi une licence non exclusive, mondiale et gratuite pour les afficher, reproduire et promouvoir dans le cadre de ses services.</p>
    <p>Toute reproduction, représentation ou exploitation non autorisée des éléments de la plateforme est strictement interdite et passible de poursuites.</p>

    <h2 id="article-11">11. Résiliation</h2>
    <p>Vous pouvez clôturer votre compte à tout moment en contactant notre support à <a href="mailto:contact@bookmi.ci">contact@bookmi.ci</a>. La résiliation prend effet après traitement de toutes les réservations en cours.</p>
    <p>BookMi se réserve le droit de suspendre ou de clôturer tout compte en cas de violation des présentes conditions, de comportement frauduleux, ou de non-respect répété des règles de la communauté, sans préavis et sans indemnité.</p>

    <h2 id="article-12">12. Contact et droit applicable</h2>
    <p>Pour toute question relative aux présentes conditions ou à l'utilisation de la plateforme, vous pouvez nous contacter :</p>
    <ul>
        <li>Par email : <a href="mailto:contact@bookmi.ci">contact@bookmi.ci</a></li>
        <li>Via la plateforme : <a href="{{ route('home') }}">bookmi.click</a></li>
    </ul>
    <p>Les présentes conditions sont régies par le droit ivoirien. Tout litige relatif à leur interprétation ou leur exécution sera soumis à la compétence exclusive des tribunaux d'Abidjan, Côte d'Ivoire.</p>

    <div style="margin-top:3rem; padding-top:1.5rem; border-top:1px solid rgba(255,255,255,0.06); display:flex; gap:1rem; flex-wrap:wrap;">
        <a href="{{ route('legal.privacy') }}" style="color:#2196F3; font-size:0.875rem; font-weight:600; text-decoration:none;">Politique de confidentialité →</a>
        <a href="{{ route('home') }}" style="color:rgba(255,255,255,0.4); font-size:0.875rem; font-weight:600; text-decoration:none;">← Retour à l'accueil</a>
    </div>
</div>

@endsection
