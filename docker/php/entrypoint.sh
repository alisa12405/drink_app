#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

set_env() {
  local key="$1" val="$2"
  if grep -qE "^${key}=" .env 2>/dev/null; then
    sed -i "s|^${key}=.*|${key}=${val}|" .env
  else
    echo "${key}=${val}" >> .env
  fi
}

if [ ! -f "artisan" ] || [ ! -f "composer.lock" ]; then
  echo "[entrypoint] Backend source is incomplete: expected artisan and composer.lock." >&2
  exit 1
fi

# The development bind mount hides the vendor directory produced by docker
# build. Copy the prebuilt dependencies only when this image's lock file differs.
image_lock_checksum="$(sha256sum /opt/composer.lock | awk '{print $1}')"
if [ ! -f "vendor/autoload.php" ] || [ "$(cat vendor/.image-lock-checksum 2>/dev/null || true)" != "$image_lock_checksum" ]; then
  echo "[entrypoint] Hydrating vendor from dependencies installed at image build..."
  # vendor is a mount point in development and therefore cannot itself be removed.
  find vendor -mindepth 1 -maxdepth 1 -exec rm -rf {} +
  mkdir -p vendor
  cp -a /opt/vendor/. vendor/
  printf '%s\n' "$image_lock_checksum" > vendor/.image-lock-checksum
fi

if [ ! -f ".env" ]; then
  cp .env.example .env
fi

echo "[entrypoint] Synchronizing backend/.env with docker-compose..."
set_env APP_NAME "\"${APP_NAME:-DrinkApp}\""
set_env APP_URL "\"${APP_URL:-http://localhost}\""
set_env DB_CONNECTION "${DB_CONNECTION:-mysql}"
set_env DB_HOST "${DB_HOST:-db}"
set_env DB_PORT "${DB_PORT:-3306}"
set_env DB_DATABASE "${DB_DATABASE:-drink_app}"
set_env DB_USERNAME "${DB_USERNAME:-drink_app}"
set_env DB_PASSWORD "${DB_PASSWORD:-change_me_secret}"
set_env REDIS_HOST "${REDIS_HOST:-redis}"
set_env REDIS_PORT "${REDIS_PORT:-6379}"
set_env REDIS_CLIENT phpredis
set_env CACHE_STORE redis
set_env SESSION_DRIVER redis
set_env QUEUE_CONNECTION redis

if ! grep -qE "^APP_KEY=base64" .env 2>/dev/null; then
  php artisan key:generate --force
fi

chmod -R ugo+rwX storage bootstrap/cache 2>/dev/null || true

echo "[entrypoint] Running migrations..."
if php artisan migrate --force; then
  if [ "${LOAD_DEMO_DATA:-true}" = "true" ] && [ -f "/var/www/dump.sql" ]; then
    echo "[entrypoint] Loading demo data..."
    MYSQL_PWD="${DB_PASSWORD:-change_me_secret}" mysql --host="${DB_HOST:-db}" --port="${DB_PORT:-3306}" --user="${DB_USERNAME:-drink_app}" --default-character-set=utf8mb4 "${DB_DATABASE:-drink_app}" < /var/www/dump.sql
  fi
else
  echo "[entrypoint] Warning: migrations failed; demo data was skipped."
fi

exec "$@"
