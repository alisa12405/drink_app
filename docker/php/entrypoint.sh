#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

if [ ! -f artisan ] || [ ! -f vendor/autoload.php ]; then
  echo "[entrypoint] Production image is incomplete: artisan/vendor missing." >&2
  exit 1
fi

if [ -z "${APP_KEY:-}" ]; then
  echo "[entrypoint] APP_KEY is required. Set it in backend/.env." >&2
  exit 1
fi

mkdir -p storage/app/public/drinks storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R ugo+rwX storage bootstrap/cache

if [ "${SKIP_APP_BOOTSTRAP:-false}" = "true" ]; then
  exec "$@"
fi

echo "[entrypoint] Running production migrations..."
php artisan config:clear
php artisan migrate --force

echo "[entrypoint] Warming Laravel caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
