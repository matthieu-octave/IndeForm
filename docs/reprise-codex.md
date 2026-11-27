# Reprise du projet Indé'Form après Codex

_Synthèse de l'export de la conversation Codex (27/11/2026)._

## 1. Ce qui a été décidé

### Marque et gamme
- **MO Web Dev (MOWD)** édite **Indé'Form**, la solution globale pour les formateurs indépendants qui interviennent comme prestataires d'organismes de formation.
- Découpage final retenu en **4 modules** :
  | Module | Périmètre |
  |---|---|
  | **Form'Pilot** | « Le copilote des indépendants de la formation ». Organismes, missions et conditions, planning des interventions, heures, pièces administratives, préparation de facturation mensuelle et exports. |
  | **Form'Connect** | Connexions externes de toute la suite (Google Agenda et Outlook d'abord). |
  | **Form'Stats** | Suivi annuel, CA prévisionnel / facturé / encaissé, récapitulatifs URSSAF. |
  | **Form'Studio** | Préparer et réutiliser ses cours : modules, séances, déroulés, supports, exercices. |
- Identité visuelle : orange MOWD `#F19224`, vert MOWD `#0A9650`, anthracite `#121416` / `#212529`, blanc cassé `#FCFBF8`. Typo proposée : Manrope ou Sora (titres), Inter (interface, chiffres tabulaires).

### Stack
- **Laravel 12** (monolithe), SQLite en local, Blade + CSS maison, Livewire/Alpine envisagés. Choisi pour coller à tes compétences (PHP, Laravel, JS).

### Règles métier Form'Pilot
- Titre d'agenda : `Organisme - Groupe - [Référence module] - Intitulé`, ex. `MEWO - EADL - BC01-UA03 - Architecture Hexagonale`. Référence obligatoire ou non selon l'organisme (obligatoire pour MEWO).
- Désignation de facturation = date + titre, ex. `03/11/2026 - MEWO - EADL - BC01-UA03 - Architecture Hexagonale`, figée au moment de la préparation.
- Une intervention facturable doit avoir date, début, fin, durée > 0. Les événements « journée entière » sont ignorés à l'import.
- Import agenda → statut **« À valider »** → modale de validation à l'ouverture du tableau de bord.
- Synchro bidirectionnelle pour les interventions créées dans Form'Pilot, conflits à arbitrer manuellement.
- Export : **une ligne par intervention** (CSV), Form'Pilot ne produit pas de facture légale (outil de préparation vers un facturier externe type Auto-Entrepreneur Connect / Épithète).
- URSSAF : récapitulatif uniquement, pas de télé-déclaration.

## 2. Code produit par Codex
Le code est **uniquement sur ton PC, dans `H:\IndéGest`** (dépôt git local, jamais poussé sur GitHub).

- Modèles : `Client`, `Intervention`, `Invoice`, `InvoiceLine` (+ connexions calendrier).
- Services : `InvoiceDraftService`, `CalendarEventTitleParser`, `CalendarImportService`, `GoogleCalendarOAuthService`, `GoogleCalendarSyncService`.
- Écrans : Pilotage (tableau de bord + modale « À valider »), Interventions, Agenda hebdomadaire (clic sur créneau pour planifier), Clients (« Voir les préparations »), Préparations (registre filtrable mois/client + détail), Connexions.
- Connecteur Google Agenda : OAuth via `google/apiclient`, jetons chiffrés, import, synchro sortante, annulations. **Pas encore mis en service** : il manque les identifiants OAuth Google Cloud dans `.env`.
- Landing pages autonomes : `public/formpilot/` et `public/indeform/`.
- Tests : 15 tests, 63 assertions au vert ; Pint OK.

## 3. Points en suspens
**Form'Pilot**
1. Mise en service OAuth Google (identifiants Google Cloud).
2. Export CSV des préparations (une ligne par intervention).
3. Profil professionnel / paramètres, authentification.
4. Planning : vue mensuelle, filtres, récurrences, duplication.
5. Tarifs par intervention / mission et historique.
6. Connecteur Outlook (Microsoft Graph).
7. Tableau de bord prévu / confirmé / préparé.

**Form'Studio (dernier sujet abordé, rien de codé)** – V1 décrite :
- Bibliothèque de modules (intitulé, référence facultative, objectifs, prérequis, recherche).
- Découpage en séances réordonnables.
- Déroulé par séance (étapes, durées, consignes, total).
- Supports et exercices (fichiers / liens, typés support / exercice / corrigé).
- Duplication et adaptation d'un module ou d'une séance pour un groupe.
- Notes après la séance.
- Vue pour animer (fiche lisible, imprimable).
- Utilisable seul ; lien avec Form'Pilot plus tard (ouvrir la fiche depuis une intervention).

## 4. Proposition de reprise dans `IndeForm`
Le dépôt GitHub `matthieu-octave/IndeForm` est vide (aucun commit au 27/11/2026). Proposition :

```
IndeForm/
├── form-pilot/     ← le code de H:\IndéGest, avec son historique git
├── form-studio/    ← nouveau projet Laravel 12
├── docs/           ← décisions, charte, cette synthèse
└── README.md
```

1. Pousser le code existant de `H:\IndéGest` dans `form-pilot/` (sans le `.env`), pour ne plus dépendre d'un seul disque.
2. Créer `form-studio/` en Laravel 12, avec la même charte, et livrer la V1 par étapes : modules → séances → déroulé → supports → duplication → notes → vue pour animer.
3. Reprendre Form'Pilot ensuite (export CSV, OAuth Google).
