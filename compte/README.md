# Compte Indé’Form

Le socle commun de la suite Indé’Form : comptes, espaces de travail, et bientôt connexion unique (OAuth) pour tous les modules.
Spécification : [`docs/spec-compte-indeform.md`](../docs/spec-compte-indeform.md). En ligne (cible) : https://compte-indeform.mowd.fr

## Lot A (ce dossier)
- Inscription fermée par défaut (`REGISTRATION_OPEN`, ou code d’invitation `REGISTRATION_INVITE_CODE`), commande `compte:create-user`.
- Connexion, déconnexion, mot de passe oublié, **vérification de l’adresse e-mail** (à l’inscription et après un changement d’adresse).
- Mon compte : profil, identité de l’activité (raison sociale, SIRET, adresse), mot de passe, liens vers les modules.
- Tables `users`, `workspaces`, `workspace_user`, `audit_logs` ; **UUID v7** publics, identifiants entiers jamais exposés.
- Journal d’audit (connexions, échecs, mots de passe, e-mails, créations de comptes), purgé après 1 an.

À venir : Passport et `/api/v1/me` (lot B), console Filament (lot B bis), organismes et groupes (lot C), Studio branché (lot D), export et suppression (lot E).

## En local
```
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed   # compte demo@example.test / mot-de-passe-demo
php artisan serve
php artisan test
```
Les e-mails partent dans `storage/logs/laravel.log` (`MAIL_MAILER=log`).

## Première mise en ligne sur PlanetHoster (à faire par Matthieu, en SSH et dans N0C)
1. **Code** : `cd ~/apps && git clone https://github.com/matthieu-octave/IndeForm.git compte-src`
   (un clone séparé de celui de Studio, pour déployer chaque application indépendamment).
2. **Sous-domaine** dans N0C : `compte-indeform.mowd.fr`, PHP 8.3, racine `/home/mowdjcey/apps/compte-src/compte/public`.
   Attendre la coche verte du certificat Let’s Encrypt (sinon Cloudflare renvoie 526).
3. **Base MySQL** dans N0C : une nouvelle base et son utilisateur (par exemple `mowdjcey_compte`).
4. **Dépendances** : `cd ~/apps/compte-src/compte && composer install --no-dev --optimize-autoloader`
5. **.env** : `cp .env.example .env && php artisan key:generate`, puis renseigner :
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://compte-indeform.mowd.fr
   LOG_LEVEL=warning
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=<base>
   DB_USERNAME=<utilisateur>
   DB_PASSWORD="<mot de passe>"
   SESSION_SECURE_COOKIE=true
   MAIL_MAILER=smtp
   MAIL_SCHEME=smtps
   MAIL_HOST=<même serveur SMTP que Studio>
   MAIL_PORT=465
   MAIL_USERNAME=contact@mowd.fr
   MAIL_PASSWORD="<mot de passe de la boîte>"
   MAIL_FROM_NAME="Indé'Form"
   ```
   (même configuration SMTP que le `.env` de Studio, à recopier).
6. **Base et caches** : `php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache`
7. **Ton compte administrateur** : `php artisan compte:create-user contact@mowd.fr --name="Matthieu Octave" --admin`
8. **Cron** dans N0C :
   ```
   * * * * * cd /home/mowdjcey/apps/compte-src/compte && php artisan schedule:run >> /dev/null 2>&1
   15 3 * * * bash /home/mowdjcey/apps/compte-src/compte/backup.sh >> /home/mowdjcey/backups/compte/backup.log 2>&1
   ```
   Tester la sauvegarde une fois à la main : `bash ~/apps/compte-src/compte/backup.sh`
9. **Vérifier** : https://compte-indeform.mowd.fr/connexion, se connecter, tester « Mot de passe oublié ? ».

## Mises à jour
`bash ~/apps/compte-src/compte/deploy.sh`
