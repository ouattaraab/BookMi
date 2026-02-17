---
stepsCompleted: [step-01-init, step-02-discovery, step-03-success, step-04-journeys, step-05-domain, step-06-innovation, step-07-project-type, step-08-scoping, step-09-functional, step-10-nonfunctional, step-11-polish]
inputDocuments:
  - '_bmad-output/planning-artifacts/product-brief-BookMi_v2-2026-02-16.md'
  - '_bmad-output/analysis/brainstorming-session-2026-02-16.md'
workflowType: 'prd'
briefCount: 1
researchCount: 0
brainstormingCount: 1
projectDocsCount: 0
classification:
  projectType: 'multi-platform (web-app + mobile-app) - marketplace'
  domain: 'marketplace-ecommerce-fintech'
  complexity: 'medium-high'
  projectContext: 'greenfield'
---

# Product Requirements Document - BookMi_v2

**Author:** Aboubakarouattara
**Date:** 2026-02-16

## Résumé Exécutif

**BookMi** est la première marketplace digitale de réservation de talents en Côte d'Ivoire. Dans un marché où la réservation d'artistes passe à 100% par WhatsApp, Facebook et bouche-à-oreille — avec les risques d'arnaque, d'opacité financière et d'absence de garantie que cela implique — BookMi crée un écosystème de confiance structuré.

**Proposition de valeur unique :**
- **Pour les clients :** Trouver, vérifier et réserver un talent en toute sécurité avec escrow Mobile Money, contrat automatique et garantie de remboursement.
- **Pour les talents :** Recevoir 100% de leur cachet (modèle "cachet intact"), avec dashboard financier transparent et protection contre la fraude des managers.
- **Pour le marché :** Digitaliser et structurer un secteur informel, en devenant le standard de la réservation de talents en Afrique de l'Ouest.

**Différenciateurs clés :** Cachet intact (commission facturée au client, pas au talent), anti-fraude manager intégré (rôle manager sans visibilité financière), communication cloisonnée totale, escrow adapté au Mobile Money.

**Stack technique :** Laravel (API + backoffice web) + Flutter (mobile iOS/Android cross-platform) + Paystack/CinetPay (paiement Mobile Money + carte).

**Cible MVP :** 200 talents, 500 clients, 50 réservations/semaine à 3 mois post-lancement.

## Success Criteria

### User Success

**Côté Client :**
- Le client trouve un talent adapté à son besoin en moins de 5 minutes de recherche (filtres + profils riches)
- Le client complète une réservation (de la recherche au paiement) en moins de 10 minutes
- Le client se sent protégé : zéro risque d'arnaque grâce au badge vérifié, escrow, et contrat automatique
- Le client revient réserver une 2ème fois (taux de retour ≥ 30% à 6 mois — standard Airbnb ~25-30%)
- Note moyenne des avis ≥ 4,5/5 étoiles
- Le client recommande BookMi à son entourage (NPS ≥ 40 — standard marketplaces performantes)

**Côté Talent :**
- L'artiste reçoit sa première demande de réservation dans les 30 premiers jours après inscription
- L'artiste touche 100% de son cachet — le modèle "cachet intact" fonctionne sans friction
- L'artiste consulte son dashboard financier au moins 1 fois par semaine (engagement outil)
- L'artiste ne revient pas à l'ancien système WhatsApp/manager informel (rétention ≥ 80% à 12 mois — standard Fiverr ~75-85%)
- L'artiste fait la promotion de BookMi sur ses réseaux sociaux (taux de promotion organique ≥ 20%)

**Côté Administrateur :**
- Le CEO a une vision claire de la santé de la plateforme en moins de 30 secondes (dashboard temps réel)
- Les litiges sont résolus en moins de 48h (standard Airbnb/Fiverr : 24-72h)
- Les collaborateurs (Comptable, Contrôleur, Modérateur) opèrent de manière autonome via leurs rôles dédiés

### Business Success

**Objectifs à 3 mois (lancement) :**

| Indicateur | Cible | Benchmark |
|---|---|---|
| Talents inscrits | ≥ 200 | Standard marketplace CI lancement |
| Clients actifs | ≥ 500 | Ratio 2,5 clients/talent |
| Réservations/semaine | ≥ 50 | ~10% conversion base active |
| Taux de conversion visiteur → inscription | ≥ 15% | Standard marketplace : 10-20% |
| Taux de conversion inscription → 1ère réservation | ≥ 25% | Standard Fiverr/Upwork : 20-30% |
| Note moyenne des avis | ≥ 4,5/5 | Standard Airbnb : 4,7/5 |
| Taux de litiges | < 5% | Standard marketplace : 2-5% |

**Objectifs à 12 mois (croissance) :**

| Indicateur | Cible | Benchmark |
|---|---|---|
| Talents inscrits | ≥ 500 | Croissance organique + terrain |
| Clients actifs | ≥ 5 000 | Ratio 10 clients/talent |
| Réservations/semaine | ≥ 200 | ~4% conversion base active |
| Taux de rétention talents | ≥ 80% | Standard Fiverr : 75-85% |
| Taux de rétention clients | ≥ 60% | Standard Airbnb : 55-65% |
| CA mensuel commissions | Croissance ≥ 15%/mois | Standard marketplace early-stage |

### Technical Success

| Domaine | Métrique clé | Cible | Benchmark |
|---|---|---|---|
| Performance | Chargement pages | < 3s (LCP < 2,5s) | Google Core Web Vitals |
| Performance | Réponse API | < 500ms standard, < 1s recherche | Standard marketplace |
| Performance | Paiement Mobile Money | < 15s | Paystack/Wave API |
| Performance | Check-in jour J | < 2s | Uber temps réel |
| Disponibilité | Uptime global | ≥ 99,5% | Standard marketplace |
| Disponibilité | Uptime critique (ven-sam) | ≥ 99,9% | Standard événementiel |
| Sécurité | Fuites de données | Zéro | PCI DSS |
| Paiement | Versement talent | < 24h post-confirmation | Airbnb |
| Paiement | Auto-confirmation | 48h sans réponse client | Fiverr (3j) |
| Paiement | Défaut de paiement | < 2% | Fintech africaine |
| Scalabilité | Lancement | 1 000 simultanés | Pic samedi soir |
| Scalabilité | Horizon 12 mois | 10 000 simultanés | Sans refonte |

> Les spécifications techniques détaillées sont documentées dans la section **Non-Functional Requirements** (NFR1-NFR52).

### Measurable Outcomes

| Outcome | Métrique | Cible MVP (3 mois) | Cible Croissance (12 mois) |
|---|---|---|---|
| Adoption | Utilisateurs actifs mensuels | 700 (200T + 500C) | 5 500 (500T + 5000C) |
| Transaction | Réservations complétées/semaine | 50 | 200 |
| Qualité | Note moyenne globale | ≥ 4,5/5 | ≥ 4,5/5 |
| Confiance | Taux de litiges | < 5% | < 3% |
| Revenue | CA commissions mensuel | Mesurable dès M1 | Croissance ≥ 15%/mois |
| Rétention | Talents actifs après 3 mois | ≥ 70% | ≥ 80% |
| Satisfaction | NPS | ≥ 30 | ≥ 40 |
| Performance | Uptime | ≥ 99,5% | ≥ 99,9% |

## Product Scope

Le périmètre du produit est structuré en 3 phases de développement. Le MVP couvre les 3 espaces (Client, Talent, Administrateur) sur Web et Mobile, centrés sur la boucle de confiance complète : `Recherche → Réservation → Paiement (escrow) → Prestation → Versement → Évaluation`.

| Phase | Nom | Horizon | Focus |
|---|---|---|---|
| Phase 1 | **MVP** | Lancement | Boucle complète marketplace + confiance |
| Phase 2 | **Engagement** | 6-12 mois | Feed, stories, UGC, fidélité, paiement fractionné |
| Phase 3 | **Expansion** | 12-24 mois | Multi-pays, multi-devise, talents transfrontaliers |

**Vision long terme :** BookMi devient l'écosystème de référence pour la culture et l'événementiel en Afrique — une plateforme où l'on découvre, suit, réserve et évalue les talents au quotidien.

> Le détail complet du périmètre MVP, des features post-MVP et des simplifications acceptées est documenté dans la section **Project Scoping & Développement Phasé**.

## User Journeys

### Journey 1 : Aminata — Réservation réussie (Happy Path)

**Persona :** Aminata Koné, 32 ans, professionnelle à Abidjan. Sa petite sœur se marie dans 3 mois. Budget : 500 000 FCFA.

**Opening Scene :**
Aminata est stressée. Elle cherche un artiste depuis deux semaines — sur Facebook, elle a trouvé 3 pages pour le même chanteur sans savoir laquelle est vraie. Un soi-disant "manager" lui a demandé 200 000 FCFA d'avance par Orange Money, sans contrat, sans garantie. Elle a failli payer. Son amie Fatou lui envoie un message : "Essaie BookMi, j'ai réservé pour mon anniversaire et c'était parfait."

**Rising Action :**
1. Aminata télécharge l'app. Inscription en 2 minutes — personne physique, email, numéro de téléphone.
2. Elle explore l'annuaire. Filtre : "Musique live", budget "300K-600K FCFA", localisation "Abidjan". 12 résultats apparaissent.
3. Elle clique sur le profil de Serge Le Magnifique — badge "Vérifié" affiché. Vidéos de 3 mariages précédents, 47 avis clients, note 4,8/5, score de fiabilité "Excellent". Elle regarde les vidéos, lit les avis. Certains mentionnent "ponctuel", "a mis l'ambiance pendant 3 heures".
4. Elle consulte les packages : Essentiel (artiste seul, 1h30 — 350K), Standard (artiste + 2 danseurs, 2h — 480K), Premium (artiste + 4 danseurs + animation, 3h — 700K). Elle choisit Standard.
5. Le devis détaillé s'affiche : Cachet artiste 480 000 FCFA + Frais BookMi 72 000 FCFA (15%) = **Total 552 000 FCFA**. Chaque ligne est transparente.
6. Elle envoie la demande de réservation avec la date, le lieu (Salle des Fêtes Cocody), et un message : "C'est pour le mariage de ma petite sœur, on veut une ambiance coupé-décalé !"

**Climax :**
7. 4 heures plus tard — notification push : "Serge Le Magnifique a accepté votre réservation !" Le cœur d'Aminata s'allège. Elle paie via Orange Money — 552 000 FCFA. L'argent est en séquestre. Le contrat automatique s'affiche avec les conditions : date, heure, lieu, durée, politique d'annulation. Elle le télécharge en PDF.
8. J-7 : notification de rappel. J-2 : "Votre prestation est dans 2 jours. Serge a confirmé sa présence."
9. Jour J, 16h : notification "Serge est en préparation". 17h30 : "Serge est en route". 18h : "Serge est arrivé à la Salle des Fêtes Cocody." Aminata sourit — pas de stress, elle sait qu'il est là.
10. La prestation est un succès. Les invités dansent, sa petite sœur est ravie.

**Resolution :**
11. J+1 : BookMi demande l'évaluation. Aminata note Serge : Ponctualité 5/5, Qualité 5/5, Professionnalisme 5/5. Commentaire : "Serge a mis le feu ! Toute la famille a dansé. Je recommande à 200%."
12. Serge reçoit son cachet de 480 000 FCFA sur son Orange Money sous 24h.
13. Aminata suit 3 autres artistes sur BookMi pour le baptême de son fils dans 6 mois. Elle envoie le lien à ses collègues : "Plus jamais Facebook pour chercher un artiste."

**Capabilities révélées :** Inscription, recherche/filtres, profils vérifiés, packages, devis transparent, réservation, paiement Mobile Money, escrow, contrat auto, notifications, check-in jour J, évaluation bilatérale, suivi artistes.

---

### Journey 2 : Aminata — L'artiste ne se présente pas (Edge Case : Litige)

**Persona :** Même Aminata, 2 mois plus tard. Elle réserve un DJ pour la soirée d'anniversaire d'une amie. Budget : 200 000 FCFA.

**Opening Scene :**
Aminata a confiance en BookMi. Elle réserve DJ Lamine, noté 4,2/5 avec 8 avis. Package Essentiel : 180K + 27K frais = 207 000 FCFA. Paiement effectué, contrat généré. Tout semble en ordre.

**Rising Action :**
1. J-2 : Notification de rappel envoyée. DJ Lamine ne confirme pas sa présence.
2. Jour J, 18h : Le check-in passe à "En retard". Aucun statut de DJ Lamine. Aminata envoie un message via la messagerie BookMi : "Où êtes-vous ? La soirée commence dans 1h !" Pas de réponse.
3. 19h : Toujours rien. Aminata appuie sur le bouton "Signaler un problème" disponible sur l'écran de sa réservation.

**Climax :**
4. Le ticket de litige est créé automatiquement. L'admin reçoit une alerte immédiate : "Talent absent jour J — réservation #2847."
5. L'admin Koné ouvre le rapport de traçabilité : chronologie horodatée montrant que DJ Lamine n'a effectué aucun check-in, n'a pas répondu au message, et n'a pas confirmé à J-2. Les faits sont irréfutables.
6. L'admin contacte DJ Lamine via la plateforme. Pas de réponse dans les 2 heures. Décision prise : **remboursement intégral** des 207 000 FCFA à Aminata.

**Resolution :**
7. Aminata reçoit la notification : "Votre remboursement de 207 000 FCFA a été initié. Vous le recevrez sur votre Orange Money sous 24h." L'argent revient.
8. DJ Lamine reçoit un avertissement formel. Son score de fiabilité chute. Une note interne est ajoutée à son dossier. S'il récidive, suspension automatique.
9. Aminata est frustrée par DJ Lamine, mais pas par BookMi. Elle se dit : "Heureusement que j'avais la garantie. Sur Facebook, j'aurais perdu 200 000 FCFA sans rien pouvoir faire."
10. Elle réserve un autre DJ pour la prochaine fois — sur BookMi, évidemment. Cette fois, elle vérifie que le score de fiabilité est "Excellent".

**Capabilities révélées :** Check-in manquant, signalement problème, workflow litige, rapport traçabilité, décision admin, remboursement automatique, système d'alerte précoce, pénalité talent, score de fiabilité.

---

### Journey 3 : DJ Kerozen — De l'inscription au moment "aha" (Happy Path)

**Persona :** DJ Kerozen, artiste coupé-décalé populaire, Abidjan. 8-12 prestations/mois. Son manager gère tout via WhatsApp.

**Opening Scene :**
Kerozen voit des publications sur Instagram : des artistes BookMi partagent leur profil vérifié avec un badge bleu. Ses fans commencent à lui demander : "T'es sur BookMi ?" Son manager Moussa lui dit que d'autres artistes reçoivent des réservations via la plateforme. Kerozen décide de s'inscrire.

**Rising Action :**
1. Il s'inscrit : artiste solo, catégorie "Musique / Coupé-décalé". Il remplit son profil — bio, liens Instagram/TikTok, uploads de 5 vidéos de ses meilleures prestations.
2. Il soumet sa CNI pour vérification. 24h plus tard : badge "Vérifié" activé. Il reçoit un lien unique `bookmi.ci/dj-kerozen` — qu'il partage immédiatement sur tous ses réseaux. "C'est mon profil officiel. Les autres pages sont fausses."
3. Il crée ses packages : Essentiel (1h30, solo — 800K), Standard (2h + 2 danseurs — 1,2M), Premium (3h + show complet — 2M). Plus des micro-prestations : vidéo anniversaire personnalisée (50K), dédicace audio (25K).
4. Il configure son calendrier : bloque les lundis (repos) et les dates de ses tournées. Active les alertes de surcharge (max 15 prestations/mois).
5. Il assigne Moussa comme manager. Moussa peut voir les demandes, valider/refuser, répondre aux messages — mais ne voit PAS les montants.

**Climax :**
6. Première semaine : 3 demandes de réservation arrivent via BookMi. Moussa en valide 2 selon le calendrier.
7. Première prestation BookMi réussie. Le client paie 1 380 000 FCFA (1,2M cachet + 180K frais). Kerozen reçoit exactement 1 200 000 FCFA sur son Wave — sous 24h. Pas de "négociation" de Moussa, pas de pourcentage obscur. Le cachet est intact.
8. **Le moment "aha"** : Kerozen ouvre son dashboard financier. Il voit : revenus du mois en cours, comparaison avec le mois précédent, nombre de vues sur son profil (2 300 en une semaine), les villes qui le recherchent le plus (Abidjan 68%, Yamoussoukro 15%, Bouaké 12%). Il réalise : "Avant, Moussa me disait que les clients payaient 800K. Maintenant je vois que c'est 1,2M. Et je touche tout."
9. Fin du premier mois : 8 réservations via BookMi. Dashboard : 7,8M FCFA de revenus, note moyenne 4,9/5, niveau passé de "Nouveau" à "Confirmé" — sa visibilité augmente dans les résultats de recherche.

**Resolution :**
10. Kerozen poste sur Instagram : "Fini les fausses pages ! Réservez-moi uniquement sur BookMi." 15 000 likes. BookMi gagne 200 téléchargements en 24h grâce à son post.
11. Son portfolio s'enrichit après chaque prestation — les clients ajoutent des photos et il valide. Son profil devient un showreel vivant.
12. Il dit à ses collègues artistes : "Inscris-toi, ton cachet est intact et tu vois tout."

**Capabilities révélées :** Inscription talent, profil riche, vérification identité, lien unique partageable, packages/micro-prestations, calendrier intelligent, rôle Manager (sans finance), dashboard financier, anti-fraude, versement multi-canal, niveaux, analytics, portfolio.

---

### Journey 4 : DJ Kerozen — Annulation tardive par le client (Edge Case)

**Persona :** Même DJ Kerozen, 3 mois après son inscription. Réservation confirmée pour un mariage samedi prochain.

**Opening Scene :**
Kerozen a une réservation confirmée : mariage à Cocody, samedi, package Premium 2M FCFA. Le contrat est signé, le paiement en séquestre. Il a refusé une autre demande pour cette date.

**Rising Action :**
1. Mercredi (J-3) : Le client envoie un message via BookMi : "Désolé, le mariage est reporté. Je dois annuler."
2. La politique d'annulation s'applique automatiquement. J-3 est entre J-7 et J-2 : **remboursement 50%** au client, **50% versé à l'artiste**.
3. Le client voit la notification : "Selon la politique d'annulation, un remboursement de 50% (1 150 000 FCFA) sera effectué. 50% du cachet (1 000 000 FCFA) sera versé à DJ Kerozen pour compenser le créneau perdu."
4. Le client n'est pas content et demande un remboursement intégral via le bouton "Demander une médiation".

**Climax :**
5. L'admin Koné ouvre le dossier de médiation. Il voit : réservation confirmée il y a 3 semaines, paiement effectué, contrat signé, annulation à J-3.
6. Koné propose une solution : "Nous pouvons reporter la réservation à la nouvelle date du mariage sans frais supplémentaires, si DJ Kerozen est disponible." Il contacte Kerozen via la plateforme.
7. Kerozen vérifie son calendrier — la nouvelle date est libre. Il accepte le report.
8. Le client accepte : la réservation est reportée, le séquestre est maintenu, aucune perte pour personne.

**Resolution :**
9. Le mariage a lieu à la nouvelle date. Prestation réussie.
10. Le client évalue : 5/5. Il ajoute : "L'équipe BookMi a été réactive pour le report. Merci."
11. Kerozen n'a rien perdu. Son calendrier reflète le changement. La politique d'annulation a servi de levier de négociation, et la médiation humaine a trouvé une solution gagnant-gagnant.

**Capabilities révélées :** Politique d'annulation graduée automatique, médiation exceptionnelle, report de réservation, communication cloisonnée, gestion de conflit, flexibilité du calendrier.

---

### Journey 5 : Admin Koné — Journée type de gestion (Admin/Opérations)

**Persona :** Koné, CEO et fondateur de BookMi. La plateforme a 200 talents, 1 500 clients, 50 réservations/semaine.

**Opening Scene :**
Lundi matin, 8h. Koné ouvre le backoffice BookMi sur son ordinateur. Il a 3 objectifs : valider les nouveaux talents, traiter les litiges du week-end, et vérifier la santé financière.

**Rising Action :**
1. **Dashboard global** : en un coup d'œil — 12 réservations ce week-end, toutes les prestations confirmées sauf 1 incident (DJ Lamine, absence jour J). CA de la semaine : 4,2M FCFA en séquestre, 3,8M versés aux artistes, 620K de commissions encaissées. Note moyenne : 4,7/5.
2. **Validation talents** : 5 nouvelles demandes de vérification. Il examine les pièces d'identité soumises. 4 sont conformes — il attribue les badges "Vérifié". 1 est floue — il demande un renvoi via notification automatique.
3. **Alerte qualité** : Le système signale un talent dont la note est passée sous 3,5/5 après 3 prestations consécutives avec des avis négatifs. Koné examine les avis, envoie un avertissement formel, et place le talent sous surveillance renforcée.
4. **Litige DJ Lamine** : Koné ouvre le rapport de traçabilité — chronologie complète, absence de check-in, messages sans réponse. Il confirme le remboursement intégral au client et applique un avertissement au talent.
5. Il délègue à son équipe :
   - **Comptable** : "Vérifier les versements de la semaine et exporter le rapport comptable mensuel."
   - **Contrôleur** : "Suivre les check-ins des 8 prestations prévues ce week-end prochain."
   - **Modérateur** : "3 avis signalés comme inappropriés — examiner et décider."

**Climax :**
6. Koné consulte les analytics : la détection de coordonnées a intercepté 2 tentatives d'échange de numéros cette semaine. Les messages éducatifs ont été envoyés automatiquement. Aucune récidive.
7. Il vérifie les KPIs mensuels : croissance de 18% des inscriptions talents, 22% des inscriptions clients, taux de litiges à 3,2% (sous le seuil de 5%). BookMi est sur la bonne trajectoire.

**Resolution :**
8. 10h — Koné a terminé sa revue. 2 heures de travail pour gérer une plateforme de 1 700 utilisateurs. Les collaborateurs prennent le relais pour les opérations quotidiennes. Il peut se concentrer sur la stratégie de croissance.
9. Il se dit : "Il y a 6 mois, je faisais tout manuellement. Maintenant la plateforme s'auto-régule et mon équipe opère en autonomie."

**Capabilities révélées :** Dashboard multi-vues (financier, opérationnel, qualité), validation badges, alerte précoce, gestion litiges, rapport traçabilité, délégation tâches, rôles collaborateurs, détection coordonnées, pistes d'audit, notifications automatiques.

---

### Journey 6 : Manager Moussa — Gestion d'agenda sans voir les montants

**Persona :** Moussa, manager de DJ Kerozen et de 2 autres artistes sur BookMi.

**Opening Scene :**
Moussa gère 3 artistes. Avant BookMi, il jonglait entre WhatsApp, un carnet papier et des appels téléphoniques. Il négociait les prix et prenait parfois une marge sans que les artistes le sachent. Depuis BookMi, les règles ont changé.

**Rising Action :**
1. Moussa ouvre l'app BookMi avec son compte Manager. Il voit les demandes de réservation pour ses 3 artistes — dates, lieux, événements, messages des clients. Il ne voit PAS les montants.
2. Nouvelle demande pour DJ Kerozen : "Mariage à Yamoussoukro, samedi 15 mars, 19h-22h." Moussa vérifie le calendrier de Kerozen — la date est libre. Il valide la demande.
3. Un client pose une question via la messagerie : "Est-ce que DJ Kerozen peut jouer du reggae aussi ?" Moussa répond : "Oui, son répertoire inclut reggae, coupé-décalé et afrobeats. Il adapte selon vos souhaits."
4. Il consulte le calendrier de la semaine : 2 prestations vendredi, 3 samedi. Il active l'alerte de surcharge pour la semaine suivante et bloque le dimanche comme jour de repos.

**Climax :**
5. Moussa essaie de voir le montant d'une réservation — l'information n'est pas accessible. Il voit uniquement : "Réservation confirmée — Package Standard". Il ne peut pas modifier les tarifs ni accéder au dashboard financier.
6. Il réalise : BookMi a changé sa relation avec les artistes. La transparence protège tout le monde. Son rôle est désormais celui d'un vrai manager professionnel — gestion d'agenda, communication, logistique — pas un intermédiaire financier opaque.

**Resolution :**
7. DJ Kerozen consulte son propre dashboard et voit exactement ce que chaque client a payé. Il fait confiance à Moussa pour la logistique et garde le contrôle total sur ses finances.
8. Moussa gère désormais les agendas de 3 artistes depuis une seule interface. Plus de carnet papier, plus de messages WhatsApp perdus. Il est plus efficace et sa relation avec les artistes est assainie.

**Capabilities révélées :** Rôle Manager, visibilité contrôlée (sans montants), validation/refus réservations, messagerie client, calendrier multi-artistes, alertes de surcharge, séparation gestion/finance.

---

### Journey Requirements Summary

| Journey | Capabilities clés révélées |
|---|---|
| Aminata — Happy Path | Inscription, recherche/filtres, profils vérifiés, packages, devis, réservation, paiement Mobile Money, escrow, contrat auto, notifications, check-in, évaluation |
| Aminata — Litige | Signalement, workflow litige, rapport traçabilité, remboursement auto, pénalité talent, score fiabilité |
| DJ Kerozen — Happy Path | Inscription talent, profil riche, vérification, packages/micro-prestations, calendrier, Manager, dashboard financier, anti-fraude, versement, niveaux, analytics |
| DJ Kerozen — Annulation | Politique annulation graduée, médiation, report réservation, communication cloisonnée |
| Admin Koné — Opérations | Dashboards, validation badges, alertes, litiges, traçabilité, délégation, rôles collaborateurs, détection coordonnées, audit |
| Manager Moussa — Agenda | Rôle Manager, visibilité contrôlée, validation réservations, messagerie, calendrier multi-artistes |

## Domain-Specific Requirements

### Compliance & Regulatory

**Réglementation financière (Côte d'Ivoire) :**
- **Statut d'intermédiaire financier :** BookMi opère un système d'escrow (séquestre). Conformité avec les directives de la BCEAO sur les services de paiement. Opérer via un prestataire de paiement agréé (Paystack/CinetPay) qui porte la licence EME (Établissement de Monnaie Électronique).
- **Conformité PCI DSS :** Déléguée aux passerelles de paiement (Paystack/CinetPay). BookMi ne stocke jamais de données de carte bancaire. Intégration via API sans manipulation directe des données de carte.
- **Conditions opérateurs Mobile Money :** Respecter les CGU et les limites de transaction de chaque opérateur :
  - Orange Money CI : limite transaction ~2M FCFA/jour, API via partenaire agréé
  - Wave : limite variable, API REST directe
  - MTN MoMo : API via MTN Developer Portal, KYC opérateur requis
  - Moov Money : API via partenaire, limites similaires
- **Obligations fiscales :** BookMi perçoit des commissions — assujetti à la TVA (18% en CI) sur les frais de service. Obligation de facturation conforme et de déclaration fiscale périodique. Consultation d'un fiscaliste local recommandée avant lancement.
- **Déclaration des revenus talents :** BookMi n'est pas responsable de la déclaration fiscale des talents, mais doit pouvoir fournir un récapitulatif annuel des revenus versés (attestation de revenus — fonctionnalité #57).

**Protection des données personnelles :**
- **Loi ivoirienne :** Loi n°2013-450 relative à la protection des données à caractère personnel, supervisée par l'ARTCI (Autorité de Régulation des Télécommunications/TIC). Déclaration des fichiers de données personnelles auprès de l'ARTCI avant mise en production.
- **Données sensibles collectées :** Pièces d'identité (CNI/passeport), photos, numéros de téléphone, données de paiement, historique de transactions, messages privés, données de géolocalisation.
- **Obligations :**
  - Consentement explicite à la collecte et au traitement des données (opt-in à l'inscription)
  - Droit d'accès, de rectification et de suppression pour les utilisateurs
  - Durée de conservation limitée (5 ans après dernière activité pour les données financières, 1 an pour les pièces d'identité après vérification)
  - Notification en cas de violation de données (sous 72h — aligné standards internationaux)
  - Politique de confidentialité claire et accessible en français

**Validité juridique des contrats auto-générés :**
- Contrats électroniques reconnus par la loi n°2013-546 relative aux transactions électroniques. Le contrat auto-généré est valide s'il inclut :
  - Identification claire des parties (client et talent)
  - Description précise de la prestation (date, lieu, durée, package)
  - Prix et conditions de paiement
  - Conditions d'annulation et de remboursement
  - Horodatage et acceptation électronique des deux parties
- Pour les montants élevés (> 1M FCFA), clause d'arbitrage recommandée.

### Technical Constraints

**Sécurité :**
- Chiffrement des données au repos : AES-256 pour les pièces d'identité et données financières
- Chiffrement en transit : TLS 1.3 minimum pour toutes les communications API et web
- Stockage des pièces d'identité : stockage séparé et chiffré, accès restreint aux admins autorisés, suppression après vérification (conserver uniquement le statut vérifié + date)
- Hashing des mots de passe : bcrypt avec salt (standard Laravel)
- Tokens d'authentification : JWT avec expiration courte (1h access token, 7j refresh token)
- Protection CSRF, XSS, SQL injection : protections natives Laravel + validation stricte des entrées
- Rate limiting API : 60 requêtes/minute par utilisateur

**Confidentialité de la messagerie :**
- Messages chiffrés en transit et au repos
- Accès admin aux messages uniquement dans le cadre d'un litige formel (avec piste d'audit)
- Détection d'échange de coordonnées : analyse côté serveur des patterns de numéros/emails (regex + patterns connus)
- Rétention des messages : 12 mois après la dernière réservation entre les parties

**Disponibilité et résilience :**
- Architecture cloud avec provider couvrant l'Afrique de l'Ouest (AWS Lagos, Google Cloud South Africa, ou OVH/Scaleway avec CDN africain)
- Latence cible : < 200ms depuis Abidjan
- CDN pour les assets statiques (photos, vidéos) — optimisation bande passante critique en Afrique de l'Ouest
- Compression des images côté serveur (WebP) pour réduire la consommation data mobile
- Mode dégradé : si la passerelle de paiement est indisponible, permettre la réservation avec paiement différé

### Integration Requirements

**Passerelles de paiement :**
- **Paystack** : API REST, webhooks pour notifications de statut, support Mobile Money CI + carte
- **CinetPay** : Alternative/backup, couverture Mobile Money étendue en Afrique de l'Ouest
- **Gestion multi-passerelle** : Basculement entre Paystack et CinetPay en cas de panne
- **Webhooks** : Réception fiable des callbacks de paiement (idempotence, retry, logging)

**Services tiers :**
- **Email transactionnel** : Mailgun/SendGrid pour notifications email
- **Push notifications** : Firebase Cloud Messaging (FCM) pour Flutter (iOS + Android)
- **Stockage fichiers** : AWS S3 ou équivalent pour photos/vidéos
- **Géolocalisation** : Google Maps API ou OpenStreetMap
- **Génération PDF** : DomPDF ou équivalent Laravel pour contrats et rapports

### Risk Mitigations

| Risque | Impact | Probabilité | Mitigation |
|---|---|---|---|
| Fraude au paiement (faux Mobile Money) | Élevé | Moyen | Validation via webhooks passerelle uniquement, jamais via confirmation utilisateur |
| Usurpation d'identité talent | Élevé | Moyen | Vérification CNI manuelle par admin + badge, détection de doublons |
| Fuite de données personnelles (CNI) | Critique | Faible | Chiffrement AES-256, accès restreint, suppression après vérification |
| Panne passerelle de paiement jour J | Élevé | Faible | Multi-passerelle (Paystack + CinetPay), mode dégradé |
| Désintermédiation (échange de numéros) | Moyen | Élevé | Détection patterns, avertissement éducatif, valeur ajoutée supérieure |
| Litige non résolu (escalade judiciaire) | Moyen | Faible | Clause d'arbitrage dans les CGU, rapport de traçabilité horodaté |
| Indisponibilité serveur pendant événement | Élevé | Faible | Infrastructure redondante, failover auto, sauvegardes multi-niveaux |
| Non-conformité réglementaire ARTCI | Élevé | Moyen | Déclaration ARTCI avant lancement, politique de confidentialité conforme |
| Blanchiment d'argent via la plateforme | Critique | Faible | Limites de transaction alignées opérateurs, signalement transactions suspectes, KYC |
| Contestation validité du contrat | Moyen | Faible | Contrat conforme loi 2013-546, horodatage, acceptation électronique bilatérale |

> Les risques techniques, marché et ressources liés au MVP sont détaillés dans la section **Project Scoping > Stratégie de Mitigation des Risques**.

## Innovation & Novel Patterns

### Detected Innovation Areas

**1. Modèle économique "Cachet Intact" (Innovation business model)**
BookMi inverse le modèle standard des marketplaces où la commission est déduite du prestataire. La commission de 15% est facturée au client en supplément — l'artiste touche 100% de son cachet. C'est un changement de paradigme : la plateforme élimine l'objection principale des talents ("je perds 15%") et transforme la commission en frais de service transparent pour le client.
- **Originalité :** Aucune marketplace de services en Afrique de l'Ouest n'utilise ce modèle. Airbnb, Fiverr, Upwork déduisent tous du prestataire.
- **Impact :** Argument d'acquisition décisif pour les talents — BookMi devient un canal de revenus supplémentaire, pas une ponction.

**2. Anti-fraude management intégré (Innovation confiance)**
BookMi résout un problème spécifiquement africain : la fraude des managers d'artistes. Le rôle Manager sur la plateforme donne accès à la gestion opérationnelle (agenda, messages, validation) mais masque totalement les montants financiers. L'artiste voit exactement ce que le client paie, la commission BookMi, et son cachet net.
- **Originalité :** Aucune plateforme n'a conçu un rôle "manager" avec séparation explicite gestion/finance pour protéger l'artiste de son propre entourage.
- **Impact :** Résout un problème réel et documenté dans l'industrie musicale africaine.

**3. Communication cloisonnée totale (Innovation anti-désintermédiation)**
Zéro échange de numéros de téléphone possible. Toute communication passe par la messagerie interne BookMi. Détection automatique des tentatives de partage de coordonnées avec approche éducative (pas punitive).
- **Originalité :** WhatsApp est le canal universel en CI — forcer la communication sur-plateforme est un pari audacieux. Compensé par une messagerie de type WhatsApp (vocaux, emojis, photos) qui réduit la friction.
- **Impact :** Protège le modèle économique et la sécurité des transactions.

**4. Escrow adapté au Mobile Money (Innovation fintech africaine)**
Le système d'escrow (séquestre) est standard dans les marketplaces occidentales, mais BookMi l'adapte au Mobile Money — le moyen de paiement dominant en CI. Le flux est : client paie via Orange Money/Wave → fonds en séquestre → prestation confirmée → versement automatique au talent sur son moyen préféré (Orange Money, Wave, MTN, compte bancaire).
- **Originalité :** L'escrow Mobile Money n'existe pas en CI aujourd'hui. C'est un pont entre la fintech et la marketplace.
- **Impact :** Crée la confiance dans un marché où la peur de l'arnaque est le frein principal.

**5. Premier marché digitalisé (Innovation de marché)**
BookMi digitalise un marché entièrement informel — la réservation de talents en Côte d'Ivoire passe à 100% par WhatsApp, Facebook et bouche-à-oreille. Il n'existe aucune plateforme structurée. BookMi ne vient pas concurrencer un acteur existant — il crée une catégorie.
- **Originalité :** First mover dans un marché vierge avec un timing aligné sur l'essor du Mobile Money et des smartphones en CI (2026).
- **Impact :** Celui qui structure le marché en premier définit les règles.

### Market Context & Competitive Landscape

**Paysage concurrentiel :**
- **Côte d'Ivoire :** Aucun concurrent direct identifié. Les artistes utilisent Facebook, Instagram et WhatsApp de manière informelle.
- **Afrique :** Quelques tentatives isolées (non vérifiées) sans traction significative.
- **International :** GigSalad (US), Bark (UK), UrbanClap/Urban Company (Inde) — mais aucun n'est adapté aux réalités africaines (Mobile Money, contexte culturel, catégories de talents locaux).
- **Avantage structurel :** La connaissance intime du marché ivoirien par le fondateur (vécu personnel du problème) combinée à l'absence de concurrent crée une fenêtre d'opportunité unique.

**Timing de marché :**
- Pénétration smartphone en CI : en forte croissance (2026)
- Adoption Mobile Money : +60% de la population adulte utilise au moins un service Mobile Money
- Écosystème événementiel : mariages, anniversaires, festivals — marché récurrent et culturellement ancré
- Digitalisation accélérée post-COVID : habitudes de paiement en ligne en progression

### Validation Approach

| Innovation | Méthode de validation | Indicateur de succès | Délai |
|---|---|---|---|
| Cachet intact | Taux d'inscription talents vs marketplaces classiques | ≥ 200 talents en 3 mois | M+3 |
| Anti-fraude manager | Enquête satisfaction artistes ayant un manager | ≥ 80% préfèrent BookMi vs ancien système | M+6 |
| Communication cloisonnée | Taux de désintermédiation (contournements détectés) | < 10% des conversations tentent un échange | M+3 |
| Escrow Mobile Money | Taux de completion paiement | ≥ 95% des paiements initiés aboutissent | M+1 |
| Premier marché digitalisé | Adoption vs taille estimée du marché | ≥ 5% du marché événementiel CI digitalisé en 12 mois | M+12 |

### Risk Mitigation

| Innovation | Risque principal | Fallback |
|---|---|---|
| Cachet intact | Le client perçoit les 15% comme trop élevés | Afficher clairement la valeur (escrow, contrat, garantie) incluse dans les 15%. Période de lancement à 0% pour prouver la valeur. |
| Anti-fraude manager | Les managers refusent d'utiliser la plateforme | Communiquer la valeur pour le manager (outil professionnel, calendrier, messagerie) et non la restriction. |
| Communication cloisonnée | Les utilisateurs contournent massivement | Approche éducative, pas punitive. Rendre la messagerie BookMi supérieure à WhatsApp (réponses auto, historique lié aux réservations). |
| Escrow Mobile Money | Complexité technique d'intégration | Passerelle de paiement éprouvée (Paystack/CinetPay) qui gère la complexité Mobile Money. |
| Premier marché | Résistance du marché au changement | Go-to-market hybride : digital + terrain (recrutement physique, ambassadeurs, événement de lancement). |

## Exigences Spécifiques par Type de Projet

### Vue d'ensemble Multi-Plateforme

BookMi est une plateforme multi-canal composée de :
- **Application Web (Laravel)** : Backoffice administrateur + interface web talent/client responsive
- **Application Mobile (Flutter)** : Application cross-platform iOS/Android pour clients et talents
- **API REST centralisée** : Backend Laravel servant les deux interfaces

L'architecture suit le pattern standard des marketplaces modernes : un backend API unique alimentant plusieurs frontends spécialisés.

### Web Application — Exigences Techniques

#### Architecture Web : Hybrid MPA + API

- **Pattern choisi :** Application Laravel MPA (Multi-Page Application) pour le backoffice admin avec rendu serveur (Blade), couplée à une API REST pour l'application mobile Flutter
- **Standard similaire :** Fiverr et Airbnb utilisent un rendu serveur pour le SEO des pages publiques + SPA pour les espaces authentifiés
- **Justification :** Laravel Blade pour l'admin (rapidité de développement, pas de SEO nécessaire sur l'admin), API REST pour Flutter (séparation claire des responsabilités)

#### Matrice de Navigateurs Supportés

| Navigateur | Version minimum | Priorité | Part de marché CI |
|---|---|---|---|
| Chrome (Android) | 2 dernières versions | Critique | ~65% du trafic mobile |
| Safari (iOS) | 2 dernières versions | Haute | ~20% du trafic mobile |
| Chrome (Desktop) | 2 dernières versions | Haute | ~70% du trafic desktop |
| Firefox (Desktop) | 2 dernières versions | Moyenne | ~10% du trafic desktop |
| Samsung Internet | 2 dernières versions | Moyenne | ~8% du trafic mobile CI |
| Edge (Desktop) | 2 dernières versions | Basse | ~5% du trafic desktop |

**Note :** Le trafic en Côte d'Ivoire est majoritairement mobile (>75%). L'admin backoffice est desktop-first (usage interne).

#### Design Responsive

- **Mobile-first** pour les pages publiques (profils talents, annuaire, recherche)
- **Desktop-first** pour le backoffice administrateur
- **Breakpoints standards :** 320px (mobile), 768px (tablette), 1024px (desktop), 1440px (grand écran)
- **Standard :** Bootstrap 5 ou Tailwind CSS avec Laravel Blade — aligné sur les patterns Airbnb/Fiverr

#### Cibles de Performance Web

| Métrique | Cible | Standard |
|---|---|---|
| LCP (Largest Contentful Paint) | < 2,5s | Google Core Web Vitals "Good" |
| FID (First Input Delay) | < 100ms | Google Core Web Vitals "Good" |
| CLS (Cumulative Layout Shift) | < 0,1 | Google Core Web Vitals "Good" |
| TTFB (Time to First Byte) | < 800ms | Standard serveur Afrique Ouest |
| Taille page initiale | < 1,5 MB | Optimisation bande passante CI |
| Images | WebP + lazy loading | Réduction 30-50% vs JPEG |

#### Stratégie SEO

- **Pages SEO-critiques :** Profils publics des talents, pages catégories, annuaire, page d'accueil
- **Rendu serveur (SSR) :** Laravel Blade pour les pages publiques — indexation native Google
- **Schema.org :** Markup structuré `LocalBusiness` + `Event` + `Review` pour les profils talents
- **URLs SEO-friendly :** `bookmi.ci/dj-kerozen`, `bookmi.ci/musique/abidjan`
- **Sitemap XML** dynamique, `robots.txt` configuré
- **Meta tags** Open Graph + Twitter Card pour le partage social des profils
- **Standard :** Aligné Airbnb (pages listing indexées, avis structurés, profils publics riches)

#### Niveau d'Accessibilité

- **Cible :** WCAG 2.1 Niveau AA (standard marketplace internationale)
- **Priorités :**
  - Contraste de couleurs suffisant (ratio ≥ 4,5:1)
  - Navigation au clavier complète pour l'admin
  - Textes alternatifs pour les images (photos talents, vidéos)
  - Formulaires avec labels associés et messages d'erreur accessibles
  - Taille de police minimum 16px sur mobile
- **Particularité CI :** Support des langues à script latin (français principalement), pas de complexité RTL

### Mobile Application — Exigences Techniques

#### Plateforme et Framework

- **Framework :** Flutter (cross-platform) — décision validée dans le Product Brief
- **Plateformes cibles :** iOS 14+ et Android 8.0+ (API 26+)
- **Justification :** Flutter permet un développement simultané iOS/Android avec une seule codebase, performances natives, et écosystème Dart mature
- **Standard :** Urban Company (Inde) utilise Flutter pour sa marketplace de services — validation du choix technique pour un marché similaire

#### Exigences Plateforme

| Aspect | iOS | Android |
|---|---|---|
| Version minimum | iOS 14+ | Android 8.0+ (API 26) |
| Taille d'installation | < 60 MB | < 50 MB (APK), < 30 MB (AAB) |
| RAM minimum | 2 GB | 2 GB |
| Stockage minimum | 200 MB (avec cache) | 200 MB (avec cache) |
| Architecture | arm64 | arm64-v8a, armeabi-v7a |

**Particularité CI :** Les smartphones en Côte d'Ivoire sont majoritairement Android (~80%) avec une gamme d'appareils allant de l'entrée de gamme (2 GB RAM, processeur modeste) au haut de gamme. L'app doit performer sur des appareils d'entrée de gamme.

#### Permissions Device

| Permission | Usage | Obligatoire | Moment de demande |
|---|---|---|---|
| Caméra | Upload photo profil, pièce d'identité, portfolio | Contextuelle | Au moment de l'upload |
| Galerie photos | Sélection photos/vidéos portfolio | Contextuelle | Au moment de la sélection |
| Localisation | Géolocalisation recherche, check-in jour J | Contextuelle | À la recherche / check-in |
| Notifications push | Réservations, messages, rappels, check-in | Demandée tôt | Après inscription réussie |
| Stockage | Cache hors-ligne, téléchargement contrats PDF | Automatique | Transparente |

**Stratégie :** Demande contextuelle (pas à l'ouverture de l'app) — standard Airbnb/Uber. Explication claire du "pourquoi" avant chaque demande.

#### Mode Hors-ligne

- **Niveau :** Mode hors-ligne limité (lecture seule) — standard pour une marketplace transactionnelle
- **Données disponibles hors-ligne :**
  - Profil utilisateur et paramètres
  - Réservations confirmées (détails, dates, lieux)
  - Calendrier talent (lecture seule)
  - Messages déjà chargés
  - Contrats PDF téléchargés
- **Données nécessitant une connexion :**
  - Recherche et annuaire (données dynamiques)
  - Paiement et réservation (transactions)
  - Messagerie en temps réel (envoi)
  - Check-in jour J (géolocalisation + API)
  - Upload photos/vidéos
- **Synchronisation :** Queue de synchronisation pour les actions effectuées hors-ligne (messages en attente, réponses auto-réservation), exécutées au retour de la connexion
- **Cache :** SQLite local (via Hive ou sqflite) pour les données fréquentes, 7 jours de rétention

#### Stratégie Push Notifications

| Catégorie | Type | Priorité | Canal |
|---|---|---|---|
| Réservation | Nouvelle demande, acceptation, refus, annulation | Critique | FCM High Priority |
| Paiement | Confirmation paiement, versement reçu, remboursement | Critique | FCM High Priority |
| Messagerie | Nouveau message (avec preview) | Haute | FCM Normal Priority |
| Rappels | J-7, J-2, jour J check-in | Haute | FCM Normal Priority |
| Marketing | Boost visibilité, nouveau niveau atteint | Basse | FCM Normal + Topic |
| Admin | Litige ouvert, validation requise, alerte qualité | Critique | FCM High Priority |

**Implémentation :**
- Firebase Cloud Messaging (FCM) pour iOS et Android via Flutter
- Notification channels Android (catégories séparables par l'utilisateur)
- Rich notifications avec images (photo talent dans la notif de réservation)
- Deep linking : chaque notification mène directement à l'écran pertinent
- Throttling : max 5 notifications non-critiques par jour (standard anti-spam)

#### Conformité App Stores

**Apple App Store :**
- Conformité Guidelines 3.1.1 : Les achats de services physiques (prestations) sont exemptés de la commission Apple 30% (même catégorie qu'Airbnb, Uber)
- Conformité Guidelines 5.1.1 : Politique de confidentialité obligatoire, déclaration des données collectées dans App Privacy
- App Review : prévoir 24-48h de review, screenshots en français, description claire du service
- Taille minimum : iPhone 6s et ultérieur (iOS 14+)

**Google Play Store :**
- Conformité Play Policy : Les services physiques réservés/payés via l'app sont exemptés de la commission Google 15%
- Data Safety Section : déclaration transparente des données collectées et partagées
- Target API Level : minimum API 34 (Android 14) pour les nouvelles soumissions en 2026
- App Bundle (AAB) obligatoire, pas d'APK
- Accessibilité : TalkBack compatible recommandé

**Particularités communes :**
- Support du mode sombre (iOS/Android) — standard 2026
- Support des tailles d'écran dynamiques (foldables, tablettes)
- Deep links universels (`bookmi.ci/dj-kerozen` ouvre l'app si installée)

### Considérations d'Implémentation

#### Architecture API Partagée

- **API versionnée** : `/api/v1/` — permet l'évolution indépendante mobile/web
- **Format** : JSON REST avec pagination cursor-based (standard pour les listes infinies)
- **Authentification** : Laravel Sanctum (tokens API pour mobile) + sessions web pour l'admin
- **Documentation** : OpenAPI/Swagger auto-générée

#### Gestion Multi-Plateforme

- **Feature flags** : Activer/désactiver des fonctionnalités par plateforme sans redéploiement
- **Versioning forcé** : Forcer la mise à jour de l'app mobile quand une version critique est disponible
- **Analytics unifiées** : Même événements trackés sur web et mobile pour une vision consolidée

#### Optimisations Réseau pour l'Afrique de l'Ouest

- **Compression** : Gzip/Brotli pour les réponses API
- **Images** : CDN + WebP + thumbnails progressifs (placeholder flou → image complète)
- **Pagination** : Lazy loading des listes, pas de chargement de masse
- **Retry** : Mécanisme de retry automatique pour les requêtes échouées (connectivité intermittente)
- **Cache agressif** : HTTP cache headers + cache applicatif pour les données peu volatiles (profils, catégories)

## Project Scoping & Développement Phasé

### Stratégie MVP & Philosophie

**Approche MVP :** Platform MVP — Marketplace minimale viable centrée sur la boucle de confiance

**Principe directeur :** Chaque fonctionnalité MVP répond à la question : "Est-ce nécessaire pour que la boucle `Recherche → Réservation → Paiement → Prestation → Versement` fonctionne de bout en bout avec confiance ?"

**Ressources estimées :**
- 1 développeur backend senior (Laravel)
- 1 développeur mobile senior (Flutter)
- 1 développeur fullstack junior (support web + API)
- 1 UI/UX designer (temps partiel après le design initial)
- 1 product owner / fondateur (Aboubakarouattara)
- Durée estimée MVP : 4-6 mois de développement

### MVP Feature Set (Phase 1)

#### Parcours utilisateurs couverts par le MVP

| Parcours | Couverture MVP | Justification |
|---|---|---|
| Aminata — Réservation réussie | 100% | Boucle complète client — critique |
| Aminata — Litige (artist no-show) | 100% | Confiance = différenciateur #1 |
| DJ Kerozen — Inscription au "aha moment" | 100% | Boucle complète talent — critique |
| DJ Kerozen — Annulation tardive | 90% | Politique auto + médiation manuelle |
| Admin Koné — Opérations quotidiennes | 85% | Dashboards essentiels, rôles de base |
| Manager Moussa — Gestion agenda | 100% | Innovation anti-fraude manager — critique |

#### Capacités Must-Have (MVP)

**1. Boucle de confiance (Socle) :**
- Inscription et vérification bilatérale (badge vérifié client + talent)
- Profils talents riches (bio, portfolio vidéo/photo, avis, score fiabilité)
- Packages et tarification transparente (cachet intact + 15% frais affichés)
- Contrat auto-généré conforme loi 2013-546
- Escrow Mobile Money (Orange Money, Wave, MTN, Moov) + carte bancaire
- Versement automatique 24h post-confirmation

**2. Découverte et Réservation :**
- Annuaire avec filtres (catégorie, budget, localisation, note)
- Géolocalisation
- Demande de réservation → validation talent → paiement → contrat
- Micro-prestations et réservation express
- Politique d'annulation graduée (J-14/J-7/J-2)

**3. Communication et Suivi :**
- Messagerie WhatsApp-style avec détection de coordonnées
- Notifications push (réservation, paiement, rappels J-7/J-2)
- Check-in jour J en temps réel
- Évaluation bilatérale multi-critères

**4. Espace Talent :**
- Calendrier intelligent avec alertes de surcharge
- Rôle Manager (sans visibilité financière)
- Dashboard financier et analytics de base
- Niveaux (Nouveau → Confirmé → Premium → Elite)

**5. Espace Administrateur :**
- Dashboard global (financier, opérationnel, qualité)
- Validation badges et contrôle qualité
- Gestion litiges avec rapport de traçabilité
- Rôles collaborateurs (Comptable, Contrôleur, Modérateur)
- Détection et alerte échange de coordonnées

#### Simplifications MVP acceptées

| Fonctionnalité | Version complète | Simplification MVP | Impact |
|---|---|---|---|
| Recherche | Algorithme intelligent + ML | Filtres SQL + tri par note/distance | Acceptable — 200 talents suffisent |
| Médiation | Workflow automatisé multi-étapes | Processus semi-manuel par admin | Acceptable — < 50 litiges/mois attendus |
| Analytics talent | Dashboard complet temps réel | Statistiques quotidiennes batch | Acceptable — pas de besoin temps réel |
| Rapports admin | Rapports personnalisables | 5 rapports pré-définis exportables | Acceptable — couvre les KPIs essentiels |
| Portfolio | Vidéos intégrées + montage | Upload photo/vidéo simple + liens YouTube | Acceptable — réduit la complexité storage |
| Suggestions | Algorithme de recommandation | "Artistes similaires" par catégorie/ville | Acceptable — suffisant pour le lancement |

### Post-MVP Features

#### Phase 2 : Engagement quotidien (V2 — 6-12 mois post-lancement)

| Fonctionnalité | Prérequis MVP | Valeur ajoutée |
|---|---|---|
| Feed de découverte (TikTok-like) | Portfolio + Profils | Engagement quotidien, temps passé sur l'app |
| Stories éphémères 24h | Portfolio + Push notifs | Visibilité talent, contenu frais |
| Contenu post-événement (UGC) | Évaluations + Photos | Preuve sociale organique |
| Notifications WhatsApp Business API | Messagerie interne | Canal de rappel additionnel |
| Paiement fractionné | Escrow + Paiement | Accessibilité prestations haut de gamme |
| Programme fidélité | Réservations + Évaluations | Rétention client, récurrence |
| Assurance prestation (> 500K FCFA) | Escrow + Litiges | Confiance pour les gros montants |
| Recherche intelligente (ML) | Données de navigation/réservation | Conversion améliorée |

#### Phase 3 : Expansion africaine (V3 — 12-24 mois post-lancement)

| Fonctionnalité | Prérequis | Valeur ajoutée |
|---|---|---|
| Multi-pays (CI → Sénégal → Cameroun → Nigeria) | V1 validée + KPIs atteints | Croissance exponentielle |
| Multi-devise et multi-paiement configurable | Escrow multi-passerelle | Scalabilité géographique |
| Catégories talents adaptées par pays | Annuaire flexible | Pertinence locale |
| Talents transfrontaliers | Multi-pays + frais internationaux | Marché élargi |
| Dashboard multi-pays CEO | Dashboard admin | Vision consolidée |
| API publique (partenaires événementiels) | API REST mature | Écosystème |

### Stratégie de Mitigation des Risques

#### Risques Techniques

| Risque | Impact | Mitigation MVP |
|---|---|---|
| Complexité escrow Mobile Money | Élevé | Utiliser Paystack/CinetPay qui gèrent la complexité — pas de développement custom |
| Performance sur appareils entrée de gamme CI | Moyen | Tests réguliers sur Samsung A05s / Tecno Spark, profiling Flutter |
| Intégration multi-opérateurs Mobile Money | Moyen | Commencer avec Orange Money + Wave (90% du marché CI), ajouter MTN/Moov en sprint 2 |
| Détection de coordonnées dans les messages | Moyen | V1 par regex patterns simples, amélioration ML en V2 |

#### Risques Marché

| Risque | Impact | Mitigation MVP |
|---|---|---|
| Problème "chicken and egg" (pas de talent = pas de client) | Critique | Recrutement terrain de 50 talents avant le lancement client, profils pré-remplis |
| Résistance des talents au changement | Élevé | Argument "cachet intact" + formation individuelle + ambassadeurs terrain |
| Commission 15% perçue comme élevée par les clients | Moyen | Période de lancement 0% pendant 2 mois, puis montée progressive à 15% |
| Désintermédiation massive | Moyen | Approche éducative + valeur supérieure (escrow, contrat, garantie) > friction |

#### Risques Ressources

| Risque | Impact | Contingence |
|---|---|---|
| Équipe réduite (moins de développeurs que prévu) | Élevé | MVP minimal : Mobile uniquement (pas de web client), admin simplifié |
| Budget insuffisant pour les 4-6 mois | Élevé | Lancement en 3 mois avec scope réduit : 1 seul mode de paiement (Orange Money), pas de micro-prestations |
| Turnover développeur en cours de projet | Moyen | Documentation technique rigoureuse, architecture simple (conventions Laravel), code review |

## Functional Requirements

### Gestion des Utilisateurs & Identité

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

### Découverte & Recherche

- FR11: Un client peut parcourir l'annuaire des talents vérifiés
- FR12: Un client peut filtrer les talents par catégorie, sous-catégorie, budget, localisation et note
- FR13: Un client peut rechercher des talents par géolocalisation (proximité)
- FR14: Un client peut consulter le profil public d'un talent (portfolio, avis, score de fiabilité, packages, disponibilités)
- FR15: Un client peut voir des suggestions de talents similaires sur un profil
- FR16: Un client peut suivre des talents en favoris
- FR17: Un talent possède une URL unique partageable (lien profil public)

### Réservation & Contrats

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

### Paiement & Finances

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

### Communication

- FR39: Un client et un talent peuvent échanger des messages via la messagerie interne de type WhatsApp (texte, emojis, photos, vocaux)
- FR40: Le système détecte les tentatives d'échange de coordonnées personnelles dans les messages et envoie un avertissement éducatif
- FR41: Un talent peut configurer des réponses automatiques pour la messagerie
- FR42: Le système envoie des notifications push pour les événements critiques (réservation, paiement, message, rappel)
- FR43: Le système envoie des rappels automatiques à J-7 et J-2 avant la prestation
- FR44: Un administrateur peut accéder aux messages uniquement dans le cadre d'un litige formel avec piste d'audit

### Suivi de Prestation & Évaluation

- FR45: Le système suit le statut de la prestation le jour J en temps réel (en préparation, en route, arrivé, en cours, terminé)
- FR46: Un talent peut effectuer son check-in le jour J avec géolocalisation
- FR47: Le système alerte en cas de check-in manquant ou de retard
- FR48: Un client peut évaluer un talent après la prestation (ponctualité, qualité, professionnalisme, note globale, commentaire)
- FR49: Un talent peut évaluer un client après la prestation
- FR50: Un client peut signaler un problème sur une réservation en cours ou passée
- FR51: Un talent peut enrichir son portfolio avec les photos/vidéos validées des prestations réalisées

### Gestion des Talents & Calendrier

- FR52: Un talent peut gérer son calendrier de disponibilités (bloquer des jours, marquer les jours de repos)
- FR53: Un talent peut configurer des alertes de surcharge (nombre maximum de prestations par période)
- FR54: Un manager peut consulter et gérer le calendrier de ses talents
- FR55: Un manager peut valider ou refuser des demandes de réservation au nom de ses talents
- FR56: Un manager peut répondre aux messages clients au nom de ses talents
- FR57: Le système attribue automatiquement un niveau au talent (Nouveau, Confirmé, Premium, Elite) basé sur son activité et ses évaluations
- FR58: Un talent peut consulter ses analytics (vues du profil, villes qui le recherchent, tendances)
- FR59: Un talent peut recevoir une attestation de revenus annuelle

### Administration & Gouvernance

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

## Non-Functional Requirements

### Performance

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

### Sécurité

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

### Scalabilité

- NFR23: L'architecture supporte une montée à 10 000 utilisateurs simultanés sans refonte majeure (horizon 12 mois)
- NFR24: La base de données supporte 500 talents et 5 000 clients actifs avec des temps de requête stables
- NFR25: Le système de stockage fichiers (photos/vidéos) supporte 100 GB de contenu média au lancement, extensible sans migration
- NFR26: Les pics de trafic du week-end (vendredi-samedi soir, x3 le trafic normal) sont absorbés sans dégradation
- NFR27: L'ajout d'un nouveau moyen de paiement ou opérateur Mobile Money est possible sans modification architecturale majeure
- NFR28: L'architecture est conçue pour supporter une expansion multi-pays (multi-devise, multi-langue) en V3

### Fiabilité & Disponibilité

- NFR29: Le système maintient un uptime global de 99,5% minimum (maximum 43h de downtime par an)
- NFR30: L'uptime critique les vendredis et samedis (18h-2h) est de 99,9% minimum
- NFR31: Le basculement automatique (failover) entre serveurs s'effectue en moins de 30 secondes
- NFR32: Les sauvegardes automatiques de la base de données sont effectuées toutes les 6 heures avec rétention de 30 jours
- NFR33: En cas d'indisponibilité de la passerelle de paiement principale, le système bascule automatiquement vers la passerelle secondaire
- NFR34: Les données en cache hors-ligne sur l'application mobile restent accessibles pendant 7 jours sans connexion
- NFR35: Les webhooks de paiement sont traités de manière idempotente avec mécanisme de retry (3 tentatives, intervalle exponentiel)

### Accessibilité & Utilisabilité

- NFR36: L'interface web respecte le niveau WCAG 2.1 AA (contraste ≥ 4,5:1, navigation clavier, labels formulaires)
- NFR37: L'application mobile supporte les tailles de police système (Dynamic Type iOS, font scaling Android)
- NFR38: Tous les textes et interfaces sont en français (langue unique pour le MVP CI)
- NFR39: Les formulaires affichent des messages d'erreur clairs et contextuels en français
- NFR40: L'application mobile fonctionne correctement sur des écrans de 4,7 pouces minimum à 6,7 pouces
- NFR41: L'application mobile supporte le mode sombre (Dark Mode) iOS et Android

### Intégration

- NFR42: Les intégrations de paiement (Paystack/CinetPay) supportent les webhooks avec validation de signature
- NFR43: Les notifications push via Firebase Cloud Messaging (FCM) sont délivrées sur iOS et Android
- NFR44: Le stockage de fichiers média supporte un CDN avec points de présence en Afrique de l'Ouest
- NFR45: La géolocalisation fonctionne via Google Maps API ou OpenStreetMap avec précision au quartier
- NFR46: La génération de PDF (contrats, rapports) est effectuée côté serveur sans dépendance navigateur
- NFR47: Toutes les intégrations tierces sont encapsulées derrière des interfaces abstraites permettant le remplacement du fournisseur

### Maintenabilité

- NFR48: Le code backend suit les conventions et standards Laravel (PSR-12, architecture MVC)
- NFR49: Le code mobile Flutter suit les recommandations officielles Flutter/Dart (linting, architecture BLoC ou Riverpod)
- NFR50: L'API est documentée automatiquement via OpenAPI/Swagger et mise à jour à chaque déploiement
- NFR51: Les logs applicatifs sont structurés (JSON) et centralisés avec rétention de 90 jours
- NFR52: Les déploiements sont automatisés via CI/CD avec rollback possible en moins de 5 minutes
