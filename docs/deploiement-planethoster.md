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
