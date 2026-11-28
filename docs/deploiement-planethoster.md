# Déployer Form'Studio sur un hébergement mutualisé (PlanetHoster)

_À valider sur ton offre précise avant la première mise en ligne : version de PHP, accès SSH, Composer, cron._

## Prérequis
- PHP 8.2 ou plus (8.3 recommandé), extensions usuelles de Laravel (mbstring, xml, curl, zip, pdo_mysql).
- Une base MySQL/MariaDB et son utilisateur.
- Accès SSH (pour `composer` et `artisan`) ou, à défaut, déploiement du dossier `vendor/` construit en local.
- Un nom de domaine ou sous-domaine en HTTPS.

## Principes retenus (pas de processus permanent)
- **Files d'attente** : `QUEUE_CONNECTION=sync` (les tâches s'exécutent dans la requête). Si des tâches longues apparaissent un jour : `database` + une tâche cron `php artisan queue:work --stop-when-empty` chaque minute.
- **Tâches planifiées** : une seule entrée cron, `* * * * * cd /chemin/form-studio && php artisan schedule:run >> /dev/null 2>&1`.
- **Sessions et cache** : en base ou fichiers, jamais Redis.

## Mise en ligne
1. Déposer le code (Git ou archive) hors du dossier public, par exemple `~/form-studio/`.
2. Faire pointer la **racine web du domaine vers `form-studio/public`**. Si l'offre l'interdit, ne jamais exposer le dossier du projet : seul `public/` doit être accessible.
3. `composer install --no-dev --optimize-autoloader`
4. Copier `.env.example` en `.env` puis renseigner : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `APP_KEY` (`php artisan key:generate`), `DB_CONNECTION=mysql` et les identifiants de la base.
5. `php artisan migrate --force`
6. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
7. Vérifier que `storage/` et `bootstrap/cache/` sont accessibles en écriture.

## Mises à jour
`git pull`, `composer install --no-dev -o`, `php artisan migrate --force`, puis les trois commandes `…:cache`.

## Avant d'ouvrir à de vrais utilisateurs
- Configurer l'envoi d'e-mails (SMTP) : requis pour « mot de passe oublié » et la vérification d'adresse (pas encore développés).
- Sauvegardes automatiques de la base.
- Pages légales et RGPD (lot 5 du plan).

## Inscription et comptes
- L'inscription publique est **fermée par défaut** : `/inscription` répond 404 et le lien disparaît de la page de connexion. Les comptes existants se connectent normalement.
- Créer un compte en SSH : `php artisan studio:create-user adresse@exemple.fr --name="Prénom Nom"` (le mot de passe est demandé, 10 caractères minimum).
- Inviter des testeurs : `REGISTRATION_INVITE_CODE=un-code-secret` dans `.env` puis `php artisan config:cache` ; l'inscription demande alors ce code.
- Ouvrir à tous (plus tard) : `REGISTRATION_OPEN=true`.

## Leçons de la première mise en ligne (27/11/2026)
- `mowd.fr` passe par le proxy **Cloudflare** : son certificat couvre `*.mowd.fr` mais pas un sous-domaine à deux niveaux. Utiliser un nom à **un seul niveau** (`studio-indeform.mowd.fr`), sinon erreur TLS.
- Après création d'un sous-domaine dans N0C, attendre la coche verte du certificat Let's Encrypt (sinon Cloudflare renvoie 526).
- Dossier racine du sous-domaine : `/home/mowdjcey/apps/form-studio-src/form-studio/public`, PHP 8.3. Composer est dans `~/bin`.

## Mise à jour en une commande
Depuis le serveur : `bash ~/apps/form-studio-src/form-studio/deploy.sh` (voir le script).

## E-mails (mot de passe oublié)
Envoi par la boîte `contact@mowd.fr` de PlanetHoster. Dans le `.env` du serveur :
```
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=<serveur SMTP de la boîte, affiché dans N0C > E-mails>
MAIL_PORT=465
MAIL_USERNAME=contact@mowd.fr
MAIL_PASSWORD="<mot de passe de la boîte>"
MAIL_FROM_ADDRESS=contact@mowd.fr
MAIL_FROM_NAME="Form'Studio"
```
puis `php artisan config:cache`. Test : page de connexion > « Mot de passe oublié ? » avec ton adresse.

## Sauvegardes de la base
`form-studio/backup.sh` exporte la base MySQL (compressée) dans `~/backups/form-studio/` et garde 14 jours.
Tâche cron à ajouter dans N0C (chaque nuit à 3 h) :
```
0 3 * * * bash /home/mowdjcey/apps/form-studio-src/form-studio/backup.sh >> /home/mowdjcey/backups/form-studio/backup.log 2>&1
```
Tester une fois à la main : `bash ~/apps/form-studio-src/form-studio/backup.sh`.
Restaurer : `gunzip -c ~/backups/form-studio/FICHIER.sql.gz | mysql -u UTILISATEUR -p NOM_DE_LA_BASE`.
