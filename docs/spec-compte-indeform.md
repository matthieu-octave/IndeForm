# Spécification : Compte Indé'Form (socle de la suite)

_Version 2, 28/11/2026. Rédigée par Claude à partir de la feuille de route v3 (`notes/feuille-de-route-indeform.md`) et du code actuel de Form'Studio (`form-studio/` sur `main`). Version 2 : réponses de Matthieu aux décisions ouvertes intégrées (voir § 12)._

## 0. En bref

- Une petite application Laravel à part, **`compte-indeform.mowd.fr`**, dans le dossier `compte/` du dépôt IndeForm.
- Elle possède : **comptes, espaces de travail, modules actifs (abonnement), organismes, groupes**.
- Chaque module (Pilot, Studio, puis Stats, Quali, Connect) se connecte par **« Se connecter avec Indé'Form »** (OAuth 2, flux *authorization code* + PKCE, serveur **Laravel Passport**).
- Les modules lisent le référentiel par une **API JSON v1** et se tiennent à jour grâce à un **journal d'événements** interrogé par cron (pas besoin de worker permanent).
- Studio est le premier module branché : ses comptes actuels sont repris tels quels (mêmes e-mails, mêmes mots de passe).
- Lancement par une **bêta restreinte** : Matthieu crée lui-même les comptes de bêta et d'essai dans la console d'administration **Filament**.
- Estimation : **6 à 7 jours** (lots A à E), paiement à part (lot F, phase 3 : **Stripe**, avec PayPal, Apple Pay et Google Pay).

---

## 1. Périmètre

### Ce que fait le socle
1. Créer un compte, se connecter, mot de passe oublié, vérification de l'e-mail, page « Mon compte ».
2. Gérer l'espace de travail (1 compte = 1 espace pour l'instant, modèle prêt pour les équipes).
3. Servir de fournisseur d'identité OAuth 2 pour tous les modules (connexion unique).
4. Dire quels modules sont actifs pour un espace (selon l'abonnement ou une activation manuelle).
5. Héberger le référentiel commun : **organismes** et **groupes** (écrans de saisie + API).
6. Exporter et supprimer toutes les données du compte (RGPD), et prévenir les modules.
7. Plus tard (phase 3) : abonnement et paiement.

### Ce qu'il ne fait pas
- Aucune donnée métier de module (missions, séances, émargements…).
- Pas de connexions externes Google / Outlook : c'est Form'Connect.
- Pas de facturation des clients des formateurs : c'est Pilot.

---

## 2. Architecture

```
                 compte-indeform.mowd.fr  (socle, Laravel 12 + Passport)
                 ├─ /connexion, /compte, /organismes, /groupes   (écrans)
                 ├─ /oauth/authorize, /oauth/token               (OAuth 2)
                 └─ /api/v1/...                                  (JSON)
                        ▲                     ▲
      redirection OAuth │                     │ API (jeton)
                        │                     │
   pilot-indeform.mowd.fr      studio-indeform.mowd.fr      (stats-, quali-, connect- ensuite)
   (session locale)            (session locale)
```

Principes :
- **Chaque module garde sa propre session** (cookie sur son sous-domaine). On ne partage pas de cookie sur `.mowd.fr` : Pilot est développé à part et ne doit dépendre que d'un contrat HTTP.
- **Connexion unique** : si l'utilisateur est déjà connecté au socle, passer d'un module à l'autre ne redemande rien (les clients OAuth des modules sont « internes », sans écran de consentement).
- **Identifiants publics = UUID** (v7, générés par le socle). Les modules les stockent et ne les inventent jamais. Les identifiants entiers internes du socle ne sortent jamais.
- Contraintes N0C respectées : PHP 8.3, pas de worker de file, tout ce qui est périodique passe par `schedule:run` (cron chaque minute), envois HTTP sortants synchrones avec délai court.

### Choix technique : Laravel Passport (retenu)
| Option | Pour | Contre |
|---|---|---|
| **Laravel Passport** (retenu) | Serveur OAuth 2 complet, officiel Laravel, tourne sur mutualisé (clés RSA en fichiers, tables MySQL), PKCE, jetons de rafraîchissement, *client credentials* | Pas d'OpenID Connect complet natif (on le remplace par `/api/v1/me`) |
| Sanctum + cookie partagé | Très simple | Impose le même domaine de cookie et la même base : couple trop Pilot au socle |
| Keycloak / Authentik | OIDC complet | Impossible ou lourd sur mutualisé, à administrer à part |

Les jetons d'accès Passport sont des JWT signés RSA : un module peut aussi les vérifier localement avec la clé publique du socle (utile pour l'API v1 de Pilot lue par Stats, voir § 10).

---

## 3. Modèle de données

Toutes les tables métier portent `uuid` (public, unique) en plus de `id` (interne). Horodatage `created_at` / `updated_at` partout.

### 3.1 Comptes et espaces
| Table | Colonnes principales | Remarques |
|---|---|---|
| `users` | id, uuid, name, email (unique), email_verified_at, password, remember_token, current_workspace_id, is_admin (bool), account_type (`customer`, `beta`, `trial`, `internal`), invited_by, last_login_at, deleted_at | `is_admin` = Matthieu (console Filament) ; `account_type` sert au suivi de la bêta |
| `workspaces` | id, uuid, name, legal_name, siret, address, owner_user_id, deleted_at | Identité de l'activité du formateur (utile à Pilot pour les pièces) |
| `workspace_user` | workspace_id, user_id, role (`owner` pour l'instant) | Table prête pour les équipes ; aujourd'hui une seule ligne par espace |

### 3.2 Modules et droits
| Table | Colonnes principales | Remarques |
|---|---|---|
| `modules` | code (`pilot`, `studio`, `stats`, `quali`, `connect`), name, base_url, oauth_client_id, is_public | Catalogue ; sert aussi au sélecteur de modules |
| `workspace_modules` | workspace_id, module_code, status (`trial`, `active`, `grace`, `suspended`, `cancelled`), starts_at, ends_at, source (`manual`, `beta`, `trial`, `subscription`) | **La seule source de vérité des droits**. Une ligne par module et par espace |

### 3.3 Abonnement (emplacement réservé, sans prestataire)
| Table | Colonnes principales |
|---|---|
| `plans` | code (`pilot`, `pilot_studio`…), name, modules (json), price_cents, interval (`month`/`year`), is_active |
| `subscriptions` | workspace_id, plan_code, status, trial_ends_at, current_period_end, cancel_at, provider (null), provider_customer_id, provider_subscription_id |

Règle : un changement d'abonnement **recalcule `workspace_modules`** (une seule fonction `SyncEntitlements`). Les modules ne lisent jamais `subscriptions`, seulement les droits.

### 3.4 Référentiel commun
| Table | Colonnes principales | Remarques |
|---|---|---|
| `organisms` | id, uuid, workspace_id, name, short_name, siret, address_line, postal_code, city, email, phone, contact_name, billing_email, module_reference_required (bool), payment_terms_days (null), notes, archived_at | Liste à valider avec la table actuelle de Pilot (§ 12, point encore ouvert) |
| `groups` | id, uuid, workspace_id, organism_id (obligatoire), label (ex. `EADL`), code (null), headcount (null), starts_on, ends_on, notes, archived_at | Un groupe appartient **toujours à un seul organisme** (confirmé) |

Pas de suppression définitive d'un organisme ou d'un groupe par l'API : on **archive** (`archived_at`), car des missions et émargements y font référence. Suppression définitive seulement depuis l'écran du socle si aucun module ne la bloque (V2) ou lors de la suppression du compte.

### 3.5 Journal d'événements et audit
| Table | Colonnes | Rôle |
|---|---|---|
| `events` | id (curseur croissant), uuid, workspace_id, type, subject_uuid, payload (json), created_at | Flux de synchronisation lu par les modules (§ 5.4). Conservé 30 jours |
| `audit_logs` | id, workspace_id, user_id, action, subject_type, subject_uuid, ip, user_agent, created_at | Connexions, changements de mot de passe, d'e-mail, de droits, exports, suppressions. Conservé 1 an |

Plus les tables Passport (`oauth_clients`, `oauth_access_tokens`, `oauth_refresh_tokens`, `oauth_auth_codes`).

---

## 4. Connexion unique (flux OAuth)

### 4.1 Clients
Un client Passport **confidentiel** par module (avec secret, car tous les modules sont des applications serveur), créé par Matthieu dans la console Filament ou par `php artisan passport:client`. Pour chacun :
- `redirect_uri` exacte, par exemple `https://pilot-indeform.mowd.fr/auth/indeform/callback` ;
- consentement sauté (client interne) ;
- droit *client credentials* activé pour la synchronisation en tâche de fond.

### 4.2 Portées (scopes)
| Portée | Donne accès à |
|---|---|
| `profile` | `/api/v1/me` (utilisateur, espace, modules actifs) |
| `organisms:read` / `organisms:write` | Lecture / écriture des organismes |
| `groups:read` / `groups:write` | Lecture / écriture des groupes |
| `events:read` | Journal d'événements (jeton *client credentials* uniquement) |

### 4.3 Déroulé
1. L'utilisateur ouvre `pilot-indeform.mowd.fr` sans session → Pilot génère `state` et `code_verifier` (PKCE), les met en session, redirige vers
   `https://compte-indeform.mowd.fr/oauth/authorize?client_id=…&redirect_uri=…&response_type=code&scope=profile organisms:read organisms:write groups:read groups:write&state=…&code_challenge=…&code_challenge_method=S256`
2. Le socle affiche sa page de connexion si besoin (sinon rien), puis redirige vers `redirect_uri?code=…&state=…`.
3. Pilot vérifie `state`, échange le code : `POST /oauth/token` (grant_type `authorization_code`, client_id, client_secret, redirect_uri, code, code_verifier) → `access_token` (1 h), `refresh_token` (30 jours, renouvelé à chaque usage).
4. Pilot appelle `GET /api/v1/me`, crée ou met à jour l'utilisateur et l'espace locaux (par UUID), vérifie que `pilot` est actif, ouvre sa session locale.
5. Toutes les 15 minutes d'activité (délai validé) (ou à l'expiration du jeton), le module rappelle `/api/v1/me` (en rafraîchissant le jeton si besoin). Jeton refusé → déconnexion locale ; module devenu inactif → page « module non activé ». C'est ce qui propage déconnexions et changements de droits sans worker.

### 4.4 Déconnexion
- « Se déconnecter » dans un module : fermeture de la session locale, puis redirection vers `https://compte-indeform.mowd.fr/deconnexion?retour=<url du module>`. Le socle ferme sa session et **révoque tous les jetons de l'utilisateur** ; les autres modules le constatent au plus tard 15 minutes après (étape 5).
- « Se déconnecter partout » sur la page Mon compte : même effet, plus invalidation des sessions « se souvenir de moi ».

### 4.5 Inscription
Création de compte **uniquement sur le socle** (les modules n'ont plus d'inscription). Fermée au public pendant la bêta (`REGISTRATION_OPEN=false`), commande `php artisan compte:create-user` en secours.

**Comptes de bêta et d'essai (console Filament)** : pendant la bêta restreinte (collègues formateurs et développeurs), c'est Matthieu qui crée les comptes :
1. Formulaire « Nouveau compte » : nom, e-mail, type (`beta`, `trial`, `internal`), modules à activer (par défaut Pilot, Studio en option), date de fin (vide = illimité pour la bêta), note interne.
2. Le socle crée l'utilisateur, son espace et les lignes `workspace_modules` (`source = beta` ou `trial`), puis envoie un **e-mail d'invitation** avec un lien valable 7 jours pour choisir son mot de passe (aucun mot de passe ne transite par Matthieu).
3. Actions sur un compte : renvoyer l'invitation, prolonger l'accès, ajouter ou retirer un module, suspendre, passer en client payant (plus tard), voir la dernière connexion.
4. Fin d'un essai : statut `grace` (lecture seule et export) pendant 30 jours, puis `suspended`. Une tâche planifiée quotidienne fait la bascule et prévient l'utilisateur 7 jours avant.
5. À la commercialisation : ouverture de l'inscription publique avec un essai automatique (durée à fixer) ; les comptes de bêta restent gérés à la main.

---

## 5. API v1 du socle

Base : `https://compte-indeform.mowd.fr/api/v1`. JSON, en-tête `Authorization: Bearer <jeton>`. L'espace est **toujours déduit du jeton utilisateur** ; avec un jeton *client credentials*, le paramètre `workspace=<uuid>` est obligatoire et refusé si le module n'est pas actif pour cet espace. Pagination `?page=` (50 par page, champ `meta.next_page`). Erreurs au format `{ "error": "code", "message": "…" }` avec 401, 403, 404, 410, 422, 429.

### 5.1 `GET /me`
```json
{
  "user": { "uuid": "0193…", "name": "Matthieu Octave", "email": "…", "email_verified": true },
  "workspace": { "uuid": "0193…", "name": "MOWD", "legal_name": "…", "siret": "…", "role": "owner" },
  "modules": [
    { "code": "pilot",  "name": "Form'Pilot",  "status": "active", "ends_at": null, "url": "https://pilot-indeform.mowd.fr" },
    { "code": "studio", "name": "Form'Studio", "status": "trial",  "ends_at": "2027-01-15T00:00:00Z", "url": "https://studio-indeform.mowd.fr" }
  ],
  "account_url": "https://compte-indeform.mowd.fr/compte"
}
```
`modules` liste tous les modules du catalogue ; un module non souscrit a `status: "none"` (pour afficher « Découvrir » dans le sélecteur).
Statuts qui donnent accès : `trial`, `active` (complet) et `grace` (**lecture seule et export**, 30 jours après la fin de l'abonnement).

### 5.2 Organismes
| Méthode | Route | Portée | Rôle |
|---|---|---|---|
| GET | `/organisms?updated_since=…&include_archived=1` | organisms:read | Liste |
| GET | `/organisms/{uuid}` | organisms:read | Détail |
| POST | `/organisms` | organisms:write | Créer (renvoie l'objet avec son `uuid`) |
| PATCH | `/organisms/{uuid}` | organisms:write | Modifier, ou archiver avec `{"archived": true}` |

Objet : `uuid, name, short_name, siret, address_line, postal_code, city, email, phone, contact_name, billing_email, module_reference_required, payment_terms_days, notes, archived_at, updated_at`.

### 5.3 Groupes
Mêmes routes sous `/groups` (filtre `?organism=<uuid>`), portées `groups:*`. Objet : `uuid, organism_uuid, label, code, headcount, starts_on, ends_on, notes, archived_at, updated_at`.

### 5.4 Journal d'événements : `GET /events?after=<curseur>`
Jeton *client credentials* d'un module, portée `events:read`. Renvoie, dans l'ordre, au plus 200 événements **des espaces où ce module est actif** (plus les suppressions d'espaces qui l'ont eu), et `next_cursor`.
```json
{ "events": [
    { "id": 1042, "type": "organism.updated", "workspace": "0193…", "subject": "0193…", "payload": { …objet complet… }, "at": "…" }
  ], "next_cursor": 1042 }
```
Types : `organism.created|updated|archived`, `group.created|updated|archived`, `workspace.updated`, `workspace.deleted`, `user.updated`, `modules.changed`.
Curseur trop ancien (plus de 30 jours) → **410** : le module refait une synchronisation complète par les listes.

Pourquoi un journal plutôt que des webhooks : sur N0C, sans file d'attente, un webhook raté est perdu ; un journal lu par cron toutes les 5 minutes est fiable, rejouable et simple à coder côté module. On pourra ajouter plus tard un « ping » webhook facultatif pour accélérer.

### 5.5 Autres
- `GET /modules` : catalogue public (nom, URL, description) pour le sélecteur.
- Limites : 120 requêtes / minute / jeton ; 6 tentatives de connexion / minute / IP.

---

## 6. Droits des modules

- Chaque module a un **middleware** « module actif » qui s'appuie sur la dernière réponse `/me` (cache de session 15 min).
- `trial` / `active` : accès complet. `grace` : lecture seule + export, bandeau « abonnement terminé ». `suspended`, `cancelled`, `none` : page d'information avec lien vers `compte-indeform.mowd.fr/compte/abonnement`.
- **Avant le paiement (bêta)** : Matthieu crée les comptes et active les modules dans la console Filament (`source = beta` ou `trial`, voir § 4.5).
- Offre de lancement : plan `pilot` (Compte + Pilot) et option `studio`. **L'option Studio exige Pilot** au lancement (décidé) : le socle refuse d'activer `studio` sur un espace sans `pilot` actif, sauf comptes `internal`. Rien n'empêche de lever cette règle plus tard pour vendre Studio seul.
- Sélecteur de modules : chaque module affiche en haut la liste issue de `/me.modules` (une petite barre commune, même charte orange `#F19224` / vert `#0A9650` / anthracite `#121416`).

---

## 7. Reprise des comptes de Form'Studio

État actuel (vérifié sur `main`) : `users` et `workspaces` à identifiants entiers, `users.workspace_id`, données isolées par `course_modules.workspace_id` (séances et étapes en héritent), `session_templates.workspace_id` NULL pour les modèles fournis. Connexion locale, mot de passe oublié, page Mon compte, export et suppression dans Studio. Peu de comptes (Matthieu et éventuels testeurs).

Étapes :
1. **Studio** : migration ajoutant `uuid` à `users` et `workspaces` (renseignés pour l'existant), puis commande `php artisan studio:export-accounts > comptes.json` (uuid, nom, e-mail, hash du mot de passe, e-mail vérifié, date de création, uuid et nom de l'espace).
2. **Socle** : `php artisan compte:import-studio comptes.json` crée utilisateurs et espaces **avec les mêmes UUID** et le même hash (bcrypt Laravel des deux côtés : le mot de passe actuel continue de marcher), active `studio` (`source = manual`), ajoute une ligne d'audit.
3. **Studio se branche** : route `/auth/indeform/callback`, connexion par OAuth (§ 4.3). Correspondance par `uuid` ; un compte inconnu de Studio (créé sur le socle) est **créé à la volée** avec son espace. La colonne `password` devient inutilisée (rendue nullable, supprimée plus tard).
4. **Studio retire** : inscription, connexion par mot de passe, mot de passe oublié, modification du nom / e-mail / mot de passe (renvoi vers le socle), commande `studio:create-user` (remplacée par `compte:create-user`). **Studio garde** : export de ses données, et purge de ses données sur `workspace.deleted`.
5. Studio lit le journal d'événements par cron (5 min) pour `workspace.deleted`, `user.updated`, `modules.changed`.
6. Bascule en production : déployer le socle, importer, tester la connexion de Matthieu sur un compte de test, puis déployer Studio. **Retour arrière** possible tant que la colonne `password` n'est pas supprimée (variable `AUTH_MODE=local|indeform` dans Studio pendant 2 semaines).

Rien ne change pour les données de cours : `workspace_id` reste l'entier local de Studio, relié au socle par `workspaces.uuid`.

---

## 8. Abonnement et paiement (phase 3)

Rien n'est branché pendant la bêta. Le modèle (§ 3.3) reste neutre. Choix faits :
- **Stripe** comme prestataire, via **Laravel Cashier** (abonnements, factures, portail client, webhooks synchrones compatibles N0C).
- **Moyens de paiement** : carte bancaire, **Apple Pay** et **Google Pay** (proposés par Stripe Checkout ; pour Apple Pay, il faut vérifier le domaine `compte-indeform.mowd.fr` dans Stripe), **PayPal** via Stripe (proposé aux comptes Stripe européens ; usage en abonnement à confirmer à l'ouverture du compte, à défaut PayPal pour un paiement annuel). Prélèvement SEPA possible plus tard.
- **TVA** : franchise en base. Stripe Tax désactivé, prix nets, mention **« TVA non applicable, article 293 B du CGI »** en pied de chaque facture Stripe. Surveiller le seuil de franchise ; s'il est dépassé, il suffira d'activer la TVA dans Stripe.
- Pages `compte/abonnement` : offre Pilot, option Studio, passage d'un compte d'essai en compte payant, factures téléchargeables (portail client Stripe).
- Chaque webhook met à jour `subscriptions` puis appelle `SyncEntitlements` → événement `modules.changed`. Paiement échoué : relances Stripe, puis `grace` (lecture seule) 30 jours, puis `suspended`.

## 9. Sécurité et RGPD

### Sécurité
- HTTPS partout, HSTS, en-têtes de sécurité (comme Studio PR #10), cookies `Secure`, `HttpOnly`, `SameSite=Lax`.
- OAuth : PKCE obligatoire, `state` vérifié, `redirect_uri` exactes, secrets clients uniquement dans les `.env` des modules, jetons d'accès 1 h, jetons de rafraîchissement 30 jours avec rotation, purge quotidienne (`passport:purge`).
- Clés Passport hors dépôt (`storage/oauth-*.key`, droits 600, ou variables `PASSPORT_PRIVATE_KEY` / `PASSPORT_PUBLIC_KEY`).
- *Client credentials* limité aux espaces où le module est actif, et au journal d'événements + lecture du référentiel.
- Mots de passe : 10 caractères minimum, vérification contre les fuites connues (`Password::uncompromised()`), limitation des tentatives, e-mail de vérification, avertissement par e-mail lors d'un changement de mot de passe ou d'e-mail.
- Double authentification (TOTP) : plus tard, facultative (décidé).
- **Tests d'isolation systématiques** sur chaque route API : un jeton de l'espace A ne lit, ne modifie ni ne liste rien de l'espace B ; un module inactif est refusé.
- Journal d'audit (§ 3.5) ; console Filament (`/admin`) réservée à `is_admin`, avec audit de chaque action. Filament tourne sur N0C (PHP 8.3, sans worker ; ses fichiers statiques sont publiés au déploiement par `php artisan filament:assets`).

### RGPD
- Le socle traite les données de compte (MOWD responsable de traitement) ; pour les données que les formateurs saisissent sur leurs organismes, groupes et plus tard apprenants (Quali), **MOWD est sous-traitant** : prévoir avant la commercialisation un **contrat de sous-traitance (art. 28)** dans les CGV, un registre des traitements et la liste des sous-traitants (PlanetHoster, prestataire SMTP, prestataire de paiement).
- Hébergement : **données hébergées en France** chez PlanetHoster (choix confirmé), à indiquer dans la politique de confidentialité de chaque module. L'entité nommée comme hébergeur doit être celle de la facture ; un éventuel accès du support depuis le Canada est couvert par la décision d'adéquation de l'UE.
- **Export** : page Mon compte du socle → archive JSON du compte, de l'espace, des organismes et groupes, plus un lien vers l'export de chaque module actif.
- **Suppression du compte** : sur le socle uniquement (avec mot de passe). Effets : utilisateur et espace marqués supprimés, jetons révoqués, événement `workspace.deleted` ; chaque module purge ses données à la lecture de l'événement (au plus tard 24 h) ; purge définitive du socle après 30 jours (délai pour annuler une erreur) ; disparition des sauvegardes au bout de 14 jours. Avertir l'utilisateur d'exporter d'abord ses préparations de facturation (obligations comptables qui lui incombent).
- Comptes inactifs : rappel par e-mail après 24 mois sans connexion, puis suppression (à inscrire dans la politique de confidentialité).
- Pages légales communes : mentions, confidentialité, CGU/CGV hébergées sur le socle, les modules y renvoient.

---

## 10. Section à donner à ChatGPT : brancher Form'Pilot sur le Compte Indé'Form

> **À copier telle quelle.** Elle se suffit à elle-même.

### Contexte
Form'Pilot fait partie de la suite Indé'Form. Les comptes, espaces de travail, droits d'accès, **organismes** et **groupes** appartiennent à une application séparée, le **Compte Indé'Form** (`https://compte-indeform.mowd.fr`, Laravel + Passport). Pilot ne gère plus ni inscription, ni mot de passe, ni table maîtresse d'organismes ou de groupes. Pilot sera servi sur `https://pilot-indeform.mowd.fr` (hébergement mutualisé PlanetHoster N0C, PHP 8.3, Laravel 12, **pas de worker de file** : utiliser le planificateur Laravel lancé par cron chaque minute).

### Variables d'environnement de Pilot
```
INDEFORM_URL=https://compte-indeform.mowd.fr
INDEFORM_CLIENT_ID=<fourni par Matthieu>
INDEFORM_CLIENT_SECRET=<fourni par Matthieu>
INDEFORM_REDIRECT_URI=https://pilot-indeform.mowd.fr/auth/indeform/callback
INDEFORM_MODULE_CODE=pilot
```

### 1. Connexion (OAuth 2, authorization code + PKCE)
- `GET /connexion` (ou tout accès sans session) : générer `state` (40 caractères aléatoires) et `code_verifier` (64 caractères), les stocker en session, rediriger vers
  `{INDEFORM_URL}/oauth/authorize?client_id={ID}&redirect_uri={REDIRECT}&response_type=code&scope=profile organisms:read organisms:write groups:read groups:write&state={state}&code_challenge={base64url(sha256(code_verifier))}&code_challenge_method=S256`
- `GET /auth/indeform/callback` : refuser si `state` différent. Puis `POST {INDEFORM_URL}/oauth/token` (form) : `grant_type=authorization_code, client_id, client_secret, redirect_uri, code, code_verifier`. Réponse : `access_token`, `refresh_token`, `expires_in`.
- Appeler `GET {INDEFORM_URL}/api/v1/me` avec `Authorization: Bearer {access_token}`. Réponse :
  `user {uuid, name, email}`, `workspace {uuid, name, legal_name, siret, role}`, `modules [{code, status, ends_at, url}]`, `account_url`.
- Créer ou mettre à jour l'utilisateur local par `socle_uuid = user.uuid` et l'espace local par `socle_uuid = workspace.uuid`. Ne jamais rapprocher par e-mail.
- Si le module `pilot` n'a pas le statut `trial`, `active` ou `grace` : afficher une page « Form'Pilot n'est pas activé sur votre compte » avec un lien vers `account_url`. En `grace` : lecture seule et export seulement.
- Stocker en base, chiffrés (`encrypted` cast), `access_token`, `refresh_token`, `expires_at` sur l'utilisateur. Ouvrir la session Laravel.
- Utiliser le client HTTP de Laravel (`Http::`) ; Socialite n'est pas nécessaire.

### 2. Maintien de la session
- Middleware sur toutes les routes connectées : si la dernière vérification date de plus de 15 minutes, rafraîchir le jeton si expiré (`POST /oauth/token`, `grant_type=refresh_token`, stocker le **nouveau** refresh_token) puis rappeler `/api/v1/me`. Réponse 401 → déconnexion locale et retour à la connexion. Mettre à jour nom, e-mail, espace et statut du module.
- **Déconnexion** : fermer la session locale, puis rediriger vers `{INDEFORM_URL}/deconnexion?retour=https://pilot-indeform.mowd.fr`.
- Supprimer de Pilot : inscription, connexion par mot de passe, mot de passe oublié, modification de profil. Lien « Mon compte » → `account_url`.
- Afficher en haut une barre de modules à partir de `me.modules` (lien `url` si actif, sinon « Découvrir »).

### 3. Données : règles à appliquer partout
- Chaque table métier de Pilot a une colonne `workspace_id` (espace local) et un filtre global par espace ; un utilisateur ne voit jamais les données d'un autre espace (prévoir des tests).
- Chaque enregistrement exposé à d'autres modules (missions, interventions, préparations de facturation, encaissements) a un `uuid` stable (UUID v7, `HasUuids`).
- **Organismes et groupes** : tables locales `organisms` et `groups` en **copie en lecture seule** du socle, avec `socle_uuid` (unique), `workspace_id`, les champs ci-dessous, `archived_at` et `synced_at`. Les missions et interventions référencent l'id local, et l'API de Pilot expose le `socle_uuid`.
  - Organisme : `name, short_name, siret, address_line, postal_code, city, email, phone, contact_name, billing_email, module_reference_required, payment_terms_days, notes`.
  - Groupe : `organism_uuid, label, code, headcount, starts_on, ends_on, notes`.
- Créer ou modifier un organisme ou un groupe depuis Pilot = appel API au socle avec le jeton de l'utilisateur, puis mise à jour de la copie locale avec la réponse :
  - `POST {INDEFORM_URL}/api/v1/organisms` / `PATCH /api/v1/organisms/{uuid}` (archiver : `{"archived": true}`), idem `/api/v1/groups` (avec `organism_uuid`).
  - Lister : `GET /api/v1/organisms?updated_since=…&include_archived=1&page=…` et `GET /api/v1/groups?organism=…`.
  - Erreur 422 : afficher les messages de validation renvoyés (`errors`).
- Pas de suppression définitive : un organisme ou groupe archivé reste affiché sur les anciennes missions, mais n'est plus proposé dans les listes.

### 4. Synchronisation par cron (toutes les 5 minutes)
- Obtenir un jeton machine : `POST {INDEFORM_URL}/oauth/token`, `grant_type=client_credentials, client_id, client_secret, scope=events:read organisms:read groups:read` (le garder en cache jusqu'à expiration).
- `GET {INDEFORM_URL}/api/v1/events?after={dernier_curseur}` → `events[{id, type, workspace, subject, payload, at}]`, `next_cursor`. Traiter dans l'ordre, enregistrer le curseur après chaque lot, recommencer tant qu'il reste des événements.
  - `organism.created|updated|archived`, `group.*` : mettre à jour la copie locale avec `payload`.
  - `workspace.updated`, `user.updated` : mettre à jour nom, raison sociale, SIRET, e-mail.
  - `modules.changed` : invalider le cache des droits de l'espace.
  - `workspace.deleted` : **supprimer toutes les données de cet espace** dans Pilot (obligation RGPD), dans les 24 h.
- Réponse **410** (curseur trop ancien) : resynchronisation complète de chaque espace connu via les listes, avec `workspace={uuid}` en paramètre (obligatoire avec un jeton machine), puis reprise avec le nouveau curseur.
- Rendre le traitement idempotent (rejouer un événement ne doit rien casser).

### 5. Reprise des données existantes de Pilot (une fois)
Commande `php artisan pilot:push-referentiel` : pour chaque organisme local existant, `POST /api/v1/organisms`, enregistrer le `uuid` renvoyé dans `socle_uuid` ; puis pour chaque groupe, `POST /api/v1/groups` avec `organism_uuid`. Afficher un rapport (créés, doublons de nom à vérifier). Ensuite les tables locales deviennent des copies.

### 6. Ce que Pilot exposera ensuite (préparer dès maintenant)
Une API JSON en lecture, versionnée `/api/v1`, lue par Form'Stats, Form'Quali et Form'Studio : missions, interventions (avec `reference_module` du type `BC01-UA03`), préparations de facturation, encaissements. Les appels arriveront avec un jeton émis par le Compte Indé'Form (JWT signé RSA, à vérifier avec la clé publique du socle, champ `aud` = client autorisé). Le détail de cette API fera l'objet d'une spec séparée ; il suffit pour l'instant que chaque table ait `uuid` et `workspace_id`.

### 7. Tests à écrire côté Pilot
Callback avec `state` faux refusé ; création à la volée d'un utilisateur inconnu ; module inactif → page d'information ; `grace` → écriture refusée ; jeton révoqué → déconnexion au plus tard à la vérification suivante ; isolation entre deux espaces ; traitement idempotent des événements ; `workspace.deleted` purge tout.

---

## 11. Lots de réalisation (socle et Studio)

| Lot | Contenu | Estimation |
|---|---|---|
| **A. Squelette et comptes** | App `compte/` (Laravel 12, charte Indé'Form), inscription fermée / code d'invitation, connexion, mot de passe oublié (contact@mowd.fr), vérification d'e-mail, Mon compte, espaces, UUID, `compte:create-user`, déploiement N0C sur `compte-indeform.mowd.fr` (script `deploy.sh`, sauvegarde nocturne, CI GitHub) | 1 j |
| **B. OAuth et droits** | Passport (clés, clients, portées, PKCE, consentement sauté), `/api/v1/me`, `modules` et `workspace_modules`, middleware de droits, déconnexion globale, tests | 1,5 j |
| **B bis. Console Filament** | Filament sur `/admin` : comptes et espaces, création de comptes de bêta et d'essai avec invitation, activation des modules, prolongation, suspension, tâche quotidienne de fin d'essai, clients OAuth, journal d'audit | 1 j |
| **C. Référentiel** | Écrans organismes et groupes, API v1 (liste, détail, création, modification, archivage), journal d'événements + `/events`, tests d'isolation et de portées | 1 j |
| **D. Studio branché** | UUID dans Studio, export / import des comptes, connexion OAuth avec création à la volée, retrait des écrans de compte, barre de modules, cron des événements (purge), bascule `AUTH_MODE` | 1,5 j |
| **E. RGPD et finitions** | Export et suppression sur le socle, purge à 30 jours, journal d'audit consultable, pages légales communes, e-mails d'alerte (changement de mot de passe / e-mail) | 0,5 j |
| **F. Abonnement** (phase 3) | Stripe + Cashier, plans, Apple Pay, Google Pay, PayPal, pages abonnement, webhooks, mention TVA, essai automatique et grâce | 1,5 à 2 j |

Total lots A à E : 6 à 7 jours. Ordre : A → B → B bis → C → D → E, une pull request par lot. Pilot peut commencer son branchement dès que B est en ligne (connexion) et C (référentiel).

---

## 12. Décisions

**Prises le 28/11/2026 (réponses de Matthieu)**
1. Laravel Passport comme serveur OAuth.
2. Délai de 15 minutes maximum pour propager une déconnexion ou un changement de droits : accepté.
3. Un groupe est toujours rattaché à un seul organisme.
4. Option Studio sans Pilot au lancement : refusée.
5. Lancement par une bêta restreinte (collègues formateurs et développeurs) : comptes de bêta et d'essai créés par Matthieu dans la console.
6. Double authentification : plus tard, facultative.
7. Paiement : Stripe, avec PayPal, Apple Pay et Google Pay ; franchise en base de TVA.
8. Hébergement des données en France.
9. Console d'administration : Filament.
10. Dossier `compte/` dans le dépôt.
11. Spec versée dans `docs/` du dépôt.

**Encore ouvertes**
- Champs des organismes et des groupes : comparer le § 3.4 avec la table actuelle de Pilot (délai de paiement, adresse de facturation distincte, numéro de convention…).
- Durée de l'essai automatique à l'ouverture publique, et confirmation des 30 jours de lecture seule après un essai ou un abonnement terminé.
- PayPal en abonnement via Stripe : à confirmer à l'ouverture du compte Stripe.
- Form'Stats et Form'Quali : intégrés au pack ou en option ; prix du pack et des options.
