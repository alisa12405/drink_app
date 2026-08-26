#!/usr/bin/env bash
# Auto-scaffold Laravel 11 (backend/) khi container khoi dong LAN DAU (khi
# thu muc mounted ./backend chua co "artisan"). Idempotent - lan sau chi
# dong bo .env + composer install + migrate, khong cai lai tu dau.
# Xem SPEC_smart-drink-recommendation-app.md muc 2.1, 2.6.
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

if [ ! -f "artisan" ]; then
  echo "[entrypoint] Chua co Laravel, dang cai Laravel 11 vao backend/ ..."
  # Cai vao thu muc tam roi copy de tranh loi "directory is not empty"
  # (backend/ co the da co san file placeholder truoc do).
  rm -rf /tmp/laravel-skeleton
  composer create-project laravel/laravel /tmp/laravel-skeleton --prefer-dist --no-interaction
  shopt -s dotglob nullglob
  cp -rf /tmp/laravel-skeleton/. .
  shopt -u dotglob nullglob
  rm -rf /tmp/laravel-skeleton

  echo "[entrypoint] Cai them Sanctum (theo muc 2.1 SPEC: Auth module Sanctum/JWT) ..."
  composer require laravel/sanctum --no-interaction
  php artisan vendor:publish --provider="Laravel\\Sanctum\\SanctumServiceProvider" --no-interaction || true
fi

if [ ! -f ".env" ]; then
  cp .env.example .env
fi

echo "[entrypoint] Dong bo backend/.env voi docker-compose ..."
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
set_env OPENAI_API_KEY "${OPENAI_API_KEY:-}"
set_env WEATHER_API_KEY "${WEATHER_API_KEY:-}"

if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
  echo "[entrypoint] composer install ..."
  composer install --no-interaction --prefer-dist
fi

if ! grep -qE "^APP_KEY=base64" .env 2>/dev/null; then
  echo "[entrypoint] Sinh APP_KEY ..."
  php artisan key:generate --force
fi

# ---------------------------------------------------------------------------
# Sinh khung Models / Migrations / Controllers / Jobs / Services theo dung
# cau truc goi y o muc 2.2 va 2.6 cua SPEC. Chi tao khi CHUA co (idempotent).
# ---------------------------------------------------------------------------
make_model() {
  local name="$1"
  [ -f "app/Models/${name}.php" ] || php artisan make:model "${name}" -m --no-interaction
}
make_controller() {
  local name="$1"
  [ -f "app/Http/Controllers/Api/${name}.php" ] || php artisan make:controller "Api/${name}" --no-interaction
}
make_job() {
  local name="$1"
  [ -f "app/Jobs/${name}.php" ] || php artisan make:job "${name}" --no-interaction
}

if [ -f "artisan" ]; then
  echo "[entrypoint] Sinh khung Models/Migrations (muc 2.2 SPEC) ..."
  make_model UserPreference
  make_model Drink
  make_model Order
  make_model OrderItem
  make_model Rating
  make_model RecommendationLog

  echo "[entrypoint] Sinh khung Controllers Api/* (muc 2.6 SPEC) ..."
  make_controller DrinkController
  make_controller PreferenceController
  make_controller RecommendationController
  make_controller OrderController
  make_controller RatingController

  echo "[entrypoint] Sinh khung Jobs (muc 2.6 SPEC) ..."
  make_job UpdateDrinkEmbeddingJob
  make_job UpdateUserProfileEmbeddingJob

  echo "[entrypoint] Sinh khung Services (muc 2.6 SPEC) ..."
  mkdir -p app/Services

  [ -f "app/Services/EmbeddingService.php" ] || cat > app/Services/EmbeddingService.php <<'PHP'
<?php

namespace App\Services;

/**
 * Goi OpenAI Embeddings API (text-embedding-3-small) va tinh cosine similarity.
 * Xem SPEC_smart-drink-recommendation-app.md muc 2.4, 2.5, 3.
 */
class EmbeddingService
{
    /**
     * Sinh vector embedding cho mot doan text (menu description hoac user profile_text).
     *
     * @return float[]
     */
    public function embed(string $text): array
    {
        // TODO: goi OpenAI Embeddings API (text-embedding-3-small), co try/catch + fallback.
        return [];
    }

    /**
     * Tinh do tuong dong ngu nghia giua 2 vector.
     */
    public function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        // TODO: implement cosine similarity thuan PHP (khong dung vector DB rieng).
        return 0.0;
    }
}
PHP

  [ -f "app/Services/RecommendationService.php" ] || cat > app/Services/RecommendationService.php <<'PHP'
<?php

namespace App\Services;

/**
 * Orchestrate luong goi y: pre-filter -> cosine similarity -> goi LLM re-rank.
 * Xem SPEC_smart-drink-recommendation-app.md muc 2.4 (buoc 1-8).
 */
class RecommendationService
{
    public function __construct(
        protected EmbeddingService $embeddingService,
        protected WeatherService $weatherService,
    ) {
    }

    /**
     * Sinh danh sach goi y ca nhan hoa cho user theo ngu canh hien tai.
     */
    public function recommend(int $userId, ?float $lat = null, ?float $lon = null): array
    {
        // TODO:
        // 1. Lay context (gio, thoi tiet, nhiet do) qua WeatherService.
        // 2. Pre-filter bang drinks theo context (temperature_type...).
        // 3. Tinh cosine similarity giua user_vector va tung drink_vector.
        // 4. Goi gpt-4o-mini de re-rank top candidate + sinh giai thich.
        // 5. Ghi log vao recommendation_logs.
        return [];
    }
}
PHP

  [ -f "app/Services/WeatherService.php" ] || cat > app/Services/WeatherService.php <<'PHP'
<?php

namespace App\Services;

/**
 * Goi Weather API theo toa do, cache ket qua theo toa do + khung gio (TTL ~30 phut).
 * Xem SPEC_smart-drink-recommendation-app.md muc 2.4 (buoc 2), muc 3.
 */
class WeatherService
{
    /**
     * Lay thoi tiet/nhiet do hien tai theo toa do. Fallback null neu khong co quyen vi tri
     * hoac goi API loi (theo Business Rule muc 1.5).
     *
     * @return array{weather: string, temperature: float}|null
     */
    public function getCurrentWeather(float $lat, float $lon): ?array
    {
        // TODO: goi Weather API, cache Redis theo toa do (TTL ~30 phut), try/catch fallback null.
        return null;
    }
}
PHP
fi

# php-fpm worker chay bang user www-data (xem www.conf), trong khi thu muc
# storage/bootstrap-cache duoc tao boi composer chay voi user root (owner cua
# volume mount) -> can mo quyen write cho "other" de www-data ghi duoc
# (cache view, log, session...).
chmod -R ugo+rwX storage bootstrap/cache 2>/dev/null || true

echo "[entrypoint] Chay migrate ..."
php artisan migrate --force || echo "[entrypoint] Canh bao: migrate loi, kiem tra ket noi DB."

echo "[entrypoint] Xong. Backend da san sang tai /var/www/html."

exec "$@"
