# Smart Drink — Project Guide & Task Tracker for Coding Agents

> Cập nhật theo lần rà soát repository ngày **2026-09-03**, branch `ui`, commit `b569f74`.
> Đây là điểm bắt đầu dành cho mọi coding agent. Đọc file này trước khi sửa code, sau đó đọc tài liệu và file nguồn liên quan trực tiếp đến task.

## 1. Mục tiêu và cách dùng tài liệu

Dự án là web app đặt đồ uống có cá nhân hoá theo sở thích và ngữ cảnh. Hai vai trò chính:

- `customer`: đăng ký/đăng nhập, khai báo sở thích, xem menu, nhận gợi ý, đặt/hủy đơn, xem lịch sử và đánh giá món.
- `admin`: quản lý menu, xử lý trạng thái đơn và xem báo cáo.

Thứ tự ưu tiên khi các nguồn không đồng nhất:

1. Yêu cầu mới nhất của người dùng.
2. `SPEC_smart-drink-recommendation-app.md` cho phạm vi và quy tắc nghiệp vụ.
3. Migration, route và code đang chạy cho trạng thái hiện thực thực tế.
4. `database/*.md`, `UI_DESIGN_TEMPLATE.md`, README cho giải thích thiết kế.

Không xem tài liệu cũ là bằng chứng một tính năng đã chạy. Một task chỉ được đánh dấu `[x]` khi code, migration/API/UI liên quan đã hoàn tất và kiểm thử phù hợp đã pass.

## 2. Quy trình bắt buộc cho agent

Trước khi coding:

- Đọc `git status --short`; không ghi đè thay đổi chưa commit của người khác.
- Đọc task trong mục 11, các acceptance criteria, route/controller/service/model/test liên quan.
- Nếu sửa backend, đọc thêm `backend/AGENTS.md`. File này hiện yêu cầu PHP, Composer và Laravel Boost trước khi thay đổi application code.
- Nếu sửa schema, đối chiếu cả migration thực tế lẫn `database/DATABASE_SCHEMA.md` và `database/tables/*.md`.
- Nếu thay đổi UI lớn, cập nhật `UI_DESIGN_TEMPLATE.md` trong cùng lượt làm việc theo quy tắc ở đầu file đó.
- Không đọc/ghi lại giá trị thật từ `.env`; chỉ tài liệu hoá tên biến từ `.env.example`.

Trong khi coding:

- Backend: Form Request chịu trách nhiệm validation, Controller điều phối HTTP, Service chứa nghiệp vụ/tích hợp ngoài, Resource định hình JSON, Job xử lý embedding bất đồng bộ.
- Frontend: mọi HTTP call đi qua `frontend/src/services/api.js`; auth/cart dùng Pinia; enum/value dùng chung đặt ở `frontend/src/constants/drinkOptions.js`.
- Giữ API dùng `snake_case` và giá trị enum đang có. Không tự đổi schema/endpoint/stack nếu chưa có yêu cầu.
- Gọi OpenAI/Weather phải có timeout, `try/catch`, log an toàn và fallback; không làm request gợi ý thất bại hoàn toàn chỉ vì dịch vụ ngoài lỗi.
- Không gọi LLM cho toàn menu: pre-filter → cosine similarity → top ứng viên → LLM re-rank.
- Với tác vụ queue, bảo đảm có worker chạy; việc `dispatch()` thành công không có nghĩa job đã được xử lý.

Khi hoàn tất task:

- Chạy test nhỏ nhất bao phủ thay đổi, sau đó suite liên quan; frontend chạy lint và production build.
- Cập nhật checkbox trong mục 11 và dòng “Bằng chứng” của task. Không đánh dấu hoàn tất nếu test chưa chạy được; ghi `BLOCKED` và lý do.
- Cập nhật mục 12 nếu phát hiện thêm lệch giữa SPEC, docs và code.
- Ghi ngắn gọn file đã đổi, test đã chạy và rủi ro còn lại trong handoff/commit.

## 3. Bản đồ repository

```text
drink_app/
├── AGENTS.md                         # File hiện tại: bản đồ + task tracker
├── SPEC_smart-drink-recommendation-app.md
│                                     # Hợp đồng nghiệp vụ, UC-01..UC-10, luồng recommendation
├── UI_DESIGN_TEMPLATE.md             # Design system và trạng thái triển khai UI
├── README-DOCKER.md                  # Cách chạy Docker (có vài thông tin phiên bản đã cũ)
├── Makefile                          # up/down/build/restart/logs/shell/ps
├── docker-compose.yml                # PHP/Nginx/Vue/MySQL/Redis/phpMyAdmin
├── docker/                           # Dockerfile, entrypoint, Nginx, MySQL
├── backend/                          # Laravel API
│   ├── AGENTS.md                     # Hướng dẫn riêng của backend
│   ├── app/
│   │   ├── Enums/                    # Enum nghiệp vụ
│   │   ├── Http/Controllers/Api/     # Auth, menu, order, rating, reports; recommendation còn stub
│   │   ├── Http/Requests/            # Validation theo module
│   │   ├── Http/Resources/           # JSON resources
│   │   ├── Jobs/                     # Hai job embedding hiện còn TODO
│   │   ├── Models/                   # 7 Eloquent models
│   │   └── Services/                 # Profile text hoàn thiện; AI/weather còn TODO
│   ├── database/                     # 11 migrations, factories, seeders
│   ├── routes/api.php                # Nguồn route API thực tế
│   └── tests/                        # PHPUnit feature tests
├── frontend/                         # Vue SPA đang dùng
│   ├── src/views/                    # 9 view đang có route
│   ├── src/components/               # layout/menu/ui components
│   ├── src/stores/                   # Pinia auth/cart (+ counter mẫu không dùng)
│   ├── src/services/api.js           # Axios client và toàn bộ API wrappers
│   └── src/assets/                   # Tailwind entry, fonts, design tokens
├── database/                         # ERD/schema/table docs; một số câu “stub” đã lỗi thời
└── template_frontend/                # React/Figma reference, không phải app production
```

Các file scaffold Vue còn dư và không nằm trong router/app hiện tại: `AboutView.vue`, `HelloWorld.vue`, `TheWelcome.vue`, `WelcomeItem.vue`, `components/icons/*`, `stores/counter.js`.

`template_frontend/` và `template_frontend.zip` chỉ là nguồn tham khảo hình ảnh/layout React + shadcn; không import trực tiếp vào Vue và không sửa trừ khi task yêu cầu cập nhật template.

## 4. Stack thực tế

| Layer | Công nghệ đang khai báo |
|---|---|
| Backend | PHP `^8.3`, Laravel `^13.17`, Sanctum `^4.3`, PHPUnit `^12.5` |
| Frontend | Vue `^3.5`, Vue Router `^5.2`, Pinia `^4.0`, Axios `^1.19` |
| UI | Tailwind CSS `^4.3`, `lucide-vue-next`; Inter + Poppins qua Google Fonts |
| Database | MySQL 8 trong Docker; SQLite là default của `backend/.env.example`/test skeleton |
| Cache/queue | Redis 7 theo Docker; Laravel config vẫn có database fallback |
| Web | Nginx 1.27 Alpine → PHP-FPM |
| Dev runtime | Docker image PHP 8.3, Node 22 Alpine |

Lưu ý lệch phiên bản: SPEC/README/Docker comments vẫn nói **Laravel 11**, nhưng `backend/composer.json` hiện yêu cầu **Laravel 13**. Khi xử lý task môi trường, chốt một phiên bản rồi đồng bộ tất cả tài liệu và image; không âm thầm downgrade/upgrade.

## 5. Kiến trúc và luồng dữ liệu

```text
Vue View/Component
      │
      ▼
Pinia store / services/api.js (Axios + Bearer token)
      │  JSON /api/*
      ▼
routes/api.php → Sanctum/role middleware
      │
      ▼
Form Request → API Controller → Service/Job → Eloquent Model
                                      │              │
                                      │              └── MySQL
                                      ├── Redis cache/queue
                                      ├── Weather API       [chưa triển khai]
                                      └── OpenAI API        [chưa triển khai]
```

### 5.1 Entry points

- Browser SPA: `frontend/src/main.js` → Pinia + router → `frontend/src/App.vue` → `RouterView`.
- Frontend routing/guards: `frontend/src/router/index.js`.
- HTTP client: `frontend/src/services/api.js`; token lấy từ `localStorage.auth_token`.
- Laravel HTTP: `backend/public/index.php` → `backend/bootstrap/app.php` → `backend/routes/api.php`.
- Role middleware alias: `role` → `EnsureUserHasRole`; admin routes dùng `auth:sanctum,role:admin`.

### 5.2 Module ownership

| Module | Backend | Frontend | Trạng thái ngắn |
|---|---|---|---|
| Auth | `AuthController`, Auth Requests, `UserResource` | `auth.js`, Login/Register | Hoạt động theo token Sanctum |
| Preferences | `PreferenceController`, `UserProfileTextService` | `PreferenceView` | Explicit + profile text có; embedding chưa chạy thật |
| Menu | `DrinkController`, `DrinkResource` | `HomeView`, menu components | Public menu/filter có |
| Cart/checkout | `OrderController::store` | `cart.js`, `CheckoutView` | Core order có; chưa có size |
| Order history | `OrderController::history/show/cancel` | `OrderHistoryView` | Có phân trang API, UI hiện chỉ lấy trang đầu |
| Ratings | `RatingController` | rating form trong `OrderHistoryView` | Upsert cho món thuộc đơn `done` |
| Admin menu | admin methods của `DrinkController` | `AdminMenuView` | CRUD/availability/soft delete có |
| Admin orders | admin methods của `OrderController` | `AdminOrdersView` | Filter, pagination, transition có |
| Reports | `ReportController` | `AdminReportsView` | Best seller có; recommendation report chờ log UC-04 |
| Recommendation | Controller/Services/Jobs skeleton | Banner tĩnh | Chưa triển khai end-to-end |

### 5.3 Luồng chính đang hoạt động

Auth:

1. Register luôn tạo role `customer`; login xoá token cũ rồi tạo token `auth` mới.
2. Frontend lưu token và user vào localStorage; Axios thêm `Authorization: Bearer ...`.
3. Khi nhận 401 (trừ login), interceptor xoá local session và điều hướng `/login`.

Đặt hàng:

1. `HomeView` tải menu public, lọc category ở client, thêm món vào Pinia cart.
2. `CheckoutView` cho chỉnh quantity/sugar/ice/note, order type và occasion.
3. Backend validate món còn bán, mở transaction, snapshot giá vào `unit_price/subtotal`, tổng vào `orders.total_price`.
4. `context_snapshot` hiện lưu hour, order type, occasion, lat/lon; weather và temperature luôn `null`.
5. Customer chỉ hủy được `pending`; admin chuyển `pending → confirmed → done` hoặc `pending/confirmed → cancelled`.

Sở thích/feedback:

1. Preference dùng quan hệ 1-1 và `updateOrCreate`.
2. `UserProfileTextService` ghép tag/default/allergy + 5 món gần đây + 3 rating từ 4 sao.
3. Chỉ khi `profile_text` đổi mới dispatch `UpdateUserProfileEmbeddingJob`.
4. Job embedding hiện chưa gọi API và không ghi vector, nên `has_embedding` vẫn không phản ánh recommendation hoạt động.

## 6. Database nhanh

```text
users 1 ── 1 user_preferences
users 1 ── * orders 1 ── * order_items * ── 1 drinks
users 1 ── * ratings * ── 1 drinks
orders 1 ── * ratings
users 1 ── * recommendation_logs
```

| Bảng | Dữ liệu quan trọng | Quy tắc |
|---|---|---|
| `users` | name, email, hashed password, role | email unique; role customer/admin |
| `user_preferences` | taste tags, default sugar/ice, allergy, profile text/vector | `user_id` unique, cascade khi xóa user |
| `drinks` | nội dung, category, price, temperature, tags, availability, vector | soft delete; public chỉ thấy available và chưa xóa |
| `orders` | user, status, total, context snapshot | giá/ngữ cảnh là snapshot |
| `order_items` | drink, quantity, sugar, ice, note, unit price, subtotal | giữ giá lúc mua; cascade theo order, restrict drink |
| `ratings` | user/drink/order, 1–5, comment | unique `(user_id, order_id, drink_id)`; chỉ đơn done |
| `recommendation_logs` | context, candidates, final rank, explanation | append-only, không có `updated_at` |

Enum là hợp đồng giữa DB/PHP/JS:

- `UserRole`: `customer`, `admin`
- `OrderStatus`: `pending`, `confirmed`, `done`, `cancelled`
- `OrderType`: `dine_in`, `takeaway` (lưu trong JSON, không có cột riêng)
- `SugarLevel`: `0`, `30`, `50`, `70`, `100`
- `IceLevel`: `no_ice`, `less_ice`, `normal_ice`, `extra_ice`
- `TemperatureType`: `hot`, `cold`, `both`

Thay đổi enum phải đồng bộ PHP enum, migration/schema, Form Request, resource, frontend constants và tests.

## 7. API hiện có

Response Resource thường bọc payload trong `{ "data": ... }`; lỗi validation theo chuẩn Laravel 422. Register/login có `token` ở top-level bên cạnh `data`.

### Public/auth

| Method | Route | Auth | Ghi chú |
|---|---|---|---|
| POST | `/api/auth/register` | Public | name/email/password/password_confirmation |
| POST | `/api/auth/login` | Public | email/password |
| POST | `/api/auth/logout` | User | xóa toàn bộ token user |
| GET | `/api/auth/me` | User | user hiện tại |
| GET | `/api/drinks` | Public | optional `category`; chỉ available |
| GET | `/api/drinks/{drink}` | Public | 404 nếu unavailable |

### Customer/authenticated

| Method | Route | Chức năng |
|---|---|---|
| GET/PUT | `/api/preferences` | xem/cập nhật preference |
| POST | `/api/orders` | tạo order từ `items[]`, optional order_type/occasion/lat/lon |
| GET | `/api/orders/history` | lịch sử phân trang 10 |
| GET | `/api/orders/{order}` | chi tiết của owner hoặc admin |
| PATCH | `/api/orders/{order}/cancel` | owner hủy đơn pending |
| GET/POST | `/api/ratings` | list rating của mình/upsert rating hợp lệ |

### Admin

| Method | Route | Chức năng |
|---|---|---|
| GET/POST | `/api/admin/drinks` | list tất cả đang tồn tại/tạo món |
| GET/PUT/DELETE | `/api/admin/drinks/{drink}` | xem/sửa/soft-delete |
| GET | `/api/admin/orders` | optional status/user_id; phân trang 15 |
| GET | `/api/admin/orders/{order}` | chi tiết + customer |
| PATCH | `/api/admin/orders/{order}/status` | chuyển trạng thái hợp lệ |
| GET | `/api/admin/reports/best-selling-drinks` | optional from/to/limit |
| GET | `/api/admin/reports/recommendation-effectiveness` | optional from/to/window_hours/limit |

Chưa có route `/api/recommendations`; `RecommendationController` chưa được import/đăng ký trong `routes/api.php`.

## 8. Frontend routes và UI conventions

| URL | View | Guard |
|---|---|---|
| `/login`, `/register` | Login/Register | guest |
| `/` | menu + cart | authenticated |
| `/preferences` | preference form | authenticated |
| `/checkout` | cart/checkout/confirmation | authenticated, cart không rỗng |
| `/orders` | history/cancel/rating | authenticated |
| `/admin/menu` | menu CRUD | admin |
| `/admin/orders` | order operations | admin |
| `/admin/reports` | reports | admin |

UI dùng `<script setup>`, Composition API, Tailwind utility và token ở `frontend/src/assets/theme.css`. Dùng lại `AppNavbar`, `BaseButton`, `BaseCard`, `FormField`, `RadioCard`, `SectionHeader`, `ProgressSteps` trước khi tạo component mới. Màu thương hiệu chính là caramel `#b45309`; font body Inter, heading Poppins.

Không đưa logic nghiệp vụ mới vào `template_frontend/`. Khi port mẫu React sang Vue, chuyển ý tưởng layout/token, không copy JSX.

## 9. Cấu hình và cách chạy

### Docker — đường chạy dự kiến

```bash
cp .env.example .env
make up
make ps
make logs
```

Mặc định theo root `.env.example`:

- Backend/Nginx: `http://localhost:8080`
- Frontend: `http://localhost:5174`
- phpMyAdmin: `http://localhost:8082`
- MySQL host port: `3307`
- API frontend: `http://localhost:8080/api`

Lệnh thường dùng:

```bash
# Backend trong container
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --dirty
docker compose exec app php artisan queue:work

# Frontend trong container
docker compose exec frontend npm run lint
docker compose exec frontend npm run build
```

Biến môi trường nghiệp vụ dự kiến: `OPENAI_API_KEY`, `WEATHER_API_KEY`. Hiện `docker/php/entrypoint.sh` chèn chúng vào `backend/.env`, nhưng `backend/.env.example` và `backend/config/services.php` chưa khai báo mapping tương ứng. Khi triển khai UC-04 phải bổ sung cấu hình rõ ràng, không gọi `env()` trực tiếp ngoài config.

Quan trọng: `docker-compose.yml` chưa có service queue worker. Với `QUEUE_CONNECTION=redis`, hai embedding job sẽ nằm trong queue nhưng không tự chạy nếu không có `php artisan queue:work` hoặc service worker riêng.

### Trạng thái xác minh tại lần rà soát

- Không chạy được backend tests: host không có `php`/`composer`; yêu cầu cài prerequisite không được cấp quyền.
- Không chạy được frontend lint/build: host không có `node`/`npm`.
- Không chạy được Docker: Docker cài qua snap từ chối chạy vì `snapd.apparmor` chưa hoạt động đúng.
- `UI_DESIGN_TEMPLATE.md` ghi nhận các lần trước đã pass backend `50/50` và frontend build/lint, nhưng đó là bằng chứng lịch sử, chưa được tái kiểm chứng trong lần rà soát này.

## 10. Test map hiện có

Backend hiện có **50 feature test methods** (trong đó 1 test skeleton) + **1 unit placeholder**, tổng cộng 51 test methods trong source. `UI_DESIGN_TEMPLATE.md` ghi nhận một lần chạy lịch sử là 50/50, tức kết quả đó có trước ít nhất một test hiện tại và không nên dùng thay cho lần chạy mới.

| Test file | Bao phủ |
|---|---|
| `AuthTest.php` | register/login/me/logout, duplicate/invalid credentials |
| `DrinkTest.php` | public menu/filter, availability, admin authorization/CRUD/job dispatch |
| `PreferenceTest.php` | defaults, 1-1 update, profile text, không dispatch trùng |
| `OrderTest.php` | create/snapshot/defaults/order type/history/ownership/cancel |
| `AdminOrderTest.php` | admin auth/filter/detail/status transitions |
| `RatingTest.php` | eligibility, ownership, 1–5, upsert, profile job |
| `ReportTest.php` | best seller/date filter/recommendation conversion |

Khoảng trống test lớn nhất:

- Không có test cho RecommendationController/Service, WeatherService, EmbeddingService và hai job thực thi thực tế.
- Frontend chưa có Vitest/component/E2E tests trong `package.json`.
- `ExampleTest.php` và `Unit/ExampleTest.php` chỉ là skeleton.

## 11. Task tracker

### 11.1 Đã hoàn thành trong code

- [x] **DOC-00 — Agent project guide và task tracker**: kiến trúc, module/API/schema/runtime, test map, rủi ro, backlog có acceptance criteria và quy tắc cập nhật đã được tổng hợp trong file này. Bằng chứng: `AGENTS.md`, audit source ngày 2026-09-03.
- [x] **FOUND-01 — Khung Docker full stack**: Nginx, PHP-FPM, MySQL, Redis, phpMyAdmin, Vue dev server và Makefile đã có. Bằng chứng: `docker-compose.yml`, `docker/*`, `Makefile`. Chưa tái chạy được ở lần audit này.
- [x] **DB-01 — Schema nghiệp vụ và Eloquent models**: 7 bảng nghiệp vụ, enum, quan hệ, casts, fillable, soft delete, constraints đã được hiện thực. Bằng chứng: `backend/database/migrations/*`, `backend/app/Models/*`, feature tests lịch sử.
- [x] **DATA-01 — Factory/seeder demo**: customer/admin mẫu và menu đồ uống mẫu đã có. Bằng chứng: `DatabaseSeeder`, `DrinkSeeder`, `UserFactory`, `DrinkFactory`.
- [x] **UC-01 — Đăng ký/đăng nhập/đăng xuất/profile**: Sanctum token, API, Pinia và UI đã có. Bằng chứng: Auth controller/routes/store/views + `AuthTest`.
- [x] **UC-02A — Khai báo/cập nhật sở thích explicit**: 1 preference/user, defaults, tag/sugar/ice/allergy UI/API. Bằng chứng: Preference module + `PreferenceTest`.
- [x] **UC-02B — Tổng hợp profile text**: sở thích + lịch sử mua + rating cao, tránh dispatch khi text không đổi. Bằng chứng: `UserProfileTextService` + tests.
- [x] **UC-03 — Xem menu và lọc category**: public API chỉ lấy available, UI filter/card. Bằng chứng: Drink module/HomeView + `DrinkTest`.
- [x] **UC-05A — Giỏ hàng và tạo đơn cốt lõi**: quantity/sugar/ice/note/order type/occasion, transaction và price snapshot, checkout giả lập. Bằng chứng: Order module/cart/Checkout + `OrderTest`.
- [x] **UC-06 — Lịch sử/chi tiết/hủy đơn**: ownership và rule chỉ customer hủy pending. Bằng chứng: Order controller/UI/tests.
- [x] **UC-07A — Rating/feedback cốt lõi**: chỉ món thuộc order done, upsert unique, list rating của chính user. Bằng chứng: Rating module/UI + `RatingTest`.
- [x] **UC-08A — Admin CRUD menu**: role guard, create/update/availability/soft delete và UI. Bằng chứng: Drink controller/admin routes/AdminMenu + `DrinkTest`.
- [x] **UC-09 — Admin quản lý đơn**: list/filter/detail/pagination và state machine. Bằng chứng: Order controller/AdminOrders + `AdminOrderTest`.
- [x] **UC-10A — Báo cáo món bán chạy**: chỉ đếm order done, date range, quantity/revenue. Bằng chứng: `ReportController`, AdminReports + `ReportTest`.
- [x] **UI-01 — Design system và 9 màn hình**: Tailwind tokens/components dùng chung, responsive layout, customer/admin views. Bằng chứng: `frontend/src`, `UI_DESIGN_TEMPLATE.md`.

### 11.2 Ưu tiên P0 — cần làm để hoàn thành tính năng cốt lõi

- [ ] **REC-01 — Chốt contract API recommendation**
  - Acceptance: có Request validate `lat/lon/occasion` (hoặc contract được chốt khác), Resource/JSON schema cho từng món + score + explanation, fallback schema giống success schema.
  - Acceptance: thêm route authenticated `GET /api/recommendations` và controller không chứa logic tích hợp chi tiết.
- [ ] **REC-02 — Implement `EmbeddingService`**
  - Acceptance: gọi model embedding đã chốt qua config; timeout/retry/error handling; validate vector; cosine similarity xử lý vector rỗng, khác chiều, zero norm.
  - Acceptance: unit tests bao phủ cosine và failure paths; không log API key/input nhạy cảm.
- [ ] **REC-03 — Hoàn thiện hai embedding jobs**
  - Acceptance: `UpdateDrinkEmbeddingJob` ghép `name + description + ingredients + tags`, ghi `description_embedding` nếu nguồn đổi.
  - Acceptance: `UpdateUserProfileEmbeddingJob` embed `profile_text`, ghi `profile_embedding`; retry/backoff/failed behavior rõ ràng.
  - Acceptance: sửa `DrinkController::update()` để thay đổi `description` cũng trigger job.
- [ ] **REC-04 — Implement `WeatherService`**
  - Acceptance: lấy weather/temperature theo lat/lon, cache theo tọa độ + time bucket khoảng 30 phút, timeout/fallback `null`, fake HTTP trong test.
- [ ] **REC-05 — Implement `RecommendationService` end-to-end**
  - Acceptance: chỉ món available; weather-aware pre-filter; fallback khi thiếu location/weather/vector/API.
  - Acceptance: cosine rank → top 10 → LLM structured re-rank top 5; không gửi toàn menu.
  - Acceptance: ghi `recommendation_logs` với context/candidate/final/explanation theo đúng thứ tự.
- [ ] **REC-06 — Nối recommendation vào frontend**
  - Acceptance: wrapper trong `services/api.js`; `RecommendationBanner`/Home hiển thị loading/error/fallback/results và cho thêm món vào cart.
  - Acceptance: xin vị trí là tùy chọn; từ chối vị trí vẫn có gợi ý rule-based.
- [ ] **REC-07 — Test UC-04**
  - Acceptance: feature tests cho auth/validation/logging/ranking/fallback; unit tests services/jobs; external calls đều fake.
  - Acceptance: đo response time cục bộ và tránh N+1/call OpenAI lặp.
- [ ] **OPS-01 — Bảo đảm queue worker chạy**
  - Acceptance: thêm worker process/service phù hợp Docker, health/restart policy, tài liệu command; chứng minh job thực sự được consume.
- [ ] **DOC-01 — Đồng bộ stack Laravel 11/13 và tài liệu stale**
  - Acceptance: chốt version mục tiêu; đồng bộ SPEC/README/Docker comments/composer.
  - Acceptance: xóa các câu “model/migration hiện là stub” trong `database/*.md` khi không còn đúng.

### 11.3 Ưu tiên P1 — hoàn thiện hợp đồng nghiệp vụ và độ tin cậy

- [ ] **ORDER-01 — Chốt và triển khai size đồ uống**
  - SPEC UC-05 yêu cầu chọn size nhưng schema/request/cart hiện không có `size` và giá phụ thu.
  - Acceptance: có quyết định sản phẩm (bỏ khỏi SPEC hoặc thêm enum/schema/price rule/API/UI/tests); tất cả nguồn được đồng bộ.
- [ ] **PROFILE-01 — Đồng bộ implicit profile sau đơn hàng**
  - FR1 nói cập nhật sau order/rating; hiện order mới không gọi `UserProfileTextService`, chỉ preference/rating gọi.
  - Acceptance: chốt thời điểm (`store` hay khi `done`), dispatch sau commit, có test không tạo job/vector thừa.
- [ ] **CONTEXT-01 — Ghi weather thật vào order context**
  - Hiện `OrderController::store` luôn ghi weather/temperature `null` dù có lat/lon.
  - Acceptance: tái dùng context service, fallback an toàn, snapshot một lần, test có/không có location.
- [ ] **UC-10B — Vận hành báo cáo hiệu quả gợi ý**
  - Endpoint/UI/query đã có nhưng chưa có log thật từ UC-04.
  - Acceptance: dữ liệu từ REC-05 hiển thị đúng; chốt conversion là “tạo order” hay chỉ order `done`; test theo quyết định.
- [ ] **TEST-01 — Khôi phục môi trường và chạy full verification**
  - Acceptance: PHP/Composer/Node/npm hoặc Docker hoạt động; migrate fresh seed; backend full suite, frontend lint/build đều pass; ghi kết quả mới vào mục 9.
- [ ] **TEST-02 — Thêm frontend tests**
  - Acceptance: setup Vitest + Vue Test Utils cho store/view quan trọng; E2E tối thiểu login → menu → checkout → history và admin flow, hoặc ghi rõ phạm vi test được chốt.
- [ ] **API-01 — Chuẩn hóa validation query admin reports/orders**
  - Acceptance: validate status/date range/positive limit/window; không truyền limit âm hoặc date lỗi xuống query builder; tests 422.
- [ ] **PAGINATION-01 — Hỗ trợ đầy đủ lịch sử đơn ở frontend**
  - API history phân trang 10 nhưng UI hiện không có chuyển trang/infinite load.
  - Acceptance: pagination UX + giữ rating map đúng giữa các trang + tests.

### 11.4 Ưu tiên P2 — bảo trì và mở rộng

- [ ] **CLEAN-01 — Xóa scaffold frontend không dùng**: About/Hello/Welcome/icons/counter sau khi xác nhận không còn tham chiếu.
- [ ] **DOC-02 — Viết README root chuẩn**: quick start, accounts demo, architecture link, troubleshooting, không chứa secret.
- [ ] **AUTH-01 — Chốt quản lý user admin**: phần Actor nói admin quản lý user nhưng UC/API/UI chưa có; quyết định thêm use case hay bỏ khỏi phạm vi.
- [ ] **AUTH-02 — Chốt chiến lược token/session**: hiện login/logout xóa toàn bộ token của user; quyết định single-device hay multi-device và sửa tests/docs.
- [ ] **MEDIA-01 — Chốt ảnh món**: hiện chỉ nhận URL string; quyết định upload/storage/URL validation/placeholder.
- [ ] **UI-02 — Self-host hoặc fallback font**: Google Fonts là network dependency; bảo đảm UI vẫn ổn offline/Docker demo.
- [ ] **OBS-01 — Logging/monitoring tích hợp ngoài**: log request id/provider latency/fallback/queue failure mà không lộ dữ liệu nhạy cảm.
- [ ] **PERF-01 — Cache menu/weather/recommendation có invalidation**: invalidate khi menu/preference/rating đổi; có test cache key/TTL.

## 12. Các lệch/rủi ro đã biết

1. **Laravel version drift:** docs/Docker nói Laravel 11, Composer dùng Laravel 13.
2. **UC-04 chưa tồn tại:** controller trống, services/jobs TODO, không route, không frontend API/result UI.
3. **Queue không có worker:** Docker chỉ chạy PHP-FPM; job Redis có thể tồn đọng mãi.
4. **External service config thiếu:** root env có key nhưng `backend/config/services.php` và backend example env chưa map.
5. **Embedding invalidation thiếu `description`:** update mô tả món không dispatch job.
6. **FR1 chưa đủ:** profile text không tự sync ngay sau khi order thay đổi/hoàn tất.
7. **Context chưa đủ:** order snapshot có vị trí nhưng weather/temperature luôn null.
8. **Size bị thiếu:** UC-05 trong SPEC nhắc size nhưng DB/API/UI không có.
9. **Docs database stale:** nhiều file vẫn gọi model/migration là stub dù đã implement.
10. **Frontend history mất phần sau trang 1:** API paginate nhưng UI không điều hướng trang.
11. **Frontend không có automated tests:** chỉ có lint/build lịch sử.
12. **Scaffold/artefact dư:** Vue sample files, `template_frontend.zip`, build `frontend/dist`, Laravel cached view/log không thuộc logic nguồn; tránh dựa vào chúng để hiểu hệ thống.
13. **Dev infra hiện không xác minh được trên máy audit:** thiếu host runtimes và Docker snap/AppArmor lỗi.
14. **Auth client tin user cache:** route guard dùng `auth_user` trong localStorage; quyền thật vẫn được backend bảo vệ, nhưng UI có thể tạm hiển thị sai tới khi API trả 401/403.
15. **Report conversion cần định nghĩa:** query hiện coi bất kỳ order status nào trong window là conversion; cần xác nhận nghiệp vụ.

## 13. Definition of Done chung

Một task chỉ chuyển sang `[x]` khi:

- Acceptance criteria đã đáp ứng; không còn TODO giả lập trong đường chạy của task.
- Validation, authorization, transaction/side effect đúng và có failure/fallback path.
- Backend có test cho happy path + permission/validation + edge case quan trọng.
- Frontend thay đổi đã lint/build; nếu là luồng chính, có test phù hợp hoặc ghi rõ debt.
- Migration tương thích MySQL Docker và test DB; có phương án rollback.
- Không lộ secret/PII trong code, log, fixture hoặc Markdown.
- SPEC/schema/API/UI docs và task tracker được cập nhật cùng code.
- `git diff` chỉ chứa file thuộc task và không ghi đè thay đổi của người khác.

## 14. Handoff template

```markdown
### TASK-ID — Tên task

- Status: DONE | PARTIAL | BLOCKED
- Changed: `path/to/file`, ...
- Behavior: mô tả ngắn hành vi mới
- Tests: lệnh + kết quả chính xác
- Docs/checklist: file/mục đã cập nhật
- Remaining risk: không có | mô tả cụ thể
```
