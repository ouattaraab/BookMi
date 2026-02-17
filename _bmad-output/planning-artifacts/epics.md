---
stepsCompleted: [1, 2, 3, 4]
lastStep: 4
status: 'complete'
completedAt: '2026-02-17'
inputDocuments:
  - '_bmad-output/planning-artifacts/prd.md'
  - '_bmad-output/planning-artifacts/architecture.md'
  - '_bmad-output/planning-artifacts/ux-design-specification.md'
workflowType: 'epics'
project_name: 'BookMi_v2'
user_name: 'Aboubakarouattara'
date: '2026-02-17'
---

# BookMi_v2 - Epic Breakdown

## Overview

This document provides the complete epic and story breakdown for BookMi_v2, decomposing the requirements from the PRD, UX Design if it exists, and Architecture requirements into implementable stories.

## Requirements Inventory

### Functional Requirements

- FR1: Un visiteur peut créer un compte client (personne physique ou morale) avec email et numéro de téléphone
- FR2: Un visiteur peut créer un compte talent (artiste solo ou groupe) avec catégorie et sous-catégorie
- FR3: Un utilisateur peut soumettre une pièce d'identité (CNI/passeport) pour vérification
- FR4: Un administrateur peut examiner et valider ou rejeter une demande de vérification d'identité
- FR5: Un utilisateur vérifié reçoit un badge "Vérifié" visible sur son profil public
- FR6: Un talent peut créer et gérer son profil riche (bio, photos, vidéos, liens réseaux sociaux)
- FR7: Un talent peut assigner un manager à son compte avec accès opérationnel sans visibilité financière
- FR8: Un manager peut gérer les comptes de plusieurs talents depuis une interface unifiée
- FR9: Un utilisateur peut se connecter via email/mot de passe et recevoir un token d'authentification
- FR10: Un utilisateur peut réinitialiser son mot de passe via email
- FR11: Un client peut parcourir l'annuaire des talents vérifiés
- FR12: Un client peut filtrer les talents par catégorie, sous-catégorie, budget, localisation et note
- FR13: Un client peut rechercher des talents par géolocalisation (proximité)
- FR14: Un client peut consulter le profil public d'un talent (portfolio, avis, score de fiabilité, packages, disponibilités)
- FR15: Un client peut voir des suggestions de talents similaires sur un profil
- FR16: Un client peut suivre des talents en favoris
- FR17: Un talent possède une URL unique partageable (lien profil public)
- FR18: Un client peut envoyer une demande de réservation à un talent (date, lieu, message, package choisi)
- FR19: Un talent (ou son manager) peut accepter ou refuser une demande de réservation
- FR20: Un client peut consulter un devis détaillé transparent (cachet artiste + frais BookMi 15%)
- FR21: Le système génère automatiquement un contrat électronique conforme avec identification des parties, description de la prestation, prix et conditions
- FR22: Un client peut télécharger le contrat en format PDF
- FR23: Un talent peut créer et gérer des packages de prestation (Essentiel, Standard, Premium)
- FR24: Un talent peut proposer des micro-prestations (vidéo personnalisée, dédicace audio)
- FR25: Un client peut effectuer une réservation express (processus simplifié)
- FR26: Le système applique automatiquement la politique d'annulation graduée (J-14 remboursement intégral, J-7 50%, J-2 médiation uniquement)
- FR27: Un client peut demander l'annulation d'une réservation confirmée
- FR28: Un client ou un talent peut demander un report de réservation via médiation
- FR29: Un client peut payer via Mobile Money (Orange Money, Wave, MTN MoMo, Moov Money)
- FR30: Un client peut payer via carte bancaire ou virement
- FR31: Le système place le paiement en séquestre (escrow) jusqu'à la confirmation de la prestation
- FR32: Le système verse automatiquement le cachet intégral (100%) au talent dans les 24h suivant la confirmation du client
- FR33: Le système confirme automatiquement la prestation si le client ne se prononce pas sous 48h
- FR34: Le système effectue un remboursement au client en cas de litige résolu en sa faveur
- FR35: Un talent peut consulter son dashboard financier (revenus, historique des versements, comparaisons mensuelles)
- FR36: Un talent peut choisir son moyen de versement préféré (Orange Money, Wave, MTN, compte bancaire)
- FR37: Le système bascule automatiquement entre passerelles de paiement (Paystack/CinetPay) en cas d'indisponibilité
- FR38: Un administrateur comptable peut exporter les rapports financiers
- FR39: Un client et un talent peuvent échanger des messages via la messagerie interne de type WhatsApp (texte, emojis, photos, vocaux)
- FR40: Le système détecte les tentatives d'échange de coordonnées personnelles dans les messages et envoie un avertissement éducatif
- FR41: Un talent peut configurer des réponses automatiques pour la messagerie
- FR42: Le système envoie des notifications push pour les événements critiques (réservation, paiement, message, rappel)
- FR43: Le système envoie des rappels automatiques à J-7 et J-2 avant la prestation
- FR44: Un administrateur peut accéder aux messages uniquement dans le cadre d'un litige formel avec piste d'audit
- FR45: Le système suit le statut de la prestation le jour J en temps réel (en préparation, en route, arrivé, en cours, terminé)
- FR46: Un talent peut effectuer son check-in le jour J avec géolocalisation
- FR47: Le système alerte en cas de check-in manquant ou de retard
- FR48: Un client peut évaluer un talent après la prestation (ponctualité, qualité, professionnalisme, note globale, commentaire)
- FR49: Un talent peut évaluer un client après la prestation
- FR50: Un client peut signaler un problème sur une réservation en cours ou passée
- FR51: Un talent peut enrichir son portfolio avec les photos/vidéos validées des prestations réalisées
- FR52: Un talent peut gérer son calendrier de disponibilités (bloquer des jours, marquer les jours de repos)
- FR53: Un talent peut configurer des alertes de surcharge (nombre maximum de prestations par période)
- FR54: Un manager peut consulter et gérer le calendrier de ses talents
- FR55: Un manager peut valider ou refuser des demandes de réservation au nom de ses talents
- FR56: Un manager peut répondre aux messages clients au nom de ses talents
- FR57: Le système attribue automatiquement un niveau au talent (Nouveau, Confirmé, Premium, Elite) basé sur son activité et ses évaluations
- FR58: Un talent peut consulter ses analytics (vues du profil, villes qui le recherchent, tendances)
- FR59: Un talent peut recevoir une attestation de revenus annuelle
- FR60: Un administrateur peut consulter les dashboards en temps réel (financier, opérationnel, qualité)
- FR61: Un administrateur peut gérer les litiges avec rapport de traçabilité horodaté
- FR62: Un administrateur peut émettre un avertissement formel à un talent
- FR63: Un administrateur peut suspendre un compte utilisateur
- FR64: Le système signale automatiquement les talents dont la note passe sous un seuil défini
- FR65: Le système détecte et signale les comportements suspects (doublons d'identité, transactions anormales)
- FR66: Un administrateur CEO peut déléguer des tâches spécifiques à ses collaborateurs (Comptable, Contrôleur, Modérateur)
- FR67: Un administrateur comptable peut consulter et exporter les données financières
- FR68: Un contrôleur opérationnel peut suivre les check-ins et les prestations en cours
- FR69: Un modérateur peut examiner et décider sur les avis signalés comme inappropriés
- FR70: Le système maintient une piste d'audit complète pour toutes les actions administratives
- FR71: Le système envoie des relances automatiques pour les actions administratives en attente
- FR72: Un administrateur peut consulter les KPIs de la plateforme (inscriptions, réservations, taux de litiges, CA)

### NonFunctional Requirements

- NFR1: Les pages web se chargent en moins de 3 secondes sur une connexion 3G standard en Côte d'Ivoire (LCP < 2,5s)
- NFR2: Les réponses API standards sont retournées en moins de 500ms
- NFR3: Les recherches avec filtres dans l'annuaire retournent des résultats en moins de 1 seconde
- NFR4: Le traitement d'un paiement Mobile Money aboutit en moins de 15 secondes
- NFR5: Le check-in jour J (géolocalisation + mise à jour statut) répond en moins de 2 secondes
- NFR6: L'envoi et la réception de messages dans la messagerie interne s'effectuent en moins de 1 seconde
- NFR7: L'application mobile Flutter démarre en moins de 3 secondes sur un appareil Android d'entrée de gamme (2 GB RAM)
- NFR8: La taille initiale de la page web ne dépasse pas 1,5 MB (images compressées WebP incluses)
- NFR9: Le système supporte 1 000 utilisateurs simultanés au lancement sans dégradation de performance
- NFR10: Les notifications push sont délivrées dans les 5 secondes suivant l'événement déclencheur
- NFR11: Toutes les données sensibles (pièces d'identité, données financières) sont chiffrées au repos avec AES-256
- NFR12: Toutes les communications API et web sont chiffrées en transit avec TLS 1.3 minimum
- NFR13: Les mots de passe sont hachés avec bcrypt et salt (minimum 12 rounds)
- NFR14: Les tokens d'authentification JWT expirent après 1 heure (access token) et 7 jours (refresh token)
- NFR15: L'API est protégée par rate limiting à 60 requêtes par minute par utilisateur authentifié
- NFR16: Les protections CSRF, XSS et SQL injection sont actives sur tous les endpoints
- NFR17: Les pièces d'identité soumises sont stockées dans un espace séparé et chiffré, accessible uniquement aux administrateurs autorisés
- NFR18: Les pièces d'identité sont supprimées après vérification (seuls le statut vérifié et la date sont conservés)
- NFR19: L'accès administrateur aux messages privés est journalisé avec piste d'audit complète
- NFR20: Le système détecte et bloque les tentatives de connexion suspectes (plus de 5 échecs consécutifs = blocage temporaire 15 min)
- NFR21: Les données de paiement (carte bancaire) ne sont jamais stockées par BookMi — déléguées aux passerelles certifiées PCI DSS
- NFR22: Les données personnelles sont conservées conformément à la loi ivoirienne 2013-450 (5 ans données financières, 1 an pièces d'identité après vérification)
- NFR23: L'architecture supporte une montée à 10 000 utilisateurs simultanés sans refonte majeure (horizon 12 mois)
- NFR24: La base de données supporte 500 talents et 5 000 clients actifs avec des temps de requête stables
- NFR25: Le système de stockage fichiers (photos/vidéos) supporte 100 GB de contenu média au lancement, extensible sans migration
- NFR26: Les pics de trafic du week-end (vendredi-samedi soir, x3 le trafic normal) sont absorbés sans dégradation
- NFR27: L'ajout d'un nouveau moyen de paiement ou opérateur Mobile Money est possible sans modification architecturale majeure
- NFR28: L'architecture est conçue pour supporter une expansion multi-pays (multi-devise, multi-langue) en V3
- NFR29: Le système maintient un uptime global de 99,5% minimum (maximum 43h de downtime par an)
- NFR30: L'uptime critique les vendredis et samedis (18h-2h) est de 99,9% minimum
- NFR31: Le basculement automatique (failover) entre serveurs s'effectue en moins de 30 secondes
- NFR32: Les sauvegardes automatiques de la base de données sont effectuées toutes les 6 heures avec rétention de 30 jours
- NFR33: En cas d'indisponibilité de la passerelle de paiement principale, le système bascule automatiquement vers la passerelle secondaire
- NFR34: Les données en cache hors-ligne sur l'application mobile restent accessibles pendant 7 jours sans connexion
- NFR35: Les webhooks de paiement sont traités de manière idempotente avec mécanisme de retry (3 tentatives, intervalle exponentiel)
- NFR36: L'interface web respecte le niveau WCAG 2.1 AA (contraste ≥ 4,5:1, navigation clavier, labels formulaires)
- NFR37: L'application mobile supporte les tailles de police système (Dynamic Type iOS, font scaling Android)
- NFR38: Tous les textes et interfaces sont en français (langue unique pour le MVP CI)
- NFR39: Les formulaires affichent des messages d'erreur clairs et contextuels en français
- NFR40: L'application mobile fonctionne correctement sur des écrans de 4,7 pouces minimum à 6,7 pouces
- NFR41: L'application mobile supporte le mode sombre (Dark Mode) iOS et Android
- NFR42: Les intégrations de paiement (Paystack/CinetPay) supportent les webhooks avec validation de signature
- NFR43: Les notifications push via Firebase Cloud Messaging (FCM) sont délivrées sur iOS et Android
- NFR44: Le stockage de fichiers média supporte un CDN avec points de présence en Afrique de l'Ouest
- NFR45: La géolocalisation fonctionne via Google Maps API ou OpenStreetMap avec précision au quartier
- NFR46: La génération de PDF (contrats, rapports) est effectuée côté serveur sans dépendance navigateur
- NFR47: Toutes les intégrations tierces sont encapsulées derrière des interfaces abstraites permettant le remplacement du fournisseur
- NFR48: Le code backend suit les conventions et standards Laravel (PSR-12, architecture MVC)
- NFR49: Le code mobile Flutter suit les recommandations officielles Flutter/Dart (linting, architecture BLoC ou Riverpod)
- NFR50: L'API est documentée automatiquement via OpenAPI/Swagger et mise à jour à chaque déploiement
- NFR51: Les logs applicatifs sont structurés (JSON) et centralisés avec rétention de 90 jours
- NFR52: Les déploiements sont automatisés via CI/CD avec rollback possible en moins de 5 minutes

### Additional Requirements

**Depuis l'Architecture :**

- ARCH-STARTER-1: Backend initialisé avec `laravel new bookmi --database=mysql --no-starter` (Laravel 12.x, PHP 8.2+)
- ARCH-STARTER-2: Mobile initialisé avec `very_good create flutter_app bookmi_app` (Flutter 3.38.x, Dart 3.x, BLoC 9.0)
- ARCH-INFRA-1: Infrastructure Docker + MySQL 8.x + Redis 7.x + Nginx reverse proxy + Supervisor sur Hostinger VPS
- ARCH-INFRA-2: CDN Cloudflare pour assets statiques et médias avec PoP Afrique de l'Ouest
- ARCH-INFRA-3: SSL via Cloudflare (full strict), HTTPS forcé
- ARCH-INFRA-4: Sauvegardes automatiques via Spatie Laravel Backup toutes les 6h, rétention 30j, stockage local VPS + copie externe
- ARCH-AUTH-1: Authentification mobile via Laravel Sanctum (tokens 24h), web admin via sessions Laravel
- ARCH-AUTH-2: 7 rôles via Spatie Laravel Permission v7 : client, talent, manager, admin_ceo, admin_comptable, admin_controleur, admin_moderateur
- ARCH-AUTH-3: 2 guards : `api` (Sanctum) + `web` (Session)
- ARCH-RT-1: WebSocket via Laravel Reverb pour messagerie temps réel et tracker jour J
- ARCH-RT-2: Channels : private (user, booking, conversation) + presence (tracking jour J)
- ARCH-QUEUE-1: Redis + Laravel Horizon avec 4 pipelines : payments (3 workers), notifications (2), media (2), default (1)
- ARCH-API-1: API versionnée URL-based `/api/v1/`, format JSON envelope `{ "data": {...}, "error": {...} }`
- ARCH-API-2: Pagination cursor-based pour mobile, offset pour admin
- ARCH-API-3: Dates ISO 8601 UTC dans l'API, montants en centimes (int), snake_case partout
- ARCH-API-4: Documentation API via Scribe (auto-générée)
- ARCH-API-5: Codes d'erreur métier préfixés par domaine : AUTH_, BOOKING_, PAYMENT_, ESCROW_, TALENT_, MEDIA_, VALIDATION_
- ARCH-FLUTTER-1: State management BLoC 9.0 avec sealed classes (Dart 3 exhaustive pattern matching)
- ARCH-FLUTTER-2: Navigation GoRouter avec deep linking (`bookmi.ci/talent/dj-kerozen`)
- ARCH-FLUTTER-3: HTTP client Dio avec interceptors (auth, retry, logging)
- ARCH-FLUTTER-4: Stockage local Hive (cache 7j), stockage sécurisé flutter_secure_storage
- ARCH-CICD-1: GitHub Actions : lint → test → build → deploy, rollback < 5min via tags
- ARCH-CICD-2: Linting : Laravel Pint (PSR-12) + Larastan + Flutter very_good_analysis
- ARCH-MONITOR-1: Monitoring erreurs Sentry (Laravel + Flutter), alertes Slack/email
- ARCH-MONITOR-2: Laravel Telescope (dev) + Horizon dashboard (prod) pour monitoring queues
- ARCH-MONITOR-3: Logs JSON structurés Monolog, rotation quotidienne, rétention 90j
- ARCH-PATTERN-1: 47 patterns de cohérence définis (naming, structure, format, communication, process) — tous les agents IA doivent suivre ces patterns
- ARCH-PATTERN-2: Pattern Service Layer + Repository obligatoire côté Laravel (Controllers ne contiennent PAS de logique métier)
- ARCH-PATTERN-3: Pattern BLoC obligatoire par feature Flutter (3 fichiers : event, state, bloc) avec sealed classes
- ARCH-PATTERN-4: Format réponse erreur unifié avec codes métier BookMi
- ARCH-PATTERN-5: Tests miroirs obligatoires (tests reflètent la structure du code source)

**Depuis l'UX Design :**

- UX-DESIGN-1: Design system hybride : Material 3 (Flutter) fortement thématisé + couche glassmorphism custom + Tailwind CSS (admin web)
- UX-DESIGN-2: Dégradation gracieuse GPU en 3 tiers : Tier 3 (full blur sigma 20), Tier 2 (blur sigma 10), Tier 1 (fond opaque 0.85, pas de blur)
- UX-DESIGN-3: Détection automatique du tier GPU au runtime via `DeviceCapability.detectGlassLevel()`
- UX-DESIGN-4: Police Nunito (Google Fonts) avec hiérarchie 10 niveaux (Display Large 36px → Overline 10px)
- UX-DESIGN-5: Palette de marque : Navy #1A2744 (Brand) + Blue #2196F3 (Brand) + Orange #FF6B35 (CTA uniquement)
- UX-DESIGN-6: 9 tokens glassmorphism (glass-white, glass-dark, glass-border, glass-blur, etc.)
- UX-DESIGN-7: 6 gradients définis (hero, brand, cta, card, shield, celebration)
- UX-DESIGN-8: 7 couleurs par catégorie talent (DJ=violet, Groupe=bleu foncé, Humoriste=rose, etc.)
- UX-COMP-1: 10 composants custom à développer : GlassCard, GlassAppBar, GlassShield, TalentCard, StatusTracker, CelebrationOverlay, ChatBubble, ProgressRing, MobileMoneySelector, FilterBar
- UX-COMP-2: Composants Material 3 thématisés : NavigationBar, SearchBar, BottomSheet, Chip, TextField, Buttons, TabBar, Dialog, etc.
- UX-NAV-1: Bottom tab bar 5 onglets : Accueil, Recherche, Réservations, Messages, Profil
- UX-NAV-2: Deep linking universel : `bookmi.ci/talent/{slug}` ouvre l'app si installée
- UX-NAV-3: Transitions : Hero animation (carte → profil), slide right (push), bottom sheet up, fade (tabs)
- UX-FLOW-1: Flow réservation en 4 étapes stepper : Package → Date/Lieu → Récapitulatif → Paiement
- UX-FLOW-2: Onboarding talent gamifié en 5 étapes avec barre circulaire de progression (20% → 100%)
- UX-FLOW-3: Sauvegarde automatique de chaque champ (connexion intermittente CI)
- UX-FLOW-4: Tracker jour J en 5 statuts temps réel (en préparation → en route → arrivé → en cours → terminé)
- UX-FLOW-5: Évaluation post-prestation avec suggestions de mots en français (ponctuel, ambiance, professionnel)
- UX-ACCESS-1: Conformité WCAG 2.1 AA : contraste ≥ 4.5:1, zones de tap ≥ 48x48px, labels sémantiques
- UX-ACCESS-2: Support Dynamic Type (iOS) et font scaling (Android)
- UX-ACCESS-3: Support mode sombre (Dark Mode) iOS et Android
- UX-ACCESS-4: Support mouvements réduits (`prefers-reduced-motion`) — animations désactivées
- UX-RESPONSIVE-1: 4 breakpoints Flutter : compact (<360px), medium (360-599px), expanded (600-839px), large (≥840px)
- UX-RESPONSIVE-2: Admin Tailwind : desktop-first, sidebar 256px collapsible, grille 12 colonnes
- UX-RESPONSIVE-3: Web public SSR : mobile-first, responsive desktop, SEO Schema.org
- UX-FEEDBACK-1: Haptic feedback sur actions critiques (paiement, confirmation)
- UX-FEEDBACK-2: Skeleton screens pour tous les chargements de listes
- UX-FEEDBACK-3: CelebrationOverlay pour moments clés (paiement confirmé, profil complété, niveau atteint)
- UX-FEEDBACK-4: Messages d'erreur en français courant avec suggestion d'action corrective
- UX-OFFLINE-1: Données disponibles hors-ligne : réservations confirmées, calendrier, messages chargés, contrats PDF
- UX-OFFLINE-2: Queue de synchronisation pour actions hors-ligne, exécution au retour online
- UX-FORM-1: Formatage FCFA avec séparateur milliers espace (convention BCEAO)
- UX-FORM-2: Téléphone +225 prefix auto avec masque XX XX XX XX XX
- UX-FORM-3: Validation temps réel (au blur) avec messages contextuels en français

### FR Coverage Map

| FR | Epic | Description |
|---|---|---|
| FR3 | Epic 1 | Soumission pièce d'identité |
| FR4 | Epic 1 | Validation vérification identité |
| FR5 | Epic 1 | Badge "Vérifié" |
| FR6 | Epic 1 | Profil riche talent |
| FR11 | Epic 1 | Annuaire talents vérifiés |
| FR12 | Epic 1 | Filtres talents |
| FR13 | Epic 1 | Recherche géolocalisation |
| FR14 | Epic 1 | Profil public talent |
| FR15 | Epic 1 | Suggestions talents similaires |
| FR16 | Epic 1 | Favoris |
| FR17 | Epic 1 | URL unique talent |
| FR23 | Epic 1 | Packages de prestation |
| FR24 | Epic 1 | Micro-prestations |
| FR1 | Epic 2 | Inscription client |
| FR2 | Epic 2 | Inscription talent |
| FR9 | Epic 2 | Connexion email/mot de passe |
| FR10 | Epic 2 | Réinitialisation mot de passe |
| FR18 | Epic 3 | Demande de réservation |
| FR19 | Epic 3 | Accepter/refuser réservation |
| FR20 | Epic 3 | Devis détaillé transparent |
| FR21 | Epic 3 | Contrat électronique auto |
| FR22 | Epic 3 | Télécharger contrat PDF |
| FR25 | Epic 3 | Réservation express |
| FR26 | Epic 3 | Politique annulation graduée |
| FR27 | Epic 3 | Annulation réservation |
| FR28 | Epic 3 | Report réservation |
| FR52 | Epic 3 | Calendrier disponibilités |
| FR29 | Epic 4 | Paiement Mobile Money |
| FR30 | Epic 4 | Paiement carte/virement |
| FR31 | Epic 4 | Séquestre escrow |
| FR32 | Epic 4 | Versement auto 24h |
| FR33 | Epic 4 | Confirmation auto 48h |
| FR34 | Epic 4 | Remboursement litige |
| FR35 | Epic 4 | Dashboard financier |
| FR36 | Epic 4 | Moyen versement préféré |
| FR37 | Epic 4 | Failover passerelles |
| FR38 | Epic 4 | Export rapports financiers |
| FR39 | Epic 5 | Messagerie interne |
| FR40 | Epic 5 | Détection coordonnées |
| FR41 | Epic 5 | Réponses automatiques |
| FR42 | Epic 5 | Notifications push |
| FR43 | Epic 5 | Rappels J-7 et J-2 |
| FR44 | Epic 5 | Accès messages litige |
| FR45 | Epic 6 | Suivi jour J temps réel |
| FR46 | Epic 6 | Check-in géolocalisation |
| FR47 | Epic 6 | Alerte check-in manquant |
| FR48 | Epic 6 | Évaluation talent par client |
| FR49 | Epic 6 | Évaluation client par talent |
| FR50 | Epic 6 | Signalement problème |
| FR51 | Epic 6 | Portfolio prestations |
| FR7 | Epic 7 | Assigner manager |
| FR8 | Epic 7 | Interface unifiée manager |
| FR53 | Epic 7 | Alertes surcharge |
| FR54 | Epic 7 | Calendrier manager |
| FR55 | Epic 7 | Validation réservation manager |
| FR56 | Epic 7 | Messages manager |
| FR57 | Epic 7 | Niveaux auto talent |
| FR58 | Epic 7 | Analytics talent |
| FR59 | Epic 7 | Attestation revenus |
| FR60 | Epic 8 | Dashboards admin |
| FR61 | Epic 8 | Gestion litiges |
| FR62 | Epic 8 | Avertissement formel |
| FR63 | Epic 8 | Suspension compte |
| FR64 | Epic 8 | Signalement note basse |
| FR65 | Epic 8 | Détection comportements suspects |
| FR66 | Epic 8 | Délégation tâches admin |
| FR67 | Epic 8 | Export données financières |
| FR68 | Epic 8 | Suivi check-ins contrôleur |
| FR69 | Epic 8 | Modération avis |
| FR70 | Epic 8 | Piste d'audit |
| FR71 | Epic 8 | Relances automatiques |
| FR72 | Epic 8 | KPIs plateforme |

## Epic List

### Epic 1: Profil Talent & Découverte
**Goal:** Permettre aux talents de créer un profil riche et aux clients de découvrir et explorer les talents disponibles.
**FRs:** FR3, FR4, FR5, FR6, FR11, FR12, FR13, FR14, FR15, FR16, FR17, FR23, FR24 (13 FRs)
**Infrastructure incluse:** ARCH-STARTER-1, ARCH-STARTER-2, ARCH-INFRA-1, ARCH-INFRA-2, ARCH-INFRA-3, ARCH-API-1, ARCH-API-2, ARCH-API-3, ARCH-API-4, ARCH-API-5, ARCH-FLUTTER-1, ARCH-FLUTTER-2, ARCH-FLUTTER-3, ARCH-FLUTTER-4, ARCH-CICD-1, ARCH-CICD-2, ARCH-PATTERN-1, ARCH-PATTERN-2, ARCH-PATTERN-3, ARCH-PATTERN-4, ARCH-PATTERN-5, UX-DESIGN-1 à UX-DESIGN-8, UX-COMP-1, UX-COMP-2, UX-NAV-1, UX-NAV-2, UX-NAV-3, UX-RESPONSIVE-1, UX-RESPONSIVE-3, UX-ACCESS-1 à UX-ACCESS-4, UX-FEEDBACK-2, UX-FEEDBACK-4, UX-FORM-1 à UX-FORM-3

### Epic 2: Authentification
**Goal:** Permettre aux utilisateurs de s'inscrire, se connecter et gérer leur accès de manière sécurisée.
**FRs:** FR1, FR2, FR9, FR10 (4 FRs)
**NFRs clés:** NFR11-NFR22 (sécurité), NFR13 (bcrypt), NFR14 (tokens), NFR15 (rate limiting), NFR20 (blocage connexion)
**Infrastructure incluse:** ARCH-AUTH-1, ARCH-AUTH-2, ARCH-AUTH-3

### Epic 3: Réservation & Contrats
**Goal:** Permettre aux clients de réserver un talent et de formaliser la prestation via un contrat électronique.
**FRs:** FR18, FR19, FR20, FR21, FR22, FR25, FR26, FR27, FR28, FR52 (10 FRs)
**NFRs clés:** NFR2 (API < 500ms), NFR46 (PDF serveur)
**UX inclus:** UX-FLOW-1 (stepper 4 étapes), UX-FLOW-3 (sauvegarde auto)

### Epic 4: Paiement & Séquestre
**Goal:** Permettre les paiements sécurisés via Mobile Money et gérer le système de séquestre (escrow) avec versement automatique.
**FRs:** FR29, FR30, FR31, FR32, FR33, FR34, FR35, FR36, FR37, FR38 (10 FRs)
**NFRs clés:** NFR4 (Mobile Money < 15s), NFR21 (PCI DSS), NFR33 (failover passerelle), NFR35 (webhooks idempotents), NFR42 (validation signature)
**Infrastructure incluse:** ARCH-QUEUE-1 (pipeline payments)
**UX inclus:** UX-FEEDBACK-1 (haptic), UX-FEEDBACK-3 (celebration)

### Epic 5: Communication & Notifications
**Goal:** Permettre la communication entre clients et talents via messagerie en temps réel et notifications push.
**FRs:** FR39, FR40, FR41, FR42, FR43, FR44 (6 FRs)
**NFRs clés:** NFR6 (messagerie < 1s), NFR10 (push < 5s), NFR19 (audit messages), NFR43 (FCM)
**Infrastructure incluse:** ARCH-RT-1, ARCH-RT-2 (Reverb WebSocket)

### Epic 6: Suivi Jour J & Évaluation
**Goal:** Permettre le suivi en temps réel de la prestation le jour J et les évaluations bilatérales post-prestation.
**FRs:** FR45, FR46, FR47, FR48, FR49, FR50, FR51 (7 FRs)
**NFRs clés:** NFR5 (check-in < 2s), NFR45 (géolocalisation)
**UX inclus:** UX-FLOW-4 (tracker 5 statuts), UX-FLOW-5 (évaluation suggestions), UX-FEEDBACK-3 (celebration)

### Epic 7: Gestion Talents & Manager
**Goal:** Permettre aux managers de gérer plusieurs talents et aux talents de suivre leur progression et analytics.
**FRs:** FR7, FR8, FR53, FR54, FR55, FR56, FR57, FR58, FR59 (9 FRs)
**UX inclus:** UX-FLOW-2 (onboarding gamifié)

### Epic 8: Administration & Gouvernance
**Goal:** Fournir aux administrateurs les outils de gouvernance, modération et suivi de la plateforme.
**FRs:** FR60, FR61, FR62, FR63, FR64, FR65, FR66, FR67, FR68, FR69, FR70, FR71, FR72 (13 FRs)
**NFRs clés:** NFR48-NFR52 (maintenabilité)
**Infrastructure incluse:** ARCH-MONITOR-1, ARCH-MONITOR-2, ARCH-MONITOR-3, ARCH-INFRA-4
**UX inclus:** UX-RESPONSIVE-2 (admin Tailwind desktop-first)

---

## Epic 1: Profil Talent & Découverte

**Goal:** Permettre aux talents de créer un profil riche et aux clients de découvrir et explorer les talents disponibles.
**FRs:** FR3, FR4, FR5, FR6, FR11, FR12, FR13, FR14, FR15, FR16, FR17, FR23, FR24 (13 FRs)

### Story 1.1: Initialisation du projet backend Laravel

As a développeur,
I want le projet Laravel initialisé avec la configuration de base (MySQL, Redis, structure API, linting),
So that toutes les stories suivantes disposent d'une fondation backend solide.

**Acceptance Criteria:**

**Given** la commande `laravel new bookmi --database=mysql --no-starter` est exécutée
**When** le projet est créé
**Then** la structure des dossiers suit exactement `app/Http/Controllers/Api/V1/`, `app/Services/`, `app/Repositories/`, `app/Enums/`
**And** les fichiers de configuration Pint (PSR-12) et Larastan sont en place
**And** le docker-compose.yml fournit MySQL 8.x + Redis 7.x
**And** le format de réponse API envelope est implémenté (`{ "data": {...}, "error": {...} }`)
**And** les routes API versionnées `/api/v1/` sont configurées
**And** le fichier `config/bookmi.php` existe avec les constantes métier (commission_rate, etc.)

### Story 1.2: Initialisation du projet mobile Flutter

As a développeur,
I want le projet Flutter initialisé avec Very Good CLI, BLoC, GoRouter et le design system de base,
So that toutes les stories mobiles suivantes disposent d'une fondation Flutter cohérente.

**Acceptance Criteria:**

**Given** la commande `very_good create flutter_app bookmi_app` est exécutée
**When** le projet est créé
**Then** la structure features-based est en place (`lib/features/`, `lib/core/`)
**And** GoRouter est configuré avec deep linking
**And** Dio est configuré avec interceptors (auth, retry, logging)
**And** les tokens de design glassmorphism sont définis (couleurs, typographie Nunito, spacing, glass tokens)
**And** les composants GlassCard et GlassAppBar sont implémentés avec dégradation GPU 3 tiers
**And** la bottom navigation bar 5 onglets est en place
**And** Hive et flutter_secure_storage sont configurés
**And** les 3 flavors (dev, staging, prod) sont opérationnels

### Story 1.3: Modèle Talent et CRUD profil (backend)

As a talent,
I want pouvoir créer et gérer mon profil riche (bio, photos, vidéos, liens réseaux sociaux),
So that les clients puissent me découvrir et voir mes prestations. (FR6)

**Acceptance Criteria:**

**Given** un utilisateur authentifié avec le rôle talent
**When** il envoie `POST /api/v1/talent_profiles` avec les données du profil
**Then** un profil talent est créé avec les champs : stage_name, bio, category_id, subcategory_id, city, cachet_amount (centimes), social_links (JSON), is_verified (false par défaut)
**And** les tables `categories`, `talent_profiles` et les seeders de catégories (12 catégories) sont créés
**And** `PUT /api/v1/talent_profiles/{talent_profile}` permet la mise à jour du profil
**And** les Form Requests valident les données avec messages en français
**And** le pattern Service Layer + Repository est appliqué (TalentProfileService, TalentRepository)
**And** le slug unique est généré automatiquement pour l'URL publique (FR17)

### Story 1.4: Vérification d'identité (backend)

As a utilisateur,
I want soumettre ma pièce d'identité pour vérification,
So that je puisse obtenir le badge "Vérifié" et inspirer confiance. (FR3, FR4, FR5)

**Acceptance Criteria:**

**Given** un utilisateur authentifié
**When** il envoie `POST /api/v1/verifications` avec une photo de CNI/passeport
**Then** la pièce est stockée dans un espace chiffré séparé (AES-256)
**And** un administrateur peut consulter la demande via `GET /admin/verifications`
**And** un administrateur peut valider ou rejeter via `POST /admin/verifications/{id}/review`
**And** après validation, `is_verified` passe à true et le badge "Vérifié" est visible
**And** la pièce d'identité est supprimée après vérification (seul le statut et la date sont conservés) (NFR18)
**And** l'accès admin est journalisé avec piste d'audit

### Story 1.5: Annuaire des talents avec filtres (backend)

As a client,
I want parcourir l'annuaire des talents vérifiés et filtrer par critères,
So that je puisse trouver le talent adapté à mon événement. (FR11, FR12)

**Acceptance Criteria:**

**Given** un client (authentifié ou non)
**When** il envoie `GET /api/v1/talents` avec des filtres optionnels
**Then** la liste des talents vérifiés est retournée avec pagination cursor-based
**And** les filtres supportés sont : `category_id`, `subcategory_id`, `min_cachet`, `max_cachet`, `city`, `min_rating`
**And** les résultats sont triables par `rating`, `cachet_amount`, `created_at`
**And** le temps de réponse est < 1 seconde (NFR3)
**And** le format de réponse suit le JSON envelope avec `meta.cursor`

### Story 1.6: Recherche par géolocalisation (backend)

As a client,
I want rechercher des talents par proximité géographique,
So that je puisse trouver des artistes disponibles près de mon événement. (FR13)

**Acceptance Criteria:**

**Given** un client avec sa position GPS (latitude, longitude)
**When** il envoie `GET /api/v1/talents?lat=5.36&lng=-4.01&radius=50`
**Then** les talents dans le rayon spécifié (km) sont retournés triés par distance
**And** la recherche utilise les fonctions spatiales MySQL (`ST_Distance_Sphere`)
**And** le champ `distance_km` est inclus dans chaque résultat
**And** la performance est < 1 seconde même avec 500 talents en base (NFR24)

### Story 1.7: Profil public talent (backend)

As a client,
I want consulter le profil complet d'un talent (portfolio, avis, packages, disponibilités),
So that je puisse prendre une décision éclairée avant de réserver. (FR14, FR15, FR17)

**Acceptance Criteria:**

**Given** un visiteur ou client
**When** il accède à `GET /api/v1/talents/{slug}`
**Then** le profil complet est retourné : bio, portfolio (photos/vidéos), note moyenne, nombre d'avis, packages, score de fiabilité
**And** des suggestions de talents similaires sont incluses (même catégorie, même ville) (FR15)
**And** l'URL publique `bookmi.ci/talent/{slug}` fonctionne (FR17)
**And** la page web SSR (Blade) est servie pour le SEO avec Schema.org

### Story 1.8: Packages de prestation (backend)

As a talent,
I want créer et gérer mes packages de prestation (Essentiel, Standard, Premium),
So that les clients puissent choisir l'offre adaptée à leur budget. (FR23, FR24)

**Acceptance Criteria:**

**Given** un talent authentifié avec un profil actif
**When** il envoie `POST /api/v1/service_packages` avec nom, description, cachet_amount, durée, inclusions
**Then** le package est créé et associé au profil talent
**And** 3 types de packages sont supportés : Essentiel, Standard, Premium
**And** les micro-prestations (vidéo personnalisée, dédicace audio) sont supportées comme type spécial (FR24)
**And** les packages sont affichés sur le profil public du talent
**And** `PUT` et `DELETE` sont disponibles pour la gestion

### Story 1.9: Favoris (backend + mobile)

As a client,
I want pouvoir suivre des talents en favoris,
So that je puisse facilement les retrouver plus tard. (FR16)

**Acceptance Criteria:**

**Given** un client authentifié
**When** il envoie `POST /api/v1/talents/{talent}/favorite`
**Then** le talent est ajouté à ses favoris
**And** `DELETE /api/v1/talents/{talent}/favorite` retire le favori
**And** `GET /api/v1/me/favorites` retourne la liste des favoris avec pagination
**And** côté Flutter, l'icône coeur est animée (toggle) sur TalentCard
**And** les favoris sont disponibles hors-ligne via cache Hive (UX-OFFLINE-1)

### Story 1.10: Écrans découverte Flutter (mobile)

As a client,
I want naviguer dans l'annuaire des talents depuis l'app mobile avec une expérience fluide et visuellement riche,
So that la découverte de talents soit agréable et intuitive.

**Acceptance Criteria:**

**Given** l'app est lancée et l'utilisateur est sur l'onglet Recherche
**When** il parcourt la liste des talents
**Then** les TalentCards glassmorphism s'affichent avec photo, nom de scène, catégorie (couleur par catégorie), note, cachet
**And** les skeleton screens s'affichent pendant le chargement (UX-FEEDBACK-2)
**And** le pull-to-refresh fonctionne
**And** la FilterBar permet le filtrage par catégorie, budget, ville, note
**And** le scroll infini charge automatiquement les pages suivantes (cursor-based)
**And** le tap sur une TalentCard ouvre le profil avec Hero animation (UX-NAV-3)
**And** l'app fonctionne sur des écrans de 4,7" à 6,7" (NFR40)

### Story 1.11: Écran profil talent Flutter (mobile)

As a client,
I want voir le profil complet d'un talent dans l'app avec un design glassmorphism immersif,
So that je puisse explorer son portfolio et ses offres avant de réserver.

**Acceptance Criteria:**

**Given** le client a tapé sur une TalentCard
**When** l'écran profil s'affiche
**Then** les sections sont visibles : header avec photo/badge vérifié, bio, portfolio (grille photos/vidéos), packages (3 cartes comparatives), avis récents avec notes, bouton de réservation CTA (orange #FF6B35)
**And** le deep linking fonctionne (`bookmi.ci/talent/{slug}` ouvre l'écran profil) (UX-NAV-2)
**And** les suggestions de talents similaires sont affichées en bas
**And** le bouton favori (coeur) est interactif
**And** le mode sombre est supporté (UX-ACCESS-3)
**And** le composant respecte WCAG 2.1 AA (contraste, tap zones ≥ 48x48px)

### Story 1.12: Pipeline CI/CD initiale

As a développeur,
I want un pipeline CI/CD configuré pour les deux projets (Laravel + Flutter),
So that chaque push soit automatiquement vérifié par les linters et les tests. (ARCH-CICD-1, ARCH-CICD-2)

**Acceptance Criteria:**

**Given** un push est fait sur le repository
**When** GitHub Actions se déclenche
**Then** le pipeline Laravel exécute : `pint --test` → `phpstan analyse` → `php artisan test`
**And** le pipeline Flutter exécute : `dart analyze` → `dart format --set-exit-if-changed .` → `flutter test`
**And** le pipeline bloque le merge si une étape échoue
**And** Sentry est configuré pour le monitoring d'erreurs (ARCH-MONITOR-1)

---

## Epic 2: Authentification

**Goal:** Permettre aux utilisateurs de s'inscrire, se connecter et gérer leur accès de manière sécurisée.
**FRs:** FR1, FR2, FR9, FR10 (4 FRs)

### Story 2.1: Inscription client et talent (backend)

As a visiteur,
I want créer un compte client ou talent avec email et numéro de téléphone,
So that je puisse accéder aux fonctionnalités de BookMi. (FR1, FR2)

**Acceptance Criteria:**

**Given** un visiteur non inscrit
**When** il envoie `POST /api/v1/auth/register` avec email, phone (+225 format E.164), password, first_name, last_name, role (client ou talent), et optionnellement category_id/subcategory_id (pour talent)
**Then** un utilisateur est créé avec le mot de passe haché (bcrypt 12 rounds) (NFR13)
**And** le rôle est assigné via Spatie Permission (client ou talent) (ARCH-AUTH-2)
**And** un OTP est envoyé par SMS au numéro de téléphone
**And** la réponse retourne `201 Created` avec un message de confirmation
**And** les validations incluent : email unique, phone unique, password min 8 caractères, messages en français
**And** le rate limiting est de 10/min sur cet endpoint
**And** la table `users` est créée si absente avec les champs : email, phone, password, first_name, last_name, phone_verified_at, is_active

### Story 2.2: Vérification OTP téléphone (backend)

As a utilisateur nouvellement inscrit,
I want vérifier mon numéro de téléphone via un code OTP,
So that mon compte soit activé et sécurisé.

**Acceptance Criteria:**

**Given** un utilisateur inscrit avec un OTP envoyé
**When** il envoie `POST /api/v1/auth/verify-otp` avec phone et code OTP
**Then** le champ `phone_verified_at` est mis à jour
**And** un token Sanctum 24h est retourné avec les données utilisateur (ARCH-AUTH-1)
**And** l'OTP expire après 10 minutes
**And** après 5 tentatives OTP échouées, le compte est temporairement bloqué 15 min (NFR20)
**And** un nouvel OTP peut être demandé via `POST /api/v1/auth/resend-otp` (max 3 envois par heure)

### Story 2.3: Connexion email/mot de passe (backend)

As a utilisateur enregistré,
I want me connecter avec mon email et mot de passe,
So that je puisse accéder à mon compte depuis n'importe quel appareil. (FR9)

**Acceptance Criteria:**

**Given** un utilisateur avec un compte vérifié
**When** il envoie `POST /api/v1/auth/login` avec email et password
**Then** un token Sanctum 24h est retourné avec les données utilisateur et ses rôles/permissions
**And** les tentatives échouées sont comptabilisées : après 5 échecs consécutifs, blocage temporaire 15 min (NFR20)
**And** le rate limiting est de 10/min sur cet endpoint
**And** la réponse inclut `{ "data": { "token": "...", "user": {...}, "roles": [...] } }`
**And** un événement `UserLoggedIn` est émis pour le logging

### Story 2.4: Réinitialisation mot de passe (backend)

As a utilisateur,
I want réinitialiser mon mot de passe via email,
So that je puisse récupérer l'accès à mon compte si j'ai oublié mon mot de passe. (FR10)

**Acceptance Criteria:**

**Given** un utilisateur a oublié son mot de passe
**When** il envoie `POST /api/v1/auth/forgot-password` avec son email
**Then** un email avec un lien de réinitialisation est envoyé (via Mailgun)
**And** le lien expire après 60 minutes
**And** `POST /api/v1/auth/reset-password` avec token, email, nouveau password met à jour le mot de passe
**And** tous les tokens Sanctum existants sont révoqués après réinitialisation
**And** un événement `PasswordReset` est émis
**And** le rate limiting est de 5/min sur l'endpoint forgot-password

### Story 2.5: Déconnexion et gestion token (backend)

As a utilisateur connecté,
I want me déconnecter et révoquer mon token,
So that mon compte reste sécurisé après utilisation.

**Acceptance Criteria:**

**Given** un utilisateur authentifié avec un token Sanctum valide
**When** il envoie `POST /api/v1/auth/logout`
**Then** le token courant est révoqué
**And** `GET /api/v1/me` retourne les informations de l'utilisateur connecté (profil, rôles, permissions)
**And** un Dio interceptor côté Flutter intercepte tout 401, vide le secure storage, et redirige vers login
**And** les tokens expirent automatiquement après 24h (ARCH-AUTH-1)

### Story 2.6: Écrans d'authentification Flutter (mobile)

As a visiteur,
I want m'inscrire et me connecter depuis l'app mobile avec une expérience fluide,
So that l'accès à BookMi soit simple et rapide.

**Acceptance Criteria:**

**Given** l'app est lancée sans session active
**When** l'utilisateur arrive sur l'écran d'accueil
**Then** les écrans suivants sont disponibles : Splash screen → Onboarding (3 slides) → Login → Register → OTP Verification → Forgot Password
**And** le formulaire d'inscription affiche les champs avec le préfixe +225 auto et masque XX XX XX XX XX (UX-FORM-2)
**And** la validation temps réel (au blur) affiche les erreurs en français (UX-FORM-3)
**And** le token est stocké dans flutter_secure_storage après connexion réussie
**And** GoRouter guard redirige vers login si pas de token valide
**And** le design glassmorphism est appliqué (GlassCard pour les formulaires)
**And** le mode sombre est supporté

---

## Epic 3: Réservation & Contrats

**Goal:** Permettre aux clients de réserver un talent et de formaliser la prestation via un contrat électronique.
**FRs:** FR18, FR19, FR20, FR21, FR22, FR25, FR26, FR27, FR28, FR52 (10 FRs)

### Story 3.1: Calendrier de disponibilités du talent (backend)

As a talent,
I want gérer mon calendrier de disponibilités (bloquer des jours, marquer les jours de repos),
So that les clients ne puissent réserver que les jours où je suis disponible. (FR52)

**Acceptance Criteria:**

**Given** un talent authentifié
**When** il envoie `POST /api/v1/calendar_slots` avec date, status (available/blocked/rest)
**Then** le créneau est créé dans la table `calendar_slots`
**And** `GET /api/v1/talents/{talent}/calendar?month=2026-03` retourne les disponibilités du mois
**And** `PUT /api/v1/calendar_slots/{slot}` permet de modifier un créneau
**And** `DELETE /api/v1/calendar_slots/{slot}` supprime un créneau bloqué
**And** les jours avec réservation confirmée sont automatiquement marqués occupés
**And** le CalendarService gère les conflits de dates

### Story 3.2: Demande de réservation (backend)

As a client,
I want envoyer une demande de réservation à un talent avec la date, le lieu et le package choisi,
So that le talent puisse examiner et répondre à ma demande. (FR18)

**Acceptance Criteria:**

**Given** un client authentifié consultant le profil d'un talent disponible
**When** il envoie `POST /api/v1/booking_requests` avec talent_id, service_package_id, event_date, event_location, message
**Then** la réservation est créée avec status `pending` dans la table `booking_requests`
**And** la machine à états BookingStatus est implémentée (enum : pending, accepted, paid, confirmed, completed, cancelled, disputed)
**And** le talent reçoit une notification (événement `BookingCreated`)
**And** la disponibilité du talent est vérifiée avant création (pas de conflit calendrier)
**And** le montant total est calculé : cachet_amount + commission 15% (affiché comme frais BookMi)
**And** le BookingService gère toute la logique métier

### Story 3.3: Accepter/Refuser une réservation (backend)

As a talent,
I want accepter ou refuser une demande de réservation,
So that je puisse contrôler mes engagements. (FR19)

**Acceptance Criteria:**

**Given** un talent authentifié avec une réservation en status `pending`
**When** il envoie `POST /api/v1/booking_requests/{booking}/accept` ou `POST /api/v1/booking_requests/{booking}/reject`
**Then** le status passe à `accepted` ou `cancelled` respectivement
**And** le client reçoit une notification de la décision (événement `BookingConfirmed` ou `BookingCancelled`)
**And** en cas d'acceptation, le créneau calendrier est bloqué automatiquement
**And** la Policy vérifie que seul le talent (ou son manager) peut accepter/refuser
**And** une raison de refus optionnelle peut être fournie

### Story 3.4: Devis détaillé transparent (backend)

As a client,
I want consulter un devis détaillé transparent montrant le cachet artiste et les frais BookMi,
So that je comprenne exactement ce que je paie. (FR20)

**Acceptance Criteria:**

**Given** une réservation en status `accepted`
**When** le client accède à `GET /api/v1/booking_requests/{booking}`
**Then** le devis détaillé est inclus : cachet_amount (100% pour l'artiste), commission_amount (15% frais BookMi), total_amount (cachet + commission), tous en centimes
**And** la mention "Cachet artiste intact — BookMi ajoute 15% de frais de service" est incluse
**And** le détail du package sélectionné est affiché (nom, description, inclusions)

### Story 3.5: Contrat électronique et PDF (backend)

As a client,
I want un contrat électronique généré automatiquement et téléchargeable en PDF,
So that la prestation soit formalisée légalement. (FR21, FR22)

**Acceptance Criteria:**

**Given** une réservation en status `accepted`
**When** le système génère le contrat
**Then** le contrat contient : identification des parties (client + talent), description de la prestation, date et lieu, prix et conditions, politique d'annulation
**And** le PDF est généré côté serveur via DomPDF (NFR46)
**And** `GET /api/v1/booking_requests/{booking}/contract` retourne le PDF
**And** le contrat est conforme à la loi 2013-546 (contrats électroniques CI)
**And** le Job `GenerateContractPdf` s'exécute sur la queue `media`
**And** le contrat est disponible hors-ligne via cache local Flutter (UX-OFFLINE-1)

### Story 3.6: Réservation express (backend)

As a client,
I want effectuer une réservation express avec un processus simplifié,
So that je puisse réserver rapidement un talent pour un événement urgent. (FR25)

**Acceptance Criteria:**

**Given** un client authentifié sur le profil d'un talent avec réservation express activée
**When** il envoie `POST /api/v1/booking_requests` avec `is_express: true`
**Then** la réservation est créée et auto-acceptée (skip status `pending`, directement `accepted`)
**And** le processus passe directement à l'étape paiement
**And** seuls les talents ayant activé l'option express dans leur profil sont éligibles
**And** les mêmes validations de disponibilité s'appliquent

### Story 3.7: Politique d'annulation graduée (backend)

As a client,
I want pouvoir annuler une réservation confirmée selon une politique graduée,
So that les conditions d'annulation soient claires et équitables. (FR26, FR27)

**Acceptance Criteria:**

**Given** un client avec une réservation en status `paid` ou `confirmed`
**When** il envoie `POST /api/v1/booking_requests/{booking}/cancel`
**Then** la politique d'annulation graduée est appliquée automatiquement :
**And** J-14 ou plus : remboursement intégral (100%)
**And** J-7 à J-13 : remboursement partiel (50%)
**And** J-2 à J-6 : médiation uniquement (pas de remboursement auto)
**And** J-1 ou J : annulation impossible sans médiation
**And** le montant de remboursement est calculé et enregistré
**And** un événement `BookingCancelled` est émis avec le détail du remboursement

### Story 3.8: Report de réservation (backend)

As a client ou talent,
I want demander un report de réservation via médiation,
So that la prestation puisse être reprogrammée sans annulation. (FR28)

**Acceptance Criteria:**

**Given** un client ou talent avec une réservation active
**When** il envoie `POST /api/v1/booking_requests/{booking}/reschedule` avec new_event_date et reason
**Then** une demande de report est créée, nécessitant l'accord des deux parties
**And** l'autre partie reçoit une notification avec la proposition de nouvelle date
**And** `POST /api/v1/booking_requests/{booking}/reschedule/accept` ou `/reject` permet de répondre
**And** si accepté, la date est mise à jour et le calendrier ajusté
**And** si refusé, la réservation reste à la date originale

### Story 3.9: Flow de réservation Flutter (mobile)

As a client,
I want réserver un talent via un stepper 4 étapes fluide dans l'app mobile,
So that le processus de réservation soit clair et guidé. (UX-FLOW-1)

**Acceptance Criteria:**

**Given** un client authentifié sur le profil d'un talent
**When** il tape sur le bouton "Réserver" (CTA orange)
**Then** le stepper 4 étapes s'affiche : 1. Package → 2. Date/Lieu → 3. Récapitulatif → 4. Paiement
**And** l'étape 1 affiche les packages du talent avec cartes comparatives
**And** l'étape 2 affiche un calendrier interactif avec les jours disponibles/bloqués et un champ lieu
**And** l'étape 3 affiche le récapitulatif complet (devis transparent : cachet + frais BookMi 15%)
**And** l'étape 4 redirige vers le flow paiement (Epic 4)
**And** la sauvegarde automatique de chaque étape est implémentée (connexion intermittente CI) (UX-FLOW-3)
**And** le retour arrière entre étapes est possible sans perte de données
**And** le montant est formaté en FCFA avec séparateur milliers espace (UX-FORM-1)

### Story 3.10: Écran réservations Flutter (mobile)

As a client ou talent,
I want consulter la liste de mes réservations avec leur statut,
So that je puisse suivre l'avancement de mes prestations.

**Acceptance Criteria:**

**Given** un utilisateur authentifié sur l'onglet Réservations
**When** l'écran se charge
**Then** les réservations sont affichées par statut (en attente, confirmées, passées, annulées) avec tabs
**And** chaque réservation affiche : nom talent/client, date, lieu, montant, status badge coloré
**And** le tap ouvre le détail de la réservation avec le contrat PDF téléchargeable
**And** les actions contextuelles sont disponibles selon le status (accepter, annuler, reporter)
**And** les réservations confirmées sont disponibles hors-ligne (UX-OFFLINE-1)
**And** le skeleton screen s'affiche pendant le chargement

---

## Epic 4: Paiement & Séquestre

**Goal:** Permettre les paiements sécurisés via Mobile Money et gérer le système de séquestre (escrow) avec versement automatique.
**FRs:** FR29, FR30, FR31, FR32, FR33, FR34, FR35, FR36, FR37, FR38 (10 FRs)

### Story 4.1: Intégration Paystack — paiement Mobile Money (backend)

As a client,
I want payer via Mobile Money (Orange Money, Wave, MTN MoMo, Moov Money),
So that je puisse utiliser mon moyen de paiement préféré en Côte d'Ivoire. (FR29)

**Acceptance Criteria:**

**Given** une réservation en status `accepted` avec un montant total calculé
**When** le client envoie `POST /api/v1/payments/initiate` avec booking_id, payment_method (orange_money, wave, mtn_momo, moov_money), phone_number
**Then** le PaymentService initie la transaction via l'API Paystack
**And** les tables `transactions` et `escrow_holds` sont créées
**And** la transaction est enregistrée avec status `initiated` → `processing`
**And** le traitement Mobile Money aboutit en moins de 15 secondes (NFR4)
**And** les données de paiement (carte) ne sont JAMAIS stockées par BookMi (NFR21)
**And** l'interface abstraite `PaymentGatewayInterface` est implémentée (NFR47)

### Story 4.2: Webhooks paiement idempotents (backend)

As a système,
I want traiter les webhooks de paiement de manière fiable et idempotente,
So that chaque paiement soit correctement enregistré même en cas de retry.

**Acceptance Criteria:**

**Given** Paystack envoie un webhook de confirmation de paiement
**When** le webhook arrive sur `POST /api/v1/webhooks/paystack`
**Then** la signature du webhook est validée (NFR42)
**And** le traitement est idempotent (le même webhook reçu 2 fois ne crée pas de doublon) (NFR35)
**And** le Job `HandlePaymentWebhook` s'exécute sur la queue `payments` (3 workers)
**And** en cas de succès : transaction → `succeeded`, booking → `paid`, escrow_hold → `held`
**And** en cas d'échec : transaction → `failed`, notification au client
**And** le retry est configuré : 5 tentatives, backoff exponentiel (10s, 30s, 90s, 270s, 810s)
**And** un événement `PaymentReceived` est émis

### Story 4.3: Paiement carte bancaire et virement (backend)

As a client,
I want payer via carte bancaire ou virement,
So that j'aie une alternative au Mobile Money. (FR30)

**Acceptance Criteria:**

**Given** une réservation en status `accepted`
**When** le client envoie `POST /api/v1/payments/initiate` avec payment_method `card` ou `bank_transfer`
**Then** le PaymentService initie la transaction via Paystack (redirect URL pour 3D Secure)
**And** la callback URL `POST /api/v1/payments/callback` gère le retour après paiement
**And** `GET /api/v1/payments/{payment}/status` permet de vérifier le statut
**And** les mêmes webhooks idempotents s'appliquent

### Story 4.4: Système de séquestre escrow (backend)

As a système,
I want placer le paiement en séquestre jusqu'à la confirmation de la prestation,
So that le talent et le client soient protégés. (FR31, FR32, FR33)

**Acceptance Criteria:**

**Given** un paiement réussi (transaction status `succeeded`)
**When** l'escrow_hold est créé avec status `held`
**Then** le montant est bloqué dans l'escrow (cachet_amount pour le talent, commission pour BookMi)
**And** après confirmation du client (`POST /api/v1/booking_requests/{booking}/confirm_delivery`), le versement est déclenché dans les 24h (FR32)
**And** si le client ne se prononce pas sous 48h, la prestation est auto-confirmée (FR33)
**And** le Job `ReleaseEscrow` s'exécute sur la queue `payments`
**And** l'EscrowService gère les états : held → released, held → refunded, held → disputed
**And** chaque transition est loggée avec horodatage pour la piste d'audit

### Story 4.5: Versement automatique au talent (backend)

As a talent,
I want recevoir mon cachet intégral (100%) automatiquement après la prestation,
So that je sois payé rapidement et sans friction. (FR32, FR36)

**Acceptance Criteria:**

**Given** un escrow_hold en status `released` (prestation confirmée)
**When** le Job `ProcessPayout` s'exécute
**Then** le cachet intégral (100%) est versé au talent via son moyen de versement préféré
**And** le talent peut configurer son moyen de versement via `PUT /api/v1/me/payout_method` : orange_money, wave, mtn_momo, bank_account (FR36)
**And** la table `payouts` enregistre chaque versement avec status, montant, méthode, reference
**And** le versement est effectué dans les 24h suivant la confirmation
**And** un événement `PayoutCompleted` est émis et le talent est notifié
**And** le PayoutService gère la logique de calcul (commission déduite côté client, pas côté talent)

### Story 4.6: Failover passerelle de paiement (backend)

As a système,
I want basculer automatiquement vers CinetPay en cas d'indisponibilité de Paystack,
So that les paiements ne soient jamais bloqués. (FR37, NFR33)

**Acceptance Criteria:**

**Given** Paystack est indisponible (timeout ou erreur 5xx)
**When** une tentative de paiement échoue sur Paystack
**Then** le PaymentService bascule automatiquement vers CinetPay
**And** la même `PaymentGatewayInterface` est utilisée (implémentation CinetPay)
**And** le basculement est transparent pour le client
**And** un log est émis pour le monitoring (Sentry alert)
**And** la configuration du failover est dans `config/bookmi.php` (primary_gateway, fallback_gateway)

### Story 4.7: Remboursement en cas de litige (backend)

As a client,
I want être remboursé si un litige est résolu en ma faveur,
So that mon argent soit protégé en cas de problème. (FR34)

**Acceptance Criteria:**

**Given** un escrow_hold en status `disputed` avec un litige résolu en faveur du client
**When** un administrateur exécute le remboursement
**Then** le remboursement est initié via la passerelle de paiement d'origine
**And** l'escrow_hold passe à status `refunded`
**And** le montant remboursé est enregistré dans la table `transactions` (type `refund`)
**And** le client est notifié du remboursement
**And** la piste d'audit enregistre l'action admin avec horodatage

### Story 4.8: Dashboard financier talent (backend + mobile)

As a talent,
I want consulter mon dashboard financier avec revenus et historique,
So that je puisse suivre mes finances sur BookMi. (FR35)

**Acceptance Criteria:**

**Given** un talent authentifié
**When** il accède à `GET /api/v1/me/financial_dashboard`
**Then** le dashboard retourne : revenus_total, revenus_mois_courant, revenus_mois_precedent, comparaison_pourcentage, nombre_prestations, cachet_moyen
**And** `GET /api/v1/me/payouts` retourne l'historique des versements avec pagination
**And** les comparaisons mensuelles sont calculées
**And** côté Flutter, le dashboard affiche des graphiques (barres et lignes) avec le ProgressRing
**And** les montants sont formatés en FCFA avec séparateur milliers

### Story 4.9: Export rapports financiers admin (backend)

As a administrateur comptable,
I want exporter les rapports financiers,
So that je puisse effectuer la comptabilité et le reporting. (FR38)

**Acceptance Criteria:**

**Given** un administrateur avec le rôle `admin_comptable`
**When** il accède à `GET /admin/reports/financial?start_date=2026-01-01&end_date=2026-02-28&format=csv`
**Then** un fichier CSV/Excel est généré avec : transactions, commissions, versements, remboursements
**And** les données sont filtrables par période, type de transaction, statut
**And** le rapport inclut les totaux et sous-totaux
**And** l'accès est restreint aux rôles `admin_ceo` et `admin_comptable`

### Story 4.10: Écran paiement Flutter (mobile)

As a client,
I want payer depuis l'app mobile avec une expérience sécurisée et rassurante,
So that je me sente en confiance pour finaliser ma réservation.

**Acceptance Criteria:**

**Given** un client à l'étape 4 du stepper de réservation
**When** l'écran paiement s'affiche
**Then** le composant MobileMoneySelector affiche les opérateurs disponibles (Orange Money, Wave, MTN, Moov) avec leurs logos
**And** le GlassShield (effet vitre sécurisée) entoure la zone de paiement
**And** le haptic feedback se déclenche à la confirmation du paiement (UX-FEEDBACK-1)
**And** un spinner d'attente s'affiche pendant le traitement (< 15s)
**And** le CelebrationOverlay s'affiche après paiement réussi (UX-FEEDBACK-3)
**And** en cas d'erreur, un message clair en français avec suggestion d'action est affiché (UX-FEEDBACK-4)

---

## Epic 5: Communication & Notifications

**Goal:** Permettre la communication entre clients et talents via messagerie en temps réel et notifications push.
**FRs:** FR39, FR40, FR41, FR42, FR43, FR44 (6 FRs)

### Story 5.1: Messagerie interne temps réel (backend)

As a client ou talent,
I want échanger des messages en temps réel via la messagerie interne,
So that nous puissions coordonner les détails de la prestation. (FR39)

**Acceptance Criteria:**

**Given** un client et un talent liés par une réservation
**When** le client envoie `POST /api/v1/messages` avec conversation_id, content, type (text/image/audio)
**Then** le message est enregistré dans la table `messages` avec les champs : conversation_id, sender_id, content, type, read_at
**And** la table `conversations` est créée avec les champs : client_id, talent_id, booking_id, last_message_at
**And** le message est broadcasté en temps réel via Laravel Reverb sur le channel `private:conversation.{conversationId}`
**And** l'envoi et la réception s'effectuent en moins de 1 seconde (NFR6)
**And** les types supportés sont : texte, emojis, photos (upload), vocaux (upload audio)
**And** le MessagingService gère toute la logique

### Story 5.2: Détection anti-désintermédiation (backend)

As a système,
I want détecter les tentatives d'échange de coordonnées personnelles dans les messages,
So that la plateforme soit protégée contre la désintermédiation. (FR40)

**Acceptance Criteria:**

**Given** un utilisateur envoie un message dans la messagerie
**When** le middleware `DetectContactSharing` analyse le contenu
**Then** les patterns suivants sont détectés : numéros de téléphone, emails, URLs, mentions de WhatsApp/Telegram
**And** si un pattern est détecté, un avertissement éducatif (pas punitif) est retourné au sender
**And** le message est quand même envoyé (pas de blocage), mais flaggé dans la base
**And** l'approche est éducative : message expliquant pourquoi BookMi protège les transactions
**And** les détections sont loggées pour analyse admin

### Story 5.3: Réponses automatiques talent (backend)

As a talent,
I want configurer des réponses automatiques pour la messagerie,
So that les clients reçoivent une réponse immédiate même quand je suis occupé. (FR41)

**Acceptance Criteria:**

**Given** un talent authentifié
**When** il configure une réponse auto via `PUT /api/v1/me/auto_reply` avec message et is_active
**Then** tout nouveau message reçu déclenche l'envoi automatique de la réponse configurée
**And** la réponse auto n'est envoyée qu'une fois par conversation (pas de spam)
**And** la réponse auto est clairement identifiée comme automatique dans l'UI
**And** le talent peut activer/désactiver la réponse auto à tout moment

### Story 5.4: Notifications push FCM (backend)

As a utilisateur,
I want recevoir des notifications push pour les événements critiques,
So that je ne manque aucune information importante. (FR42)

**Acceptance Criteria:**

**Given** un événement critique se produit (réservation, paiement, message, rappel)
**When** l'événement est émis
**Then** une notification push est envoyée via Firebase Cloud Messaging (FCM) (NFR43)
**And** les notifications sont délivrées dans les 5 secondes (NFR10)
**And** le Job `SendPushNotification` s'exécute sur la queue `notifications` (2 workers)
**And** les types de notifications sont : booking (nouvelle demande, acceptée, annulée), payment (reçu, versé), message (nouveau), system (rappel, alerte)
**And** la table `notifications` stocke l'historique avec read_at
**And** `GET /api/v1/notifications` retourne les notifications non lues
**And** `POST /api/v1/notifications/{id}/read` marque comme lue

### Story 5.5: Rappels automatiques J-7 et J-2 (backend)

As a système,
I want envoyer des rappels automatiques avant la prestation,
So that le client et le talent soient préparés. (FR43)

**Acceptance Criteria:**

**Given** une réservation en status `confirmed` avec une date future
**When** la date de la prestation approche
**Then** un rappel push + email est envoyé à J-7 au client et au talent
**And** un rappel push + email est envoyé à J-2 au client et au talent
**And** les rappels sont planifiés via Laravel Schedule (commande artisan quotidienne)
**And** le contenu du rappel inclut : date, lieu, heure, nom de l'autre partie, checklist préparation
**And** les rappels ne sont envoyés que pour les réservations non annulées

### Story 5.6: Accès admin aux messages en cas de litige (backend)

As a administrateur,
I want accéder aux messages uniquement dans le cadre d'un litige formel,
So that la médiation soit basée sur des faits vérifiables. (FR44)

**Acceptance Criteria:**

**Given** un administrateur avec le rôle `admin_ceo` ou `admin_moderateur`
**When** il accède aux messages d'une conversation liée à un litige actif
**Then** les messages sont accessibles via `GET /admin/disputes/{dispute}/messages`
**And** l'accès est journalisé avec piste d'audit complète : qui, quand, quelle conversation (NFR19)
**And** l'accès est impossible si aucun litige actif n'est associé à la conversation
**And** un événement `AdminAccessedMessages` est émis et loggé

### Story 5.7: Écran messagerie Flutter (mobile)

As a client ou talent,
I want une messagerie type WhatsApp dans l'app mobile,
So that la communication soit naturelle et familière.

**Acceptance Criteria:**

**Given** un utilisateur authentifié sur l'onglet Messages
**When** l'écran s'affiche
**Then** la liste des conversations s'affiche avec : avatar, nom, dernier message, date, badge non lu
**And** le tap ouvre le thread de conversation avec ChatBubbles (gauche/droite selon sender)
**And** le champ de saisie supporte : texte, emojis (picker), photo (galerie/caméra), audio (enregistrement vocal)
**And** les messages arrivent en temps réel via WebSocket (laravel_echo_flutter)
**And** la reconnexion automatique WebSocket fonctionne avec backoff exponentiel
**And** les messages chargés sont disponibles hors-ligne (UX-OFFLINE-1)
**And** les indicateurs de lecture (vu) sont affichés
**And** les réponses automatiques sont visuellement distinctes (badge "Auto")

---

## Epic 6: Suivi Jour J & Évaluation

**Goal:** Permettre le suivi en temps réel de la prestation le jour J et les évaluations bilatérales post-prestation.
**FRs:** FR45, FR46, FR47, FR48, FR49, FR50, FR51 (7 FRs)

### Story 6.1: Tracker jour J temps réel (backend)

As a système,
I want suivre le statut de la prestation le jour J en temps réel,
So that le client et le talent soient informés de l'avancement. (FR45)

**Acceptance Criteria:**

**Given** une réservation en status `confirmed` dont la date est aujourd'hui
**When** le talent met à jour son statut via `POST /api/v1/booking_requests/{booking}/tracking` avec status
**Then** le statut est enregistré dans la table `tracking_events` avec : booking_id, status, latitude, longitude, timestamp
**And** les 5 statuts sont supportés : `preparing`, `en_route`, `arrived`, `performing`, `completed` (enum TrackingStatus)
**And** le changement est broadcasté en temps réel via Reverb sur `presence:tracking.{bookingId}`
**And** le TrackingService gère les transitions valides (pas de retour en arrière)
**And** un événement `TrackingStatusChanged` est émis à chaque transition

### Story 6.2: Check-in géolocalisation jour J (backend + mobile)

As a talent,
I want effectuer mon check-in le jour J avec géolocalisation,
So that le client sache que je suis en route et arrivé. (FR46)

**Acceptance Criteria:**

**Given** un talent avec une prestation prévue aujourd'hui
**When** il effectue son check-in via `POST /api/v1/booking_requests/{booking}/checkin` avec latitude et longitude
**Then** le check-in est enregistré et le statut passe à `arrived`
**And** le temps de réponse est < 2 secondes (NFR5)
**And** la position GPS est validée (précision au quartier) (NFR45)
**And** côté Flutter, le geolocator capture la position et l'envoie automatiquement
**And** un événement `TrackingStatusChanged` est broadcasté au client en temps réel

### Story 6.3: Alerte check-in manquant (backend)

As a système,
I want alerter en cas de check-in manquant ou de retard,
So that les parties soient prévenues et puissent réagir. (FR47)

**Acceptance Criteria:**

**Given** une prestation prévue aujourd'hui avec une heure de début définie
**When** le talent n'a pas effectué de check-in 30 minutes après l'heure prévue
**Then** une notification d'alerte est envoyée au client ("Votre artiste n'a pas encore effectué son check-in")
**And** une notification est envoyée au talent ("Rappel : votre prestation commence bientôt, n'oubliez pas le check-in")
**And** les alertes sont planifiées via Laravel Schedule
**And** si le statut reste `preparing` 1h après l'heure prévue, une alerte admin est émise

### Story 6.4: Évaluation talent par client (backend)

As a client,
I want évaluer un talent après la prestation,
So that les futurs clients puissent se baser sur des avis vérifiés. (FR48)

**Acceptance Criteria:**

**Given** une réservation en status `completed`
**When** le client envoie `POST /api/v1/reviews` avec booking_id, ratings (ponctualité, qualité, professionnalisme, note_globale sur 5), commentaire
**Then** l'avis est enregistré dans la table `reviews` avec : booking_id, reviewer_id, reviewee_id, ratings (JSON), comment, type (client_to_talent)
**And** la note moyenne du talent est recalculée automatiquement
**And** le score de fiabilité du talent est mis à jour
**And** un événement `ReviewSubmitted` est émis
**And** le ReviewService empêche les doublons (1 avis par booking par reviewer)
**And** seules les réservations complétées peuvent être évaluées

### Story 6.5: Évaluation client par talent (backend)

As a talent,
I want évaluer un client après la prestation,
So that les talents sachent à quoi s'attendre avec ce client. (FR49)

**Acceptance Criteria:**

**Given** une réservation en status `completed`
**When** le talent envoie `POST /api/v1/reviews` avec booking_id, ratings (respect, communication, conditions), commentaire
**Then** l'avis est enregistré avec type `talent_to_client`
**And** la note client est calculée et visible par les talents
**And** les mêmes règles anti-doublon s'appliquent
**And** les deux évaluations (client→talent et talent→client) sont indépendantes

### Story 6.6: Signalement de problème (backend)

As a client,
I want signaler un problème sur une réservation,
So que mon souci soit pris en charge par l'équipe BookMi. (FR50)

**Acceptance Criteria:**

**Given** un client avec une réservation en cours ou passée
**When** il envoie `POST /api/v1/booking_requests/{booking}/report` avec category (no_show, quality, behavior, other), description
**Then** un signalement est créé et lié à la réservation
**And** si la réservation est en escrow, le statut escrow passe à `disputed`
**And** un administrateur est notifié du signalement
**And** `GET /api/v1/me/reports` retourne l'historique des signalements du client
**And** le DisputeService gère le workflow de résolution

### Story 6.7: Enrichissement portfolio post-prestation (backend)

As a talent,
I want enrichir mon portfolio avec les photos/vidéos des prestations réalisées,
So que mon profil reflète mes expériences récentes. (FR51)

**Acceptance Criteria:**

**Given** un talent avec une réservation en status `completed`
**When** il envoie `POST /api/v1/portfolios` avec booking_id (optionnel), files (images/vidéos)
**Then** les médias sont uploadés, compressés (WebP pour images) et stockés
**And** le Job `CompressPortfolioImage` s'exécute sur la queue `media`
**And** les médias sont associés au profil talent et optionnellement à la réservation
**And** `GET /api/v1/talents/{talent}/portfolios` retourne le portfolio avec pagination
**And** `DELETE /api/v1/portfolios/{media}` permet de supprimer un média
**And** la taille max par fichier est de 10 MB (images) et 50 MB (vidéos)

### Story 6.8: Écran tracker jour J Flutter (mobile)

As a client ou talent,
I want suivre la prestation le jour J avec un tracker visuel en temps réel,
So que je sache exactement où en est la prestation.

**Acceptance Criteria:**

**Given** un utilisateur avec une prestation prévue aujourd'hui
**When** l'écran tracker s'affiche
**Then** le composant StatusTracker affiche les 5 étapes avec progression visuelle : en préparation → en route → arrivé → en cours → terminé (UX-FLOW-4)
**And** chaque étape s'anime en temps réel via WebSocket (presence channel)
**And** le talent voit un bouton pour passer à l'étape suivante (avec géolocalisation pour check-in)
**And** le client voit l'avancement en lecture seule
**And** les haptic feedback se déclenchent à chaque changement d'étape
**And** le CelebrationOverlay s'affiche quand le statut passe à `completed` (UX-FEEDBACK-3)

### Story 6.9: Écran évaluation Flutter (mobile)

As a client ou talent,
I want évaluer la prestation depuis l'app avec des suggestions de mots,
So que l'évaluation soit rapide et naturelle. (UX-FLOW-5)

**Acceptance Criteria:**

**Given** une prestation terminée (status `completed`)
**When** l'écran d'évaluation s'affiche (automatiquement ou via notification)
**Then** les étoiles de notation sont interactives (1 à 5) pour chaque critère
**And** des suggestions de mots en français sont proposées : "ponctuel", "ambiance", "professionnel", "créatif", "sympathique" (UX-FLOW-5)
**And** le tap sur une suggestion l'ajoute au commentaire
**And** un champ texte libre est disponible pour des commentaires additionnels
**And** le CelebrationOverlay s'affiche après soumission
**And** une notification de rappel est envoyée 24h après si l'évaluation n'est pas faite

---

## Epic 7: Gestion Talents & Manager

**Goal:** Permettre aux managers de gérer plusieurs talents et aux talents de suivre leur progression et analytics.
**FRs:** FR7, FR8, FR53, FR54, FR55, FR56, FR57, FR58, FR59 (9 FRs)

### Story 7.1: Assignation manager à un talent (backend)

As a talent,
I want assigner un manager à mon compte avec accès opérationnel sans visibilité financière,
So that mon manager puisse gérer mes réservations sans voir mes revenus. (FR7)

**Acceptance Criteria:**

**Given** un talent authentifié
**When** il envoie `POST /api/v1/me/manager` avec manager_email ou manager_phone
**Then** une invitation est envoyée au manager (email + push)
**And** si le manager n'a pas de compte, il est invité à s'inscrire avec le rôle `manager`
**And** le manager accepte via `POST /api/v1/manager_invitations/{id}/accept`
**And** le rôle `manager` est assigné via Spatie Permission avec le guard `api`
**And** le manager a accès aux réservations, calendrier, messagerie du talent
**And** le manager n'a PAS accès au dashboard financier, revenus, versements
**And** le talent peut révoquer le manager via `DELETE /api/v1/me/manager`

### Story 7.2: Interface unifiée manager multi-talents (backend)

As a manager,
I want gérer les comptes de plusieurs talents depuis une interface unifiée,
So that je puisse administrer tous mes artistes efficacement. (FR8)

**Acceptance Criteria:**

**Given** un manager authentifié avec au moins un talent assigné
**When** il accède à `GET /api/v1/manager/talents`
**Then** la liste de ses talents est retournée avec : nom de scène, catégorie, nombre réservations en cours, prochaine prestation
**And** `GET /api/v1/manager/talents/{talent}/bookings` retourne les réservations d'un talent
**And** le manager peut switch entre les contextes de ses talents
**And** les notifications du manager agrègent les événements de tous ses talents
**And** la pagination fonctionne sur toutes les listes

### Story 7.3: Alertes surcharge talent (backend)

As a talent,
I want configurer des alertes de surcharge quand j'ai trop de prestations,
So que je ne m'épuise pas et que la qualité reste constante. (FR53)

**Acceptance Criteria:**

**Given** un talent authentifié
**When** il configure via `PUT /api/v1/me/overload_settings` avec max_bookings_per_week et max_bookings_per_month
**Then** les seuils sont enregistrés
**And** quand le nombre de réservations confirmées atteint 80% du seuil, une alerte est envoyée au talent
**And** quand le seuil est atteint (100%), le calendrier bloque automatiquement les nouvelles réservations
**And** le CalendarService vérifie les seuils avant de permettre une nouvelle réservation

### Story 7.4: Gestion calendrier par le manager (backend)

As a manager,
I want consulter et gérer le calendrier de mes talents,
So que je puisse organiser leurs disponibilités. (FR54)

**Acceptance Criteria:**

**Given** un manager authentifié
**When** il accède à `GET /api/v1/manager/talents/{talent}/calendar?month=2026-03`
**Then** le calendrier du talent s'affiche avec disponibilités et réservations
**And** le manager peut créer/modifier/supprimer des créneaux via les endpoints calendrier existants (Story 3.1)
**And** la Policy vérifie que le manager est bien assigné au talent
**And** une vue consolidée de tous les talents est disponible via `GET /api/v1/manager/calendar_overview`

### Story 7.5: Validation réservation par le manager (backend)

As a manager,
I want valider ou refuser des demandes de réservation au nom de mes talents,
So que je puisse gérer les demandes sans attendre le talent. (FR55)

**Acceptance Criteria:**

**Given** un manager authentifié avec des réservations en attente pour ses talents
**When** il envoie `POST /api/v1/booking_requests/{booking}/accept` ou `/reject`
**Then** la Policy autorise l'action si le manager est assigné au talent de la réservation
**And** les mêmes effets que l'action du talent s'appliquent (Story 3.3)
**And** l'action est loggée avec mention "via manager {manager_name}"
**And** le talent est notifié de l'action prise par son manager

### Story 7.6: Messages manager au nom du talent (backend)

As a manager,
I want répondre aux messages clients au nom de mes talents,
So que la communication reste fluide même quand le talent est occupé. (FR56)

**Acceptance Criteria:**

**Given** un manager authentifié
**When** il envoie `POST /api/v1/messages` avec conversation_id d'une conversation de son talent
**Then** le message est envoyé au nom du talent (le client ne voit pas que c'est le manager)
**And** la Policy vérifie que la conversation appartient à un talent du manager
**And** le message est tagué en interne comme envoyé par le manager (visible talent + admin uniquement)
**And** le manager peut accéder aux conversations de ses talents via `GET /api/v1/manager/talents/{talent}/conversations`

### Story 7.7: Niveaux automatiques talent (backend)

As a système,
I want attribuer automatiquement un niveau au talent basé sur son activité,
So que les talents soient motivés à maintenir une bonne qualité. (FR57)

**Acceptance Criteria:**

**Given** un talent avec de l'activité sur la plateforme
**When** le Job `RecalculateTalentLevels` s'exécute (quotidiennement via Schedule)
**Then** le niveau du talent est recalculé : Nouveau (0-5 prestations), Confirmé (6-20, note ≥ 3.5), Premium (21-50, note ≥ 4.0), Elite (51+, note ≥ 4.5)
**And** le TalentLevelService applique les critères combinés (nombre de prestations + note moyenne)
**And** un événement `TalentLevelUpgraded` est émis en cas de changement de niveau
**And** le badge de niveau est visible sur le profil public du talent
**And** un CelebrationOverlay s'affiche côté Flutter lors d'une montée de niveau

### Story 7.8: Analytics talent (backend + mobile)

As a talent,
I want consulter mes analytics (vues du profil, villes, tendances),
So que je comprenne ma visibilité et adapte ma stratégie. (FR58)

**Acceptance Criteria:**

**Given** un talent authentifié
**When** il accède à `GET /api/v1/me/analytics`
**Then** les données retournées incluent : vues_profil_semaine, vues_profil_mois, top_villes (d'où viennent les consultations), tendance_vues (hausse/baisse %), nombre_favoris
**And** les vues sont comptabilisées de manière anonyme (compteur incrémenté par IP unique/jour)
**And** côté Flutter, l'écran analytics affiche des graphiques clairs avec les tendances
**And** les données sont mises à jour quotidiennement (pas en temps réel)

### Story 7.9: Attestation de revenus annuelle (backend)

As a talent,
I want recevoir une attestation de revenus annuelle,
So que je puisse justifier mes revenus pour des démarches administratives. (FR59)

**Acceptance Criteria:**

**Given** un talent ayant eu des prestations sur l'année civile
**When** il envoie `GET /api/v1/me/revenue_certificate?year=2026`
**Then** un PDF est généré avec : nom du talent, période, total des revenus perçus, nombre de prestations, détail par mois
**And** le PDF est généré côté serveur via DomPDF
**And** le document porte le logo BookMi et les mentions légales
**And** le téléchargement est disponible uniquement pour les années passées (pas l'année en cours avant le 31 décembre)

### Story 7.10: Onboarding gamifié talent Flutter (mobile)

As a nouveau talent,
I want compléter mon profil via un onboarding gamifié en 5 étapes,
So que je sois guidé pour créer un profil attractif. (UX-FLOW-2)

**Acceptance Criteria:**

**Given** un talent nouvellement inscrit avec un profil incomplet
**When** il accède à son profil
**Then** une barre circulaire de progression (ProgressRing) affiche le % de complétion (20% → 100%)
**And** les 5 étapes sont : 1. Photo de profil (20%), 2. Bio + catégorie (40%), 3. Package de prestation (60%), 4. Portfolio (80%), 5. Disponibilités calendrier (100%)
**And** chaque étape complétée déclenche une animation de progression
**And** le CelebrationOverlay s'affiche à 100% ("Profil complet !")
**And** un badge "Profil complété" est attribué
**And** les étapes peuvent être complétées dans n'importe quel ordre

---

## Epic 8: Administration & Gouvernance

**Goal:** Fournir aux administrateurs les outils de gouvernance, modération et suivi de la plateforme.
**FRs:** FR60, FR61, FR62, FR63, FR64, FR65, FR66, FR67, FR68, FR69, FR70, FR71, FR72 (13 FRs)

### Story 8.1: Dashboard admin temps réel (backend + web)

As a administrateur,
I want consulter les dashboards en temps réel (financier, opérationnel, qualité),
So que je puisse piloter la plateforme efficacement. (FR60)

**Acceptance Criteria:**

**Given** un administrateur authentifié (session web) avec le rôle `admin_ceo`
**When** il accède à `/admin/dashboard`
**Then** le dashboard affiche en temps réel : CA du jour/semaine/mois, nombre de réservations (par statut), nombre d'inscriptions, taux de litiges, top talents (par revenus), alertes actives
**And** l'interface est en Blade + Tailwind CSS, responsive desktop-first (UX-RESPONSIVE-2)
**And** les données se rafraîchissent automatiquement (polling 30s ou Reverb)
**And** les widgets sont organisés en grille 12 colonnes avec sidebar 256px collapsible
**And** l'accès est protégé par middleware `auth:web` + permission admin

### Story 8.2: Gestion des litiges avec traçabilité (backend + web)

As a administrateur,
I want gérer les litiges avec un rapport de traçabilité horodaté,
So que chaque décision soit documentée et justifiable. (FR61)

**Acceptance Criteria:**

**Given** un administrateur sur la page `/admin/disputes`
**When** il consulte un litige
**Then** le détail inclut : réservation, parties, messages échangés (si autorisé), timeline des événements, paiement associé, escrow status
**And** l'admin peut ajouter des notes internes horodatées
**And** l'admin peut résoudre le litige : en faveur client (remboursement), en faveur talent (versement), compromis (montant partiel)
**And** chaque action génère une entrée dans la table `activity_logs` avec : admin_id, action, details, ip, timestamp
**And** un rapport PDF de traçabilité est générable pour chaque litige
**And** le DisputeService gère le workflow complet

### Story 8.3: Avertissement formel et suspension (backend + web)

As a administrateur,
I want émettre un avertissement formel ou suspendre un compte,
So que les utilisateurs problématiques soient gérés. (FR62, FR63)

**Acceptance Criteria:**

**Given** un administrateur sur la page `/admin/users/{user}`
**When** il émet un avertissement via le formulaire dédié
**Then** un avertissement formel est créé avec : reason, details, date, admin_id
**And** l'utilisateur reçoit une notification avec le motif de l'avertissement
**And** l'admin peut suspendre un compte via `POST /admin/users/{user}/suspend` avec reason et duration (temporaire ou permanente)
**And** un utilisateur suspendu ne peut plus se connecter (token révoqué, login bloqué)
**And** le profil talent suspendu est masqué de l'annuaire
**And** toutes les actions sont loggées dans `activity_logs`

### Story 8.4: Signalement automatique talents note basse (backend)

As a système,
I want signaler automatiquement les talents dont la note passe sous un seuil,
So que la qualité de la plateforme soit maintenue. (FR64)

**Acceptance Criteria:**

**Given** un talent dont la note moyenne est recalculée après un avis
**When** la note passe sous le seuil défini (configurable dans `config/bookmi.php`, default 3.0)
**Then** une alerte est créée dans le dashboard admin
**And** l'administrateur est notifié (push + email)
**And** le talent est notifié avec un message éducatif et des suggestions d'amélioration
**And** si la note reste sous le seuil après 5 prestations supplémentaires, une suspension automatique temporaire est proposée à l'admin

### Story 8.5: Détection comportements suspects (backend)

As a système,
I want détecter et signaler les comportements suspects,
So que la fraude et les abus soient prévenus. (FR65)

**Acceptance Criteria:**

**Given** l'activité de la plateforme est monitorée
**When** un comportement suspect est détecté
**Then** une alerte est créée dans le dashboard admin avec détails
**And** les patterns détectés incluent : doublons d'identité (même CNI pour deux comptes), transactions anormales (montants inhabituels, fréquence élevée), inscriptions multiples (même IP/device en peu de temps)
**And** la détection s'exécute via un Job quotidien `DetectSuspiciousActivity`
**And** les alertes sont classées par sévérité (info, warning, critical)
**And** l'admin peut marquer une alerte comme résolue ou faux positif

### Story 8.6: Délégation tâches admin CEO (backend + web)

As a administrateur CEO,
I want déléguer des tâches spécifiques à mes collaborateurs,
So que chaque membre de l'équipe ait un périmètre clair. (FR66)

**Acceptance Criteria:**

**Given** un admin CEO authentifié
**When** il accède à `/admin/team`
**Then** il peut créer des comptes collaborateurs avec rôles spécifiques : `admin_comptable`, `admin_controleur`, `admin_moderateur`
**And** chaque rôle a des permissions spécifiques via Spatie Permission :
**And** `admin_comptable` : accès financier (rapports, versements, exports) — pas de modération
**And** `admin_controleur` : accès opérationnel (check-ins, prestations en cours, calendrier) — pas de finance
**And** `admin_moderateur` : accès modération (litiges, avis, messages, avertissements) — pas de finance
**And** le CEO peut modifier les rôles et révoquer les accès
**And** chaque modification est loggée dans `activity_logs`

### Story 8.7: Export données financières comptable (backend + web)

As a administrateur comptable,
I want consulter et exporter les données financières,
So que la comptabilité soit tenue à jour. (FR67)

**Acceptance Criteria:**

**Given** un admin comptable authentifié
**When** il accède à `/admin/finance`
**Then** il voit : revenus totaux, commissions BookMi, versements effectués, remboursements, solde escrow en cours
**And** les données sont filtrables par période (jour, semaine, mois, personnalisé)
**And** l'export CSV/Excel est disponible avec colonnes : date, type, montant, talent, client, statut, reference
**And** les rapports TVA sont générables (commission 15% × TVA 18%)
**And** l'accès est restreint aux rôles `admin_ceo` et `admin_comptable`

### Story 8.8: Suivi check-ins contrôleur opérationnel (backend + web)

As a contrôleur opérationnel,
I want suivre les check-ins et les prestations en cours,
So que je puisse intervenir rapidement en cas de problème. (FR68)

**Acceptance Criteria:**

**Given** un admin contrôleur authentifié
**When** il accède à `/admin/operations`
**Then** il voit la liste des prestations du jour avec leur statut de tracking en temps réel
**And** les prestations sont triées par statut : en retard (rouge), en cours (vert), à venir (bleu), terminées (gris)
**And** le tap sur une prestation affiche le détail : talent, client, lieu, timeline tracking
**And** une alerte visuelle apparaît pour les check-ins manquants (> 30 min de retard)
**And** l'accès est restreint aux rôles `admin_ceo` et `admin_controleur`

### Story 8.9: Modération des avis (backend + web)

As a modérateur,
I want examiner et décider sur les avis signalés comme inappropriés,
So que la qualité des avis soit maintenue. (FR69)

**Acceptance Criteria:**

**Given** un admin modérateur authentifié
**When** il accède à `/admin/reviews/reported`
**Then** la liste des avis signalés est affichée avec : contenu, reviewer, reviewee, date, raison du signalement
**And** le modérateur peut : approuver (maintenir l'avis), supprimer (retirer l'avis), éditer (masquer le contenu inapproprié)
**And** chaque décision est loggée avec la raison dans `activity_logs`
**And** le reviewer est notifié si son avis est supprimé avec la raison
**And** un utilisateur peut signaler un avis via `POST /api/v1/reviews/{review}/report` avec reason

### Story 8.10: Piste d'audit complète (backend)

As a système,
I want maintenir une piste d'audit complète pour toutes les actions administratives,
So que la transparence et la conformité soient garanties. (FR70)

**Acceptance Criteria:**

**Given** un administrateur effectue une action (n'importe laquelle)
**When** l'action est exécutée
**Then** une entrée est créée dans `activity_logs` avec : user_id, action, model_type, model_id, old_values (JSON), new_values (JSON), ip_address, user_agent, created_at
**And** l'AuditService enregistre automatiquement via un Listener global `LogActivityEvent`
**And** les logs d'audit sont consultables via `/admin/audit` avec filtres (user, action, période, model)
**And** les logs ne sont jamais modifiables ni supprimables (append-only)
**And** la rétention est de 5 ans (conformité loi 2013-450)

### Story 8.11: Relances automatiques admin (backend)

As a système,
I want envoyer des relances automatiques pour les actions administratives en attente,
So que rien ne reste en suspens trop longtemps. (FR71)

**Acceptance Criteria:**

**Given** des actions administratives sont en attente (vérifications identité, litiges, avis signalés)
**When** une action est en attente depuis plus de 48h
**Then** une relance est envoyée à l'administrateur assigné (ou au CEO si non assigné)
**And** une deuxième relance est envoyée à 72h
**And** à 96h, une escalade automatique est envoyée au CEO
**And** les relances sont planifiées via Laravel Schedule (commande quotidienne)
**And** le dashboard affiche le nombre d'actions en attente avec badge d'urgence

### Story 8.12: KPIs plateforme (backend + web)

As a administrateur,
I want consulter les KPIs de la plateforme,
So que je puisse mesurer la performance globale de BookMi. (FR72)

**Acceptance Criteria:**

**Given** un admin CEO authentifié
**When** il accède à `/admin/kpis`
**Then** les KPIs affichés sont : inscriptions (total, cette semaine, ce mois), réservations (total, taux de conversion demande→paiement), taux de litiges (%), CA total et par période, note moyenne plateforme, taux de rétention talents
**And** les KPIs sont comparés au mois précédent (tendance hausse/baisse)
**And** les graphiques de tendance sont disponibles (30j, 90j, 12 mois)
**And** l'export des KPIs en CSV est disponible

### Story 8.13: Monitoring et logs (backend)

As a développeur/administrateur,
I want un système de monitoring et de logs centralisé,
So que les problèmes soient détectés et résolus rapidement. (ARCH-MONITOR-1/2/3)

**Acceptance Criteria:**

**Given** l'application est en production
**When** une erreur ou un événement important se produit
**Then** Sentry capture l'erreur avec stack trace et contexte utilisateur (Laravel + Flutter)
**And** Laravel Telescope est disponible en dev pour le debug (requêtes, jobs, events, queries)
**And** Laravel Horizon dashboard est disponible en prod pour le monitoring des queues
**And** les logs JSON structurés (Monolog) sont écrits avec rotation quotidienne et rétention 90j (NFR51)
**And** les alertes critiques (queue failed, payment error, high error rate) sont envoyées sur Slack/email
**And** les sauvegardes automatiques Spatie sont configurées toutes les 6h avec alertes en cas d'échec (ARCH-INFRA-4)
