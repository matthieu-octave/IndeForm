#!/usr/bin/env bash
# Sauvegarde nocturne de la base MySQL du Compte Indé'Form, avec rotation.
# Cron N0C (chaque nuit à 3 h) :
#   15 3 * * * bash /home/mowdjcey/apps/compte-src/compte/backup.sh >> /home/mowdjcey/backups/compte/backup.log 2>&1
# Restaurer : gunzip -c FICHIER.sql.gz | mysql -u UTILISATEUR -p NOM_DE_LA_BASE
set -euo pipefail
cd "$(dirname "$0")"

KEEP_DAYS="${KEEP_DAYS:-14}"
DEST="${BACKUP_DIR:-$HOME/backups/compte}"
mkdir -p "$DEST"
chmod 700 "$DEST"

# Lit une variable du .env (gère les guillemets simples ou doubles).
env_get() {
    local line
    line=$(grep -E "^$1=" .env | tail -n 1 | cut -d= -f2-) || true
    line="${line%\"}"; line="${line#\"}"; line="${line%\'}"; line="${line#\'}"
    printf '%s' "$line"
}

DB_NAME=$(env_get DB_DATABASE)
[ -n "$DB_NAME" ] || { echo "DB_DATABASE absent du .env" >&2; exit 1; }

# Identifiants dans un fichier temporaire privé : le mot de passe n'apparaît pas dans la liste des processus.
CNF=$(mktemp)
trap 'rm -f "$CNF"' EXIT
chmod 600 "$CNF"
{
    echo "[client]"
    echo "host=$(env_get DB_HOST)"
    echo "port=$(env_get DB_PORT)"
    echo "user=$(env_get DB_USERNAME)"
    echo "password=\"$(env_get DB_PASSWORD)\""
} > "$CNF"

FILE="$DEST/compte-$(date +%Y-%m-%d_%H%M).sql.gz"
mysqldump --defaults-extra-file="$CNF" --single-transaction --quick --no-tablespaces "$DB_NAME" | gzip -9 > "$FILE"
chmod 600 "$FILE"

find "$DEST" -name 'compte-*.sql.gz' -type f -mtime +"$KEEP_DAYS" -delete
echo "$(date '+%F %T') sauvegarde OK : $FILE ($(du -h "$FILE" | cut -f1))"
