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
    background: rgba(26,179,255,0.12);
    border: 1px solid rgba(26,179,255,0.25);
    color: #1AB3FF;
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
</style>
@endsection

@section('content')

<div class="legal-hero">
    <div class="badge">Document légal</div>
    <h1>Conditions d'utilisation</h1>
    <p class="updated">Dernière mise à jour : 5 mars 2026</p>
</div>

<div class="legal-body">

    <div class="legal-toc">
        <p>Sommaire</p>
        <ol>
            <li><a href="#article-1">Définitions</a></li>
            <li><a href="#article-2">Objet</a></li>
            <li><a href="#article-3">Cadre juridique applicable</a></li>
            <li><a href="#article-4">Conditions d'inscription et d'accès</a></li>
            <li><a href="#article-5">Description des services</a></li>
            <li><a href="#article-6">Rôle d'intermédiaire et limitation de responsabilité</a></li>
            <li><a href="#article-7">Conditions financières</a></li>
            <li><a href="#article-8">Obligations des utilisateurs</a></li>
            <li><a href="#article-9">Propriété intellectuelle</a></li>
            <li><a href="#article-10">Modération et sanctions</a></li>
            <li><a href="#article-11">Règlement des litiges</a></li>
            <li><a href="#article-12">Protection des données</a></li>
            <li><a href="#article-13">Force majeure</a></li>
            <li><a href="#article-14">Modification des CGU</a></li>
            <li><a href="#article-15">Résiliation</a></li>
            <li><a href="#article-16">Dispositions diverses</a></li>
            <li><a href="#article-17">Clause de reconnaissance et d'acceptation</a></li>
        </ol>
    </div>

    <div class="legal-highlight">
        <p><strong>IMPORTANT</strong> — Veuillez lire attentivement les présentes conditions avant d'utiliser la plateforme BookMi. En vous inscrivant, en accédant ou en utilisant nos services, vous reconnaissez avoir lu, compris et accepté sans réserve l'intégralité des présentes conditions. Si vous n'acceptez pas ces conditions, vous ne devez pas utiliser la plateforme.</p>
    </div>

    <h2 id="article-1">Article 1 – Définitions</h2>
    <p>Dans les présentes Conditions Générales d'Utilisation (ci-après « CGU »), les termes suivants ont la signification qui leur est attribuée ci-dessous :</p>
    <ul>
        <li><strong>« Plateforme » ou « BookMi »</strong> : désigne le site web accessible à l'adresse <a href="https://bookmi.click">https://bookmi.click</a> ainsi que l'application mobile BookMi, exploités par la société éditrice.</li>
        <li><strong>« Éditeur »</strong> : désigne la société propriétaire et exploitante de la Plateforme BookMi, ses fondateurs, dirigeants, employés, développeurs et prestataires.</li>
        <li><strong>« Utilisateur »</strong> : désigne toute personne physique ou morale accédant à la Plateforme, qu'elle soit inscrite ou non, incluant les Clients, Talents et Managers.</li>
        <li><strong>« Client »</strong> : désigne tout Utilisateur inscrit qui recherche et réserve les services d'un Talent via la Plateforme.</li>
        <li><strong>« Talent »</strong> : désigne tout artiste, prestataire ou professionnel du spectacle inscrit sur la Plateforme offrant ses services de divertissement, de performance artistique ou créatifs.</li>
        <li><strong>« Manager »</strong> : désigne toute personne inscrite agissant en qualité de mandataire ou représentant d'un ou plusieurs Talents pour la gestion de leurs réservations.</li>
        <li><strong>« Services »</strong> : désigne l'ensemble des fonctionnalités de mise en relation, de réservation, de paiement sécurisé, de messagerie et d'évaluation proposées par la Plateforme.</li>
        <li><strong>« Réservation » ou « Booking »</strong> : désigne la demande formalisée par un Client pour retenir les services d'un Talent à une date et un lieu déterminés.</li>
        <li><strong>« Cachet »</strong> : désigne la rémunération convenue entre le Client et le Talent pour la prestation réservée.</li>
        <li><strong>« Escrow » ou « Séquestre »</strong> : désigne le mécanisme de retenue des fonds par la Plateforme jusqu'à la confirmation de la bonne exécution de la prestation.</li>
    </ul>

    <h2 id="article-2">Article 2 – Objet</h2>
    <p>Les présentes CGU ont pour objet de définir les conditions dans lesquelles les Utilisateurs peuvent accéder et utiliser la Plateforme BookMi. La Plateforme agit exclusivement en qualité d'intermédiaire technique de mise en relation entre Clients et Talents. Elle ne constitue en aucun cas une partie au contrat de prestation conclu entre le Client et le Talent.</p>
    <p>BookMi n'est ni un employeur, ni un agent artistique au sens de la législation applicable. La Plateforme fournit uniquement un espace technologique facilitant la découverte, la réservation, le paiement et l'évaluation de prestations artistiques et créatives.</p>

    <h2 id="article-3">Article 3 – Cadre juridique applicable</h2>
    <h3>3.1 Législations de référence</h3>
    <p>Les présentes CGU sont régies par et interprétées conformément aux lois et règlements suivants :</p>
    <ul>
        <li>L'Acte Uniforme OHADA relatif au Droit Commercial Général (AUDCG)</li>
        <li>L'Acte Uniforme OHADA relatif au Droit des Sociétés Commerciales et du Groupement d'Intérêt Économique (AUSCGIE)</li>
        <li>La loi n°2013-450 du 19 juin 2013 relative à la protection des données à caractère personnel en Côte d'Ivoire</li>
        <li>La loi n°2013-451 du 19 juin 2013 relative à la lutte contre la cybercriminalité en Côte d'Ivoire</li>
        <li>La loi n°2013-546 du 30 juillet 2013 relative aux transactions électroniques en Côte d'Ivoire</li>
        <li>L'Ordonnance n°2012-293 du 21 mars 2012 relative aux télécommunications et aux technologies de l'information</li>
        <li>Le Règlement Général sur la Protection des Données (RGPD – UE 2016/679) pour les Utilisateurs situés dans l'Union Européenne</li>
        <li>La Directive de la CEDEAO C/DIR/1/08/11 sur la protection des données personnelles</li>
        <li>La Convention de l'Union Africaine sur la cybersécurité et la protection des données à caractère personnel (Convention de Malabo, 2014)</li>
        <li>Le Code Civil ivoirien, notamment les dispositions relatives au droit des obligations et des contrats</li>
        <li>Le Code de propriété intellectuelle applicable en Côte d'Ivoire (Accord de Bangui révisé)</li>
    </ul>
    <h3>3.2 Compétence juridictionnelle</h3>
    <p>Tout différend relatif à l'interprétation ou à l'exécution des présentes CGU sera soumis à la compétence exclusive des juridictions d'Abidjan, Côte d'Ivoire, sauf disposition légale impérative contraire. Avant toute saisine judiciaire, les parties s'engagent à rechercher une solution amiable dans un délai de trente (30) jours à compter de la notification du différend.</p>

    <h2 id="article-4">Article 4 – Conditions d'inscription et d'accès</h2>
    <h3>4.1 Éligibilité</h3>
    <p>L'inscription sur BookMi est ouverte à toute personne physique âgée d'au moins dix-huit (18) ans jouissant de sa pleine capacité juridique, ou à toute personne morale dûment constituée. Les mineurs de plus de seize (16) ans peuvent s'inscrire avec l'autorisation écrite de leur représentant légal.</p>
    <h3>4.2 Processus d'inscription</h3>
    <p>L'Utilisateur doit fournir des informations exactes, complètes et à jour lors de son inscription. Il s'engage à :</p>
    <ul>
        <li>Fournir son nom complet, adresse e-mail valide et numéro de téléphone</li>
        <li>Créer un mot de passe sécurisé et le maintenir confidentiel</li>
        <li>Compléter la vérification d'identité si requis (pièce d'identité, selfie de vérification)</li>
        <li>Accepter expressément les présentes CGU et la Politique de Confidentialité</li>
    </ul>
    <h3>4.3 Vérification d'identité</h3>
    <p>BookMi se réserve le droit d'exiger la vérification de l'identité de tout Utilisateur par la soumission d'une pièce d'identité officielle et d'un selfie de vérification. Cette vérification est obligatoire pour les Talents et Managers souhaitant recevoir des paiements via la Plateforme. L'Éditeur se réserve le droit de suspendre ou supprimer tout compte dont l'identité ne peut être vérifiée.</p>
    <h3>4.4 Sécurité du compte</h3>
    <p>L'Utilisateur est seul responsable de la confidentialité de ses identifiants de connexion. Toute activité effectuée depuis son compte est réputée avoir été réalisée par lui. L'Utilisateur s'engage à notifier immédiatement l'Éditeur de tout accès non autorisé à son compte.</p>

    <h2 id="article-5">Article 5 – Description des services</h2>
    <h3>5.1 Services de mise en relation</h3>
    <p>BookMi propose un annuaire de Talents vérifiés avec profils détaillés (portfolios, évaluations, catégories, disponibilités) et un système de recherche avancée par catégorie, budget, localisation et niveau de talent.</p>
    <h3>5.2 Services de réservation</h3>
    <p>Le système de réservation intègre plusieurs offres de services (Essentiel, Standard, Premium), un calendrier de disponibilités, la gestion des reports et annulations, et un suivi en temps réel de l'état des réservations.</p>
    <h3>5.3 Services de paiement sécurisé</h3>
    <p>Les paiements sont traités via des passerelles de paiement certifiées (Paystack, FedaPay) et incluent les moyens de paiement suivants : Mobile Money (Orange Money, Wave, MTN MoMo, Moov Money), paiement par carte bancaire et virement bancaire. Un système de séquestre (escrow) retient les fonds jusqu'à la confirmation de la bonne exécution de la prestation.</p>
    <h3>5.4 Services de communication</h3>
    <p>La Plateforme offre une messagerie interne sécurisée entre Utilisateurs, incluant l'envoi de messages textes, vocaux, photos et vidéos. Toutes les communications doivent transiter par la Plateforme.</p>
    <h3>5.5 Services d'évaluation</h3>
    <p>Un système d'évaluation bilatérale permet aux Clients de noter les Talents et réciproquement, selon plusieurs critères : ponctualité, qualité, professionnalisme et respect du contrat.</p>

    <h2 id="article-6">Article 6 – Rôle d'intermédiaire et limitation de responsabilité</h2>
    <h3>6.1 Nature de l'intermédiation</h3>
    <p>BookMi agit exclusivement en qualité d'intermédiaire technique et ne devient en aucun cas partie au contrat de prestation entre le Client et le Talent. La Plateforme ne garantit pas la qualité, la sécurité, la légalité ou la disponibilité des prestations proposées par les Talents.</p>
    <h3>6.2 Exclusion de responsabilité</h3>
    <p>Dans les limites autorisées par la loi applicable, l'Éditeur, ses fondateurs, dirigeants, développeurs, employés et prestataires déclinent toute responsabilité pour :</p>
    <ul>
        <li>Tout préjudice direct, indirect, matériel, immatériel, corporel ou moral résultant de l'utilisation ou de l'impossibilité d'utiliser la Plateforme</li>
        <li>Tout différend entre Utilisateurs relatif à l'exécution, la qualité ou les conditions d'une prestation</li>
        <li>Tout acte illégal, frauduleux, diffamatoire, attentatoire à la vie privée ou contraire aux bonnes mœurs commis par un Utilisateur</li>
        <li>Toute perte financière, perte de données, perte d'opportunité ou perte de profit subie par un Utilisateur</li>
        <li>Les actes ou omissions de prestataires tiers (passerelles de paiement, hébergeurs, fournisseurs de télécommunications)</li>
        <li>Les interruptions de service, erreurs techniques, bugs ou failles de sécurité</li>
        <li>Le contenu publié par les Utilisateurs (profils, portfolio, évaluations, messages)</li>
        <li>Tout dommage résultant d'un cas de force majeure tel que défini par la législation applicable</li>
    </ul>
    <h3>6.3 Plafond de responsabilité</h3>
    <p>En tout état de cause, la responsabilité totale de l'Éditeur au titre des présentes CGU ne saurait excéder le montant total des commissions effectivement perçues par la Plateforme au titre de la transaction litigieuse, ou la somme de cinquante mille (50 000) francs CFA, le montant le plus élevé étant retenu.</p>
    <h3>6.4 Indemnisation</h3>
    <p>L'Utilisateur s'engage à indemniser et à dégager de toute responsabilité l'Éditeur, ses fondateurs, dirigeants, développeurs, employés, agents et prestataires contre toute réclamation, demande, action, perte, dommage, coût ou dépense (y compris les honoraires d'avocats) découlant de ou liés à :</p>
    <ul>
        <li>L'utilisation de la Plateforme par l'Utilisateur</li>
        <li>La violation des présentes CGU par l'Utilisateur</li>
        <li>La violation de tout droit de tiers par l'Utilisateur</li>
        <li>Tout contenu publié ou transmis par l'Utilisateur via la Plateforme</li>
        <li>Tout litige entre l'Utilisateur et un autre Utilisateur</li>
    </ul>

    <h2 id="article-7">Article 7 – Conditions financières</h2>
    <h3>7.1 Commission de la Plateforme</h3>
    <p>BookMi prélève une commission de quinze pour cent (15%) sur le montant total de chaque réservation, à la charge du Client. Le Talent reçoit l'intégralité du cachet convenu. Tous les montants sont exprimés en francs CFA (XOF).</p>
    <h3>7.2 Mécanisme de séquestre (Escrow)</h3>
    <p>Les fonds versés par le Client sont placés en séquestre par la Plateforme jusqu'à la confirmation de la bonne exécution de la prestation. La libération des fonds intervient soit par confirmation manuelle du Client, soit automatiquement quarante-huit (48) heures après l'achèvement de la prestation en l'absence de contestation.</p>
    <h3>7.3 Politique d'annulation et de remboursement</h3>
    <p>Les annulations sont soumises à la politique suivante :</p>
    <ul>
        <li>Annulation à plus de quatorze (14) jours avant la date de la prestation : remboursement intégral</li>
        <li>Annulation entre sept (7) et quatorze (14) jours : remboursement de cinquante pour cent (50%)</li>
        <li>Annulation entre deux (2) et sept (7) jours : médiation obligatoire, aucun remboursement automatique</li>
        <li>Annulation à moins de deux (2) jours : aucun remboursement, recours à la procédure de règlement des litiges</li>
    </ul>
    <h3>7.4 Versements aux Talents</h3>
    <p>Les versements aux Talents sont effectués via Mobile Money (Orange Money, Wave, MTN MoMo, Moov Money) ou virement bancaire, selon le moyen de paiement configuré et vérifié par le Talent.</p>

    <h2 id="article-8">Article 8 – Obligations des utilisateurs</h2>
    <h3>8.1 Obligations générales</h3>
    <p>Tout Utilisateur s'engage à :</p>
    <ul>
        <li>Utiliser la Plateforme conformément aux présentes CGU et à la législation en vigueur</li>
        <li>Fournir des informations véridiques, exactes et complètes</li>
        <li>Ne pas usurper l'identité d'un tiers</li>
        <li>Ne pas utiliser la Plateforme à des fins illégales, frauduleuses ou nuisibles</li>
        <li>Ne pas tenter de contourner les mesures de sécurité de la Plateforme</li>
        <li>Respecter les droits de propriété intellectuelle de tiers</li>
        <li>Ne pas publier de contenus diffamatoires, injurieux, discriminatoires, à caractère sexuel ou violent</li>
        <li>Ne pas solliciter des transactions en dehors de la Plateforme pour éviter les commissions</li>
    </ul>
    <h3>8.2 Obligations spécifiques du Client</h3>
    <p>Le Client s'engage à :</p>
    <ul>
        <li>Formuler des demandes de réservation précises et de bonne foi</li>
        <li>Procéder au paiement dans les délais impartis</li>
        <li>Confirmer la bonne exécution de la prestation ou signaler tout différend dans le délai de quarante-huit (48) heures</li>
        <li>Respecter les conditions du forfait de services choisi</li>
    </ul>
    <h3>8.3 Obligations spécifiques du Talent</h3>
    <p>Le Talent s'engage à :</p>
    <ul>
        <li>Fournir des informations exactes sur ses compétences, disponibilités et tarifs</li>
        <li>Honorer les réservations acceptées avec professionnalisme et ponctualité</li>
        <li>Se conformer aux exigences légales applicables à son activité (licences, assurances, autorisations)</li>
        <li>Déclarer ses revenus conformément à la législation fiscale applicable</li>
    </ul>
    <h3>8.4 Obligations spécifiques du Manager</h3>
    <p>Le Manager s'engage à :</p>
    <ul>
        <li>Agir exclusivement dans l'intérêt des Talents qu'il représente</li>
        <li>Disposer d'un mandat valide pour agir au nom des Talents</li>
        <li>Ne pas accepter de réservations sans l'accord des Talents concernés</li>
    </ul>

    <h2 id="article-9">Article 9 – Propriété intellectuelle</h2>
    <p>L'ensemble des éléments constituant la Plateforme (logo, marques, bases de données, logiciels, interfaces, design, textes, images, vidéos et tout autre contenu) sont la propriété exclusive de l'Éditeur ou font l'objet d'une licence accordée à ce dernier. Toute reproduction, modification, distribution ou exploitation non autorisée est strictement interdite et constitue une contrefaçon sanctionnée par l'Accord de Bangui révisé et les lois applicables.</p>
    <p>Les Utilisateurs conservent la propriété intellectuelle de leurs contenus publiés sur la Plateforme (photos, vidéos de portfolio, textes). Toutefois, en publiant ces contenus, ils concèdent à l'Éditeur une licence non exclusive, mondiale, gratuite et cessible d'utilisation, de reproduction et de diffusion à des fins de promotion et de fonctionnement de la Plateforme.</p>

    <h2 id="article-10">Article 10 – Modération et sanctions</h2>
    <h3>10.1 Modération des contenus</h3>
    <p>L'Éditeur se réserve le droit, sans obligation, de surveiller, modérer et supprimer tout contenu publié sur la Plateforme qui contreviendrait aux présentes CGU, à la législation en vigueur ou aux droits de tiers.</p>
    <h3>10.2 Sanctions</h3>
    <p>En cas de violation des présentes CGU, l'Éditeur peut, à sa seule discrétion et sans préavis :</p>
    <ul>
        <li>Adresser un avertissement à l'Utilisateur</li>
        <li>Suspendre temporairement le compte de l'Utilisateur</li>
        <li>Résilier définitivement le compte de l'Utilisateur</li>
        <li>Bloquer l'accès à la Plateforme</li>
        <li>Engager des poursuites judiciaires si nécessaire</li>
    </ul>
    <p>Ces mesures sont prises sans préjudice de tout dommage et intérêt que l'Éditeur pourrait réclamer.</p>

    <h2 id="article-11">Article 11 – Règlement des litiges</h2>
    <h3>11.1 Médiation interne</h3>
    <p>En cas de différend entre Utilisateurs, la Plateforme offre un service de médiation interne conduit par l'équipe de modération. Les Utilisateurs s'engagent à recourir en priorité à ce service avant toute action judiciaire.</p>
    <h3>11.2 Procédure de réclamation</h3>
    <p>Toute réclamation doit être adressée via le système de signalement intégré à la Plateforme dans un délai de sept (7) jours suivant l'événement litigieux. L'Éditeur s'engage à traiter les réclamations dans un délai raisonnable.</p>
    <h3>11.3 Arbitrage</h3>
    <p>En cas d'échec de la médiation, les parties peuvent recourir à l'arbitrage conformément aux règles de la Cour Commune de Justice et d'Arbitrage (CCJA) de l'OHADA.</p>

    <h2 id="article-12">Article 12 – Protection des données</h2>
    <p>Le traitement des données personnelles des Utilisateurs est régi par la <a href="{{ route('legal.privacy') }}">Politique de Confidentialité</a> de BookMi, qui constitue un document complémentaire aux présentes CGU. En acceptant les présentes CGU, l'Utilisateur reconnaît avoir pris connaissance de la Politique de Confidentialité et y consent expressément.</p>

    <h2 id="article-13">Article 13 – Force majeure</h2>
    <p>L'Éditeur ne saurait être tenu responsable de l'inexécution totale ou partielle de ses obligations au titre des présentes CGU si cette inexécution résulte d'un événement de force majeure, incluant notamment : catastrophes naturelles, incendies, inondations, pandémies, guerres, actes de terrorisme, grèves, pannes des réseaux de télécommunications, défaillance des prestataires tiers, décisions gouvernementales ou toute autre circonstance échappant au contrôle raisonnable de l'Éditeur.</p>

    <h2 id="article-14">Article 14 – Modification des CGU</h2>
    <p>L'Éditeur se réserve le droit de modifier les présentes CGU à tout moment. Les modifications prennent effet dès leur publication sur la Plateforme. Les Utilisateurs seront informés des modifications substantielles par notification sur la Plateforme ou par e-mail. La poursuite de l'utilisation de la Plateforme après notification vaut acceptation des CGU modifiées.</p>

    <h2 id="article-15">Article 15 – Résiliation</h2>
    <h3>15.1 Résiliation par l'Utilisateur</h3>
    <p>L'Utilisateur peut résilier son compte à tout moment en contactant le support de la Plateforme à <a href="mailto:support@bookmi.click">support@bookmi.click</a>. Les réservations en cours doivent être honorées ou annulées conformément à la politique d'annulation avant la résiliation effective.</p>
    <h3>15.2 Résiliation par l'Éditeur</h3>
    <p>L'Éditeur se réserve le droit de résilier tout compte en cas de violation des CGU, de fraude, d'activité suspecte ou pour tout motif légitime, sans préavis ni indemnité.</p>
    <h3>15.3 Effets de la résiliation</h3>
    <p>La résiliation entraîne la désactivation du compte, la suppression du profil public et la perte d'accès aux Services. Les données de l'Utilisateur seront conservées conformément à la Politique de Confidentialité et aux obligations légales de conservation.</p>

    <h2 id="article-16">Article 16 – Dispositions diverses</h2>
    <h3>16.1 Intégralité de l'accord</h3>
    <p>Les présentes CGU, complétées par la Politique de Confidentialité et toute autre politique publiée sur la Plateforme, constituent l'intégralité de l'accord entre l'Utilisateur et l'Éditeur relatif à l'utilisation de la Plateforme.</p>
    <h3>16.2 Séparabilité</h3>
    <p>Si une disposition des présentes CGU est déclarée nulle ou inapplicable par une juridiction compétente, les autres dispositions demeureront en pleine vigueur et effet.</p>
    <h3>16.3 Renonciation</h3>
    <p>Le fait pour l'Éditeur de ne pas exercer un droit prévu par les présentes CGU ne constitue pas une renonciation à ce droit.</p>
    <h3>16.4 Cession</h3>
    <p>L'Éditeur peut céder les présentes CGU et les droits et obligations qui en découlent à tout tiers, notamment en cas de fusion, acquisition ou vente d'actifs. L'Utilisateur ne peut céder ses droits ou obligations sans l'accord préalable écrit de l'Éditeur.</p>
    <h3>16.5 Langue</h3>
    <p>Les présentes CGU sont rédigées en français. En cas de traduction, seule la version française fera foi.</p>

    <h2 id="article-17">Article 17 – Clause de reconnaissance et d'acceptation</h2>
    <p>En cochant la case « J'accepte les termes et conditions d'utilisation » et/ou en cliquant sur le bouton d'inscription, l'Utilisateur reconnaît et déclare :</p>
    <ul>
        <li>Avoir lu et compris l'intégralité des présentes Conditions Générales d'Utilisation</li>
        <li>Accepter sans réserve l'ensemble des dispositions des présentes CGU</li>
        <li>Comprendre que cette acceptation constitue un accord contractuel ayant force obligatoire entre les parties, conformément aux dispositions de la loi n°2013-546 du 30 juillet 2013 relative aux transactions électroniques</li>
        <li>Reconnaître que cette acceptation électronique a la même valeur juridique qu'une signature manuscrite en vertu de la législation ivoirienne sur les transactions électroniques</li>
        <li>Comprendre que les clauses limitatives et exonératoires de responsabilité contenues dans les présentes CGU sont des conditions essentielles sans lesquelles l'Éditeur n'aurait pas mis la Plateforme à disposition</li>
        <li>Reconnaître avoir été informé de la possibilité de consulter un conseiller juridique avant d'accepter les présentes CGU</li>
    </ul>

    <div style="margin-top:3rem; padding-top:1.5rem; border-top:1px solid rgba(255,255,255,0.06); display:flex; gap:1rem; flex-wrap:wrap;">
        <a href="{{ route('legal.privacy') }}" style="color:#2196F3; font-size:0.875rem; font-weight:600; text-decoration:none;">Politique de confidentialité →</a>
        <a href="{{ route('home') }}" style="color:rgba(255,255,255,0.4); font-size:0.875rem; font-weight:600; text-decoration:none;">← Retour à l'accueil</a>
    </div>
</div>

@endsection
