# Docker environment — Smart Drink Recommendation App

Hạ tầng Docker này được khởi tạo dựa trên `SPEC_smart-drink-recommendation-app.md` để chuẩn bị sẵn
**toàn bộ** các thành phần (Nginx, PHP-FPM, MySQL, Redis, phpMyAdmin, Node/Vite) — chỉ cần
`docker compose up --build` là backend Laravel 11 và frontend Vue 3 được **tự động cài đặt và sinh
sẵn khung thư mục** để bắt đầu code ngay.

## Cấu trúc

```
docker-compose.yml
.env.example              # copy thành .env trước khi chạy
docker/
  php/
    Dockerfile             # PHP 8.3-FPM + extension Laravel can
    php.ini
    entrypoint.sh           # TU DONG cai Laravel 11 + sinh khung code (xem ben duoi)
  node/
    Dockerfile              # Node 22
    entrypoint.sh            # TU DONG scaffold Vue 3 + Vite + Router + Pinia + axios
  nginx/
    nginx.conf
    conf.d/app.conf         # HTTP, port 80
  mysql/
    my.cnf                  # utf8mb4
backend/                    # Laravel 11 (tu dong sinh boi docker/php/entrypoint.sh)
frontend/                   # Vue 3 + Vite (tu dong sinh boi docker/node/entrypoint.sh)
Makefile                    # các lệnh tiện ích (make up / down / sh ...)
```

## Tự động cài đặt khi build/chạy lần đầu

### Backend (`backend/`) — `docker/php/entrypoint.sh`

Khi container `app` khởi động và **chưa thấy file `artisan`**, script sẽ tự động:

1. `composer create-project laravel/laravel` (cài vào thư mục tạm rồi copy đè vào `backend/` để
   tránh lỗi "directory not empty" nếu đã có file cũ).
2. `composer require laravel/sanctum` (Auth module theo mục 2.1 SPEC) + publish migration/config.
3. Đồng bộ `backend/.env` với các biến của `docker-compose.yml` (`DB_HOST=db`, `REDIS_HOST=redis`,
   `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`, `OPENAI_API_KEY`,
   `WEATHER_API_KEY`...) và `php artisan key:generate`.
4. Sinh sẵn khung code đúng theo mục 2.2 và 2.6 của SPEC (chỉ khung, chưa có logic nghiệp vụ):
   - Models + migrations: `UserPreference`, `Drink`, `Order`, `OrderItem`, `Rating`,
     `RecommendationLog`.
   - Controllers: `app/Http/Controllers/Api/{Drink,Preference,Recommendation,Order,Rating}Controller.php`.
   - Jobs: `app/Jobs/{UpdateDrinkEmbeddingJob,UpdateUserProfileEmbeddingJob}.php`.
   - Services: `app/Services/{EmbeddingService,RecommendationService,WeatherService}.php`
     (class + method rỗng kèm TODO, chưa code nghiệp vụ).
5. `php artisan migrate` — tạo sẵn toàn bộ bảng trong MySQL.

Lần chạy sau (đã có `artisan`), script **bỏ qua bước cài đặt**, chỉ chạy `composer install` (nếu
thiếu `vendor/`), đồng bộ lại `.env`, và `php artisan migrate` (an toàn, tự bỏ qua migration đã chạy).

### Frontend (`frontend/`) — `docker/node/entrypoint.sh`

Khi container `frontend` khởi động và **chưa thấy `package.json`**, script sẽ tự động:

1. `npm create vue@latest` với `--router --pinia --eslint --prettier` (Vue 3 + Vite + Vue Router +
   Pinia, cài vào thư mục tạm rồi copy vào `frontend/`).
2. `npm install`.
3. Cài `axios` + tạo sẵn `src/services/api.js` (axios client trỏ tới `VITE_API_BASE_URL`, khớp
   REST API ở mục 2.3 SPEC).
4. Tạo `frontend/.env` với `VITE_API_BASE_URL=http://localhost:<APP_HTTP_PORT>/api`.
5. Chạy dev server: `npm run dev -- --host 0.0.0.0` (Vite, hot-reload).

Lần chạy sau chỉ `npm install` lại (nếu thiếu `node_modules/`) rồi start dev server.

> Cả hai script đều **idempotent**: có thể `docker compose down && docker compose up --build` bao
> nhiêu lần cũng không cài đè/mất code bạn đã viết thêm — chỉ tác động file/thư mục còn thiếu.

## Bước 1 — Chuẩn bị `.env`

```bash
cp .env.example .env
# Sửa DB_PASSWORD, DB_ROOT_PASSWORD, OPENAI_API_KEY, WEATHER_API_KEY... theo ý bạn
# Nếu máy đã có service khác dùng port 8080/3306/6379/8081/5173, đổi *_PORT tương ứng trong .env
```

## Bước 2 — Build & chạy toàn bộ stack (tự cài Laravel + Vue)

```bash
make up
# tương đương: docker compose up -d --build
```

Lần đầu chạy sẽ mất vài phút (composer create-project + npm create vue + migrate). Theo dõi tiến
trình cài đặt:

```bash
docker compose logs -f app frontend
```

Kiểm tra các container:

```bash
make ps
```

## Bước 3 — Kiểm tra các thành phần đã sẵn sàng

- Backend (Laravel welcome page): http://localhost:8080
- Frontend (Vue + Vite dev server): http://localhost:5174
- phpMyAdmin: http://localhost:8082 (user/pass = `DB_USERNAME` / `DB_PASSWORD` trong `.env`)

## Bước 4 — Bắt đầu code

Sau `make up`, các thư mục sau đã sẵn sàng để code tiếp (không cần cài đặt gì thêm):

```
backend/app/Models/{UserPreference,Drink,Order,OrderItem,Rating,RecommendationLog}.php
backend/app/Http/Controllers/Api/{Drink,Preference,Recommendation,Order,Rating}Controller.php
backend/app/Jobs/{UpdateDrinkEmbeddingJob,UpdateUserProfileEmbeddingJob}.php
backend/app/Services/{EmbeddingService,RecommendationService,WeatherService}.php
backend/database/migrations/*_create_*_table.php   (da chay migrate)

frontend/src/router/, frontend/src/stores/ (Pinia), frontend/src/services/api.js (axios)
```

Chỉ cần điền logic nghiệp vụ theo mục 2.4/2.5 của SPEC vào các Service/Controller/Job đã có sẵn.

## Các lệnh tiện ích khác

```bash
make sh            # vào shell container PHP (backend) chạy composer/artisan
make sh-frontend    # vào shell container Node (frontend) chạy npm
make logs           # xem log realtime toàn bộ service
make restart         # restart containers
make down            # tắt toàn bộ stack
```

## Ghi chú khi thêm dependency mới

- Backend: `docker compose exec app composer require <package>` — commit `composer.json`/`.lock`,
  lần build tiếp theo trên máy khác tự `composer install`.
- Frontend: `docker compose exec frontend npm install <package>` — commit `package.json`/`package-lock.json`.
