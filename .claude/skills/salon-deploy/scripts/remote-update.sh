#!/usr/bin/env bash
#
# Execute COTE SERVEUR par deploy.sh (via ssh bash -s). Ne pas lancer en local.
# Attend /tmp/salon-release.tar.gz ; conserve .env, la base SQLite, storage/ et vendor/.

set -Eeuo pipefail

APP=/var/www/salon-danse
STAGE=/tmp/salon-release
BACKUPS=/var/backups/salon-danse
ARCHIVE=/tmp/salon-release.tar.gz
DRY_RUN="${DRY_RUN:-0}"
export COMPOSER_ALLOW_SUPERUSER=1

artisan() { sudo -u www-data XDG_CONFIG_HOME=/tmp HOME=/tmp php "$APP/artisan" "$@"; }

[ -f "$ARCHIVE" ] || { echo "archive $ARCHIVE absente"; exit 1; }
[ -f "$APP/.env" ] || { echo "$APP/.env absent : serveur non provisionne (voir references/serveur.md)"; exit 1; }

rm -rf "$STAGE"; mkdir -p "$STAGE"
tar -xzf "$ARCHIVE" -C "$STAGE" --no-same-owner

# Ce qui vit seulement sur le serveur : jamais ecrase, jamais supprime.
RSYNC_EXCLUDES=(
  --exclude=/.env
  --exclude=/database/database.sqlite
  --exclude=/storage/
  --exclude=/public/storage
  --exclude=/vendor/
  --exclude=/bootstrap/cache/
)

if [ "$DRY_RUN" = 1 ]; then
  echo "-- simulation : fichiers qui changeraient"
  # -c compare le contenu : ignore les ecarts de date et de proprietaire, bruit de tar.
  rsync -rlc --delete --dry-run --itemize-changes "${RSYNC_EXCLUDES[@]}" "$STAGE/" "$APP/" | head -80 || true
  echo "-- variables SALON_* de .env.example absentes du .env serveur"
  comm -23 <(grep -oE '^SALON_[A-Z_]+' "$STAGE/.env.example" | sort -u)            <(grep -oE '^SALON_[A-Z_]+' "$APP/.env" | sort -u) | sed 's/^/  /'
  echo "-- migrations en attente"
  artisan migrate:status --pending 2>/dev/null | tail -n +2 || echo "(migrate:status indisponible)"
  rm -rf "$STAGE" "$ARCHIVE"
  exit 0
fi

echo "-- sauvegarde de la base"
mkdir -p "$BACKUPS"
BACKUP="$BACKUPS/database-$(date +%Y%m%d-%H%M%S)-${COMMIT:-inconnu}.sqlite"
sqlite3 "$APP/database/database.sqlite" ".backup '$BACKUP'"
ls -1t "$BACKUPS"/database-*.sqlite | tail -n +11 | xargs -r rm --
echo "$BACKUP"

on_error() {
  echo
  echo "ECHEC pendant la mise a jour : le site reste en maintenance."
  echo "Base sauvegardee : $BACKUP"
  echo "Restaurer : cp '$BACKUP' $APP/database/database.sqlite && chown www-data:www-data $APP/database/database.sqlite"
  echo "Puis :      sudo -u www-data php $APP/artisan up"
}
trap on_error ERR

artisan down --retry=15 >/dev/null
echo "-- maintenance activee"

echo "-- synchronisation du code"
rsync -a --delete "${RSYNC_EXCLUDES[@]}" "$STAGE/" "$APP/"
rm -rf "$STAGE" "$ARCHIVE"

echo "-- dependances (sans dev)"
cd "$APP"
composer install --no-dev --optimize-autoloader --no-interaction --quiet

echo "-- permissions"
chown -R www-data:www-data "$APP"
chmod -R 775 "$APP/storage" "$APP/bootstrap/cache" "$APP/database"
chmod 640 "$APP/.env"

echo "-- migrations"
artisan migrate --force

echo "-- caches"
artisan storage:link >/dev/null 2>&1 || true
artisan config:cache >/dev/null
artisan route:cache >/dev/null
artisan view:cache >/dev/null
systemctl reload php8.3-fpm

artisan up >/dev/null
trap - ERR
echo "-- maintenance levee"

code="$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/)"
echo "healthcheck local : $code"
[ "$code" = 200 ]
