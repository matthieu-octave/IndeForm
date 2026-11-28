#!/usr/bin/env bash
# Mise à jour du Compte Indé'Form sur le serveur (PlanetHoster N0C).
# Usage : bash ~/apps/compte-src/compte/deploy.sh
set -euo pipefail
cd "$(dirname "$0")"
export PATH="$HOME/bin:$PATH"

git -C .. pull --ff-only origin main
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "Compte Indé'Form est à jour : $(git -C .. log -1 --format='%h %s')"
