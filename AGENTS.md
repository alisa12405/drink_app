# Smart Drink — Project Guide & Task Tracker for Coding Agents

> Cập nhật theo lần rà soát repository ngày **2026-09-03**, branch `ui`, commit `b569f74`.
> Đây là điểm bắt đầu dành cho mọi coding agent. Đọc file này trước khi sửa code, sau đó đọc tài liệu và file nguồn liên quan trực tiếp đến task.

## 1. Mục tiêu và cách dùng tài liệu

Dự án là web app đặt đồ uống có cá nhân hoá theo sở thích và ngữ cảnh. Ba nhóm sử dụng:

- `guest`: xem menu/context chung, quản lý giỏ và đặt món bằng tên; không có top 5 cá nhân hóa, lịch sử hoặc rating.
- `customer`: đăng ký/đăng nhập, khai báo sở thích, xem menu, nhận gợi ý, đặt/hủy đơn, xem lịch sử và đánh giá món.
- `admin`: quản lý menu, xử lý trạng thái đơn và xem báo cáo.

Thứ tự ưu tiên khi các nguồn không đồng nhất:

1. Yêu cầu mới nhất của người dùng.
2. `doc/SPEC_smart-drink-recommendation-app.md` cho phạm vi và quy tắc nghiệp vụ.
3. Migration, route và code đang chạy cho trạng thái hiện thực thực tế.
4. `doc/database/*.md`, `doc/UI_DESIGN_TEMPLATE.md`, README cho giải thích thiết kế.

Không xem tài liệu cũ là bằng chứng một tính năng đã chạy. Một task chỉ được đánh dấu `[x]` khi code, migration/API/UI liên quan đã hoàn tất và kiểm thử phù hợp đã pass.

## 2. Quy trình bắt buộc cho agent

Trước khi coding:

- Đọc `git status --short`; không ghi đè thay đổi chưa commit của người khác.
- Đọc task trong mục 11, các acceptance criteria, route/controller/service/model/test liên quan.
- Nếu sửa backend, đọc thêm `backend/AGENTS.md`. File này hiện yêu cầu PHP, Composer và Laravel Boost trước khi thay đổi application code.
- Nếu sửa schema, đối chiếu cả migration thực tế lẫn `doc/database/DATABASE_SCHEMA.md` và `doc/database/tables/*.md`.
- Nếu thay đổi UI lớn, cập nhật `doc/UI_DESIGN_TEMPLATE.md` trong cùng lượt làm việc theo quy tắc ở đầu file đó.
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
├── DEPLOY.md                         # Triển khai từ bản clone GitHub
├── doc/                              # SPEC, UI, Docker/Cloudflare, backend/frontend và schema docs
├── Makefile                          # up/down/build/restart/logs/shell/ps
├── docker-compose.yml                # Production: PHP/Nginx/Vue/MySQL/Redis/queue/cloudflared
├── docker/                           # Dockerfile, entrypoint, Nginx, MySQL
├── backend/                          # Laravel API
│   ├── AGENTS.md                     # Hướng dẫn riêng của backend
│   ├── app/
│   │   ├── Enums/                    # Enum nghiệp vụ
│   │   ├── Http/Controllers/Api/     # Auth, menu, order, rating, reports, recommendation
│   │   ├── Http/Requests/            # Validation theo module
│   │   ├── Http/Resources/           # JSON resources
│   │   ├── Jobs/                     # Job embedding đồ uống và hồ sơ người dùng
│   │   ├── Models/                   # 7 Eloquent models
│   │   └── Services/                 # Profile, embedding, weather, recommendation và LLM rerank
│   ├── database/                     # 11 migrations, factories, seeders
│   ├── routes/api.php                # Nguồn route API thực tế
│   └── tests/                        # PHPUnit feature tests
├── frontend/                         # Vue SPA đang dùng
│   ├── src/views/                    # 9 view đang có route
│   ├── src/components/               # layout/menu/ui components
│   ├── src/stores/                   # Pinia auth/cart/menu
│   ├── src/services/api.js           # Axios client và toàn bộ API wrappers
│   └── src/assets/                   # Tailwind entry, fonts, design tokens
├── database/                         # SQL dump và các bảng tính dữ liệu menu
└── template_frontend/                # React/Figma reference, không phải app production
```

Scaffold Vue mặc định (`AboutView`, Hello/Welcome, icons mẫu và counter store) đã được xóa khỏi source production.

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
| Runtime | Docker image PHP 8.3, Node 22 build stage, Nginx 1.27 runtime |

Stack đã chốt Laravel 13/PHP 8.3; SPEC, Dockerfile và tài liệu triển khai phải giữ đồng bộ với `backend/composer.json`.

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
                                      ├── Weather API       [có cache + fallback]
                                      └── OpenAI API        [embedding + structured rerank]
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
| Preferences | `PreferenceController`, `UserProfileTextService` | `PreferenceView` | Explicit + profile text; embedding chạy qua queue |
| Menu | `DrinkController`, `DrinkResource` | `HomeView`, menu components | Public menu/filter có |
| Cart/checkout | `OrderController::store` | `cart.js`, `CheckoutView` | Core order có; chưa có size |
| Order history | `OrderController::history/show/cancel` | `OrderHistoryView` | Có phân trang API, UI hiện chỉ lấy trang đầu |
| Ratings | `RatingController` | rating form trong `OrderHistoryView` | Mỗi món/đơn chỉ gửi một lần, UI khóa sau khi đánh giá |
| Admin menu | admin methods của `DrinkController` | `AdminMenuView` | CRUD/availability/soft delete + upload ảnh có |
| Admin orders | admin methods của `OrderController` | `AdminOrdersView` | Filter, pagination, transition có |
| Notifications | `NotificationController`, database notifications | `NotificationCenter` | Chuông, panel, polling 10 giây và toast; admin nhận đơn mới, customer nhận cập nhật đơn |
| Reports | `ReportController` | `AdminReportsView` | Best seller + hiệu quả recommendation; cần dữ liệu vận hành thật |
| Recommendation | Controller/Services/Jobs + Redis worker | `RecommendationBanner` | Context public hiển thị trước; top 5 + lý do chỉ tải khi customer bấm; guest được mời đăng nhập |

### 5.3 Luồng chính đang hoạt động

Auth:

1. Register luôn tạo role `customer`; login xoá token cũ rồi tạo token `auth` mới.
2. Frontend lưu token và user vào localStorage; Axios thêm `Authorization: Bearer ...`.
3. Khi nhận 401 (trừ login), interceptor xoá local session và điều hướng `/login`.

Đặt hàng:

1. `HomeView` và `/checkout` là public; menu lọc ở client và giỏ nằm trong Pinia.
2. Customer dùng profile; guest nhập `customer_name`. Endpoint tạo đơn xác thực Sanctum nếu có bearer token, nếu không tạo đơn với `user_id = null`.
3. Backend validate món còn bán, mở transaction, snapshot giá vào `unit_price/subtotal`, tổng vào `orders.total_price`.
4. `context_snapshot` hiện lưu hour, order type, occasion, lat/lon; weather và temperature luôn `null`.
5. Customer chỉ hủy được `pending`; admin chuyển `pending → confirmed → done` hoặc `pending/confirmed → cancelled`.

Sở thích/feedback:

1. Preference dùng quan hệ 1-1 và `updateOrCreate`.
2. `UserProfileTextService` ghép tag/default/allergy + 5 món gần đây + 3 rating từ 4 sao.
3. Chỉ khi `profile_text` đổi mới dispatch `UpdateUserProfileEmbeddingJob`.
4. Job embedding gọi OpenAI qua queue `embeddings`; khi chưa có vector hoặc provider lỗi, API recommendation tự hạ cấp sang rule-based fallback.

## 6. Database nhanh

```text
users 1 ── 1 user_preferences
users 1 ── * orders 1 ── * order_items * ── 1 drinks
users 1 ── * ratings * ── 1 drinks
orders 1 ── * ratings
users 1 ── * recommendation_logs
users 1 ── * notifications
```

| Bảng | Dữ liệu quan trọng | Quy tắc |
|---|---|---|
| `users` | name, email, hashed password, role | email unique; role customer/admin |
| `user_preferences` | taste tags, default sugar/ice, allergy, profile text/vector | `user_id` unique, cascade khi xóa user |
| `drinks` | nội dung, category, price, temperature, tags, image URL/path, availability, vector | soft delete; upload ảnh lưu disk public; public chỉ thấy available và chưa xóa |
| `orders` | nullable user, customer name, status, total, context snapshot | guest dùng `user_id=null`; giá/ngữ cảnh/tên là snapshot |
| `order_items` | drink, quantity, sugar, ice, note, unit price, subtotal | giữ giá lúc mua; cascade theo order, restrict drink |
| `ratings` | user/drink/order, 1–5, comment | unique `(user_id, order_id, drink_id)`; chỉ đơn done; không sửa sau khi gửi |
| `recommendation_logs` | context, candidates, final rank, explanation | append-only, không có `updated_at` |
| `notifications` | UUID, polymorphic owner, type, JSON data, read_at | chỉ owner được đọc; lưu bền trong MySQL |

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
| GET | `/api/drinks/{drink}/image` | Public | stream ảnh upload của món |
| GET | `/api/recommendation-context` | Public | giờ/thời tiết/gợi ý chung; optional lat/lon |
| POST | `/api/orders` | Public + optional Sanctum | guest bắt buộc `customer_name`; token hợp lệ gắn order với user |

### Customer/authenticated

| Method | Route | Chức năng |
|---|---|---|
| GET/PUT | `/api/preferences` | xem/cập nhật preference |
| GET | `/api/orders/history` | lịch sử phân trang 10 |
| GET | `/api/orders/{order}` | chi tiết của owner hoặc admin |
| PATCH | `/api/orders/{order}/cancel` | owner hủy đơn pending |
| GET/POST | `/api/ratings` | list rating của mình/upsert rating hợp lệ |
| GET | `/api/notifications` | 30 thông báo gần nhất + unread count |
| PATCH | `/api/notifications/{id}/read`, `/api/notifications/read-all` | đánh dấu đã đọc, chỉ notification của user |
| GET | `/api/recommendations` | top 5 cá nhân hóa; tối đa 5 lần/phút/user |

### Admin

| Method | Route | Chức năng |
|---|---|---|
| GET/POST | `/api/admin/drinks` | list tất cả đang tồn tại/tạo món |
| GET/PUT/DELETE | `/api/admin/drinks/{drink}` | xem/sửa/soft-delete |
| POST | `/api/admin/drinks/{drink}/image` | upload JPG/PNG/WebP tối đa 5 MB |
| GET | `/api/admin/orders` | optional status/user_id; phân trang 15 |
| GET | `/api/admin/orders/{order}` | chi tiết + customer |
| PATCH | `/api/admin/orders/{order}/status` | chuyển trạng thái hợp lệ |
| GET | `/api/admin/reports/best-selling-drinks` | optional from/to/limit; mỗi món có `average_rating` theo thời điểm tạo đánh giá trong khoảng lọc |
| GET | `/api/admin/reports/recommendation-effectiveness` | optional from/to/window_hours/limit |

## 8. Frontend routes và UI conventions

| URL | View | Guard |
|---|---|---|
| `/login`, `/register` | Login/Register | guest |
| `/` | context chung + menu + cart | public |
| `/preferences` | preference form | authenticated |
| `/checkout` | cart/checkout/confirmation | public, cart không rỗng |
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

Mặc định theo root `.env.example`, toàn bộ traffic đi qua `https://drinks.hmmh.click`; Cloudflare Tunnel trỏ nội bộ tới `http://site:80`. Database, Redis, PHP-FPM và Nginx backend không publish cổng host.

Lệnh thường dùng:

```bash
# Backend trong container
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app ./vendor/bin/phpunit
docker compose exec app ./vendor/bin/pint --dirty
docker compose exec app php artisan queue:work

# Frontend được lint + production build trong image site
docker compose build site
```

Biến môi trường OpenAI/Weather đã được khai báo trong root/backend `.env.example`, truyền qua Docker và ánh xạ tại `backend/config/services.php`; application code chỉ đọc qua `config()`. Không ghi giá trị thật vào source hoặc tài liệu.

`docker-compose.yml` có service `queue` chạy hàng đợi `embeddings,default`, restart tự động và healthcheck. Lệnh backfill chỉ được chạy sau khi `check_api_keys.php` xác nhận credential hợp lệ.

### Trạng thái xác minh tại lần rà soát

- 2026-10-01: Docker hoạt động; danh mục 50 món được đồng bộ và API local/domain đều trả đủ 50 món.
- Backend `php artisan test --filter=Drink`: 15 test, 58 assertion pass; `DrinkSeederTest` xác nhận test dùng SQLite in-memory và database MySQL chạy thật giữ nguyên.
- Frontend full lint và production build đều pass; hai biến `props` không dùng trong `DrinkCard.vue` và `FormField.vue` đã được loại bỏ.
- 2026-10-01: full backend suite **54 test / 199 assertion pass**; frontend full lint và production build pass; Nginx config test pass. API menu sau khi warm OPcache ổn định khoảng 0,15–0,20 giây local và đo qua domain khoảng 0,35 giây.
- 2026-10-01: thêm UC-04 end-to-end và 13 test cho API/ranking/fallback/embedding/weather/jobs; test suite dùng fake/mock và không sử dụng key thật.
- 2026-10-01: OpenAI và OpenWeather đều HTTP 200; worker consume đủ 50 job menu, 50/50 món có vector, 0 failed job; weather live thành công; recommendation authenticated trả 5 món `hybrid_llm` trong khoảng 5,4 giây local và 4,0 giây qua `drinks.hmmh.click`.
- 2026-10-01: full backend suite sau UC-04 **67 test / 261 assertion pass**; frontend full lint và production build pass; Pint pass 15 file UC-04.
- 2026-10-01: sau khi thêm context công khai, guest checkout và JSON 401 ổn định cho API, full backend suite **72 test / 292 assertion pass**; frontend full lint/build và Pint đều pass.
- 2026-10-01: thêm notification database/polling/toast, upload ảnh, khóa rating và throttle gợi ý theo user; full backend suite **79 test / 323 assertion pass**, frontend lint/build và Pint pass.
- 2026-10-02: chuyển Compose sang production-only qua `drinks.hmmh.click`, bỏ Vite dev/phpMyAdmin/host ports/source bind mounts và xóa scaffold Vue; production build tự lint frontend, full backend PHPUnit **79 test / 323 assertion pass**, 7 service healthy, API domain HTTP 200; MySQL giữ 50 món, 4 user và 8 đơn.

## 10. Test map hiện có

Backend hiện có **73 feature test methods** (trong đó 1 test skeleton) + **6 unit test methods** (trong đó 1 placeholder), tổng cộng 79 test methods trong source.

| Test file | Bao phủ |
|---|---|
| `AuthTest.php` | register/login/me/logout, duplicate/invalid credentials |
| `DrinkTest.php` | public menu/filter/image, availability, admin authorization/CRUD/upload/job dispatch |
| `DrinkSeederTest.php` | đồng bộ 50 món, giữ món tùy chỉnh và không ghi đè menu khi Docker restart |
| `PreferenceTest.php` | defaults, 1-1 update, profile text, không dispatch trùng |
| `OrderTest.php` | customer/guest create, optional token, snapshot/defaults/order type/history/ownership/cancel |
| `AdminOrderTest.php` | admin auth/filter/detail/status transitions và hiển thị đơn guest |
| `RatingTest.php` | eligibility, ownership, 1–5, khóa sau lần gửi đầu, profile job |
| `ReportTest.php` | best seller/date filter/recommendation conversion |
| `RecommendationTest.php` | public context, auth top 5, rate-limit theo user, validation, schema/reason/log, unavailable/empty fallback, top-10 và structured rerank |
| `NotificationTest.php` | thông báo admin/customer/guest, ownership/read và cập nhật trạng thái đơn |
| `EmbeddingJobTest.php` | nguồn embedding, ghi/xóa vector, record đã xóa |
| `Unit/Services/EmbeddingServiceTest.php` | cosine edge cases, response hợp lệ và malformed |
| `Unit/Services/WeatherServiceTest.php` | normalize/cache và provider failure fallback |

Khoảng trống test lớn nhất:

- Frontend chưa có Vitest/component/E2E tests trong `package.json`.
- `ExampleTest.php` và `Unit/ExampleTest.php` chỉ là skeleton.

## 11. Task tracker

### 11.1 Đã hoàn thành trong code

- [x] **DOC-05 — Gom tài liệu và hướng dẫn triển khai từ GitHub**: chuyển 16 tài liệu dự án vào `doc/`, thêm mục lục `doc/README.md`, README root và `DEPLOY.md` hướng dẫn clone, hai file môi trường, tạo APP_KEY bằng Docker, Cloudflare, khởi tạo menu/admin, tích hợp ngoài, kiểm tra/cập nhật và bảo toàn dữ liệu. Giữ hướng dẫn agent tại vị trí tự nhận diện và dữ liệu SQL/XLSX trong `database/`. Bằng chứng: ngày 2026-10-02 kiểm tra 31 liên kết Markdown nội bộ, code fence của 20 tài liệu, Compose `config --quiet` bằng env mẫu và `git diff --check` đều pass; chỉ thay đổi tài liệu, không chạy lại ứng dụng hoặc triển khai lên domain.
- [x] **DOC-00 — Agent project guide và task tracker**: kiến trúc, module/API/schema/runtime, test map, rủi ro, backlog có acceptance criteria và quy tắc cập nhật đã được tổng hợp trong file này. Bằng chứng: `AGENTS.md`, audit source ngày 2026-09-03.
- [x] **DOC-04 — Báo cáo quản lý dự án và khởi tạo Taiga**: báo cáo Word 51 trang theo ba chương khởi tạo, triển khai và kết thúc, kế hoạch 6 người trong 8 tuần; tạo dự án Scrum riêng tư trên Taiga với 4 Epic, 20 story (82 điểm), 4 sprint, 5 task mẫu và 8 trang Wiki quản lý cùng trang tổng quan. Đã thêm 4 tài khoản Active, 2 thành viên chờ thông tin. Bằng chứng: `output/bao_cao_taiga/Bao_cao_Quan_ly_du_an_Smart_Drink_Taiga.docx`, `output/bao_cao_taiga/minh_chung/*`, https://tree.taiga.io/project/alisa12405-smart-drink-web-app/backlog; ngày 2026-10-01 đã cập nhật mục lục bằng Word và kiểm tra hình render đủ 51 trang. Chỉ hoàn tất hồ sơ và cấu hình quản lý; story giữ New, chưa chạy test ứng dụng hoặc nghiệm thu sản phẩm.
- [x] **DOC-03 — Tài liệu phân tích và thiết kế hệ thống**: bộ tài liệu Markdown theo hướng khảo sát/phân tích/thiết kế trước khi lập trình, dùng cửa hàng nhỏ Mộc Nhiên Coffee làm tình huống nghiên cứu, gồm phỏng vấn, 19 câu Google Forms, 31 UC con thuộc 10 nhóm, biểu đồ UC/trình tự/hoạt động/thành phần, kiến trúc, lớp/triển khai, cơ sở dữ liệu và API/UI/an toàn. Bằng chứng: `tai_lieu_phan_tich_thiet_ke_he_thong/*.md`; cập nhật Component Diagram theo bố cục mẫu ngày 2026-09-15; kiểm tra liên kết, code fence và whitespace đã pass.
- [x] **FOUND-01 — Docker production full stack**: Cloudflare Tunnel → static Vue/Nginx → Laravel Nginx/PHP-FPM, MySQL, Redis và queue worker; không publish service nội bộ, không bind mount source. Bằng chứng: `docker-compose.yml`, `docker/*`, `Makefile`, `doc/README-DOCKER.md`.
- [x] **DB-01 — Schema nghiệp vụ và Eloquent models**: 8 bảng nghiệp vụ gồm notifications, enum, quan hệ, casts, fillable, soft delete và constraints đã được hiện thực. Bằng chứng: `backend/database/migrations/*`, `backend/app/Models/*`, feature tests.
- [x] **DATA-01 — Factory/seeder demo**: customer/admin mẫu và danh mục 50 món từ `database/drinks_menu.xlsx` đã có; seeder đồng bộ theo tên và không xóa món ngoài danh mục. Bằng chứng: `backend/database/data/drinks_menu.csv`, `DatabaseSeeder`, `DrinkSeeder`, `MissingDrinkSeeder`, `UserFactory`, `DrinkFactory`.
- [x] **DATA-02 — SQL dump dữ liệu mẫu**: dump idempotent vẫn được giữ để nạp thủ công khi cần; production chỉ tự chạy migration, không import/seed lại lúc restart nên không ghi đè dữ liệu vận hành. Bằng chứng: `database/dump.sql`, `docker/php/entrypoint.sh`, `doc/README-DOCKER.md`.
- [x] **OPS-02 — Kiểm tra API key ngoài hệ thống**: script PHP không lộ secret kiểm tra OpenAI và OpenWeather/WeatherAPI theo provider đã chọn. Bằng chứng: `backend/scripts/check_api_keys.php`; 2026-10-01 chạy trong Docker, OpenAI và OpenWeather đều trả HTTP 200; `test_api.md` chỉ lưu kết quả đã làm sạch.
- [x] **UC-01 — Đăng ký/đăng nhập/đăng xuất/profile**: Sanctum token, API, Pinia và UI đã có. Bằng chứng: Auth controller/routes/store/views + `AuthTest`.
- [x] **UC-02A — Khai báo/cập nhật sở thích explicit**: 1 preference/user, defaults, tag/sugar/ice/allergy UI/API. Bằng chứng: Preference module + `PreferenceTest`.
- [x] **UC-02B — Tổng hợp profile text**: sở thích + lịch sử mua + rating cao, tránh dispatch khi text không đổi. Bằng chứng: `UserProfileTextService` + tests.
- [x] **UC-03 — Xem menu và lọc category**: public API/UI chỉ lấy available; guest xem menu, thời gian/thời tiết và gợi ý chung không cần đăng nhập. Bằng chứng: Drink module, context route, HomeView + tests.
- [x] **UC-05A — Giỏ hàng và tạo đơn cốt lõi**: customer/guest dùng chung giỏ và checkout; guest nhập tên, order lưu nullable user + customer name; quantity/sugar/ice/note/order type/occasion, transaction và price snapshot. Bằng chứng: Order module/cart/Checkout + `OrderTest`.
- [x] **UC-06 — Lịch sử/chi tiết/hủy đơn**: ownership và rule chỉ customer hủy pending. Bằng chứng: Order controller/UI/tests.
- [x] **UC-07A — Rating/feedback cốt lõi**: chỉ món thuộc order done, unique và khóa sau lần gửi đầu, list rating của chính user. Bằng chứng: Rating module/UI + `RatingTest`.
- [x] **UC-08A — Admin CRUD menu**: role guard, create/update/availability/soft delete, upload/replace ảnh và UI không hiện mã UC. Bằng chứng: Drink controller/admin routes/AdminMenu + `DrinkTest`.
- [x] **NOTIFY-01 — Thông báo đơn hàng online**: lưu database; chuông/panel/polling 10 giây/toast; admin nhận đơn mới, customer nhận đặt hàng thành công và trạng thái mới; ownership/read API. Bằng chứng: notification classes/controller/routes, `NotificationCenter.vue`, `NotificationTest`.
- [x] **UC-09 — Admin quản lý đơn**: list/filter/detail/pagination và state machine. Bằng chứng: Order controller/AdminOrders + `AdminOrderTest`.
- [x] **UC-10A — Báo cáo món bán chạy**: chỉ đếm order done, date range, quantity/revenue và điểm đánh giá trung bình theo khoảng lọc. Bằng chứng: `ReportController`, `AdminReportsView`, `ReportTest`; 2026-09-03 `ReportTest` 7/7 pass, lint riêng `AdminReportsView` và frontend build pass (full lint còn lỗi cũ ở `DrinkCard.vue`/`FormField.vue`).
- [x] **UI-01 — Design system và 9 màn hình**: Tailwind tokens/components dùng chung, responsive layout, customer/admin views; menu rộng tối đa 1800px, dùng thẻ lớn 3 cột trên màn rộng, ảnh trọn khung và phân trang 12 món/trang. Bằng chứng: `frontend/src`, `doc/UI_DESIGN_TEMPLATE.md`; 2026-10-01 frontend lint/build pass.

### 11.2 Ưu tiên P0 — cần làm để hoàn thành tính năng cốt lõi

- [x] **REC-01 — Chốt contract API recommendation**
  - Acceptance: có Request validate `lat/lon/occasion` (hoặc contract được chốt khác), Resource/JSON schema cho từng món + score + explanation, fallback schema giống success schema.
  - Acceptance: thêm route authenticated `GET /api/recommendations` và controller không chứa logic tích hợp chi tiết.
  - Bằng chứng: `GetRecommendationRequest`, `RecommendationResource`, `RecommendationController`, `routes/api.php`, `RecommendationTest`.
- [x] **REC-02 — Implement `EmbeddingService`**
  - Acceptance: gọi model embedding đã chốt qua config; timeout/retry/error handling; validate vector; cosine similarity xử lý vector rỗng, khác chiều, zero norm.
  - Acceptance: unit tests bao phủ cosine và failure paths; không log API key/input nhạy cảm.
  - Bằng chứng: `EmbeddingService`, `EmbeddingServiceTest`; HTTP được fake trong test.
- [x] **REC-03 — Hoàn thiện hai embedding jobs**
  - Acceptance: `UpdateDrinkEmbeddingJob` ghép `name + description + ingredients + tags`, ghi `description_embedding` nếu nguồn đổi.
  - Acceptance: `UpdateUserProfileEmbeddingJob` embed `profile_text`, ghi `profile_embedding`; retry/backoff/failed behavior rõ ràng.
  - Acceptance: sửa `DrinkController::update()` để thay đổi `description` cũng trigger job.
  - Bằng chứng: hai job queue `embeddings`, `DrinkController`, `EmbeddingJobTest`, command backfill.
- [x] **REC-04 — Implement `WeatherService`**
  - Acceptance: lấy weather/temperature theo lat/lon, cache theo tọa độ + time bucket khoảng 30 phút, timeout/fallback `null`, fake HTTP trong test.
  - Bằng chứng: `WeatherService`, `WeatherServiceTest`.
- [x] **REC-05 — Implement `RecommendationService` end-to-end**
  - Acceptance: chỉ món available; weather-aware pre-filter; fallback khi thiếu location/weather/vector/API.
  - Acceptance: cosine rank → top 10 → LLM structured re-rank top 5; không gửi toàn menu.
  - Acceptance: ghi `recommendation_logs` với context/candidate/final/explanation theo đúng thứ tự.
  - Bằng chứng: `RecommendationService`, `RecommendationReranker`, `RecommendationTest`; OpenAI lỗi vẫn trả fallback cùng schema.
- [x] **REC-06 — Nối recommendation vào frontend**
  - Acceptance: wrapper trong `services/api.js`; `RecommendationBanner`/Home hiển thị loading/error/fallback/results và cho thêm món vào cart.
  - Acceptance: xin vị trí là tùy chọn; từ chối vị trí vẫn có gợi ý rule-based.
  - Bằng chứng: `services/api.js`, `RecommendationBanner.vue`; context giờ/thời tiết/gợi ý chung hiển thị trước, top 5 chỉ tải khi bấm, có lý do tổng quát/từng món, popup đăng nhập/sở thích; frontend lint/build pass ngày 2026-10-01.
- [x] **REC-07 — Test UC-04**
  - Acceptance: feature tests cho auth/validation/logging/ranking/fallback; unit tests services/jobs; external calls đều fake.
  - Acceptance: đo response time cục bộ và tránh N+1/call OpenAI lặp.
  - Bằng chứng: `RecommendationTest`, service/unit/job/order/admin-order tests; full suite 79/323 pass; smoke live một lần re-rank trả 5 món trong khoảng 5,4 giây.
  - Rate-limit: 5 request/phút theo user; UI đếm ngược theo `Retry-After`; test xác nhận request vượt ngưỡng nhận 429.
- [x] **OPS-01 — Bảo đảm queue worker chạy**
  - Acceptance: thêm worker process/service phù hợp Docker, health/restart policy, tài liệu command; chứng minh job thực sự được consume.
  - Bằng chứng: service `queue` healthy; backfill 50 món được consume đủ, 50 vector được ghi, Redis queue trống và failed job bằng 0.
- [x] **DOC-01 — Đồng bộ stack Laravel 13 và tài liệu triển khai**
  - Acceptance: chốt version mục tiêu; đồng bộ SPEC/README/Docker comments/composer.
  - Bằng chứng: `doc/SPEC_smart-drink-recommendation-app.md`, PHP Dockerfile, `doc/README-DOCKER.md`, `doc/CLOUDFLARE_DEPLOYMENT.md`; production chốt Laravel 13/PHP 8.3.

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

- [x] **CLEAN-01 — Xóa scaffold/dev runtime không dùng**: đã xóa About/Hello/Welcome/icons/counter, Vite devtools, Docker Node dev service và phpMyAdmin khỏi stack production; frontend dùng `/api` cùng origin. Bằng chứng: `frontend/src`, `frontend/package.json`, `docker-compose.yml`; lint/build trong image site.
- [ ] **DOC-02 — Viết README root chuẩn**: quick start, accounts demo, architecture link, troubleshooting, không chứa secret.
- [ ] **AUTH-01 — Chốt quản lý user admin**: phần Actor nói admin quản lý user nhưng UC/API/UI chưa có; quyết định thêm use case hay bỏ khỏi phạm vi.
- [ ] **AUTH-02 — Chốt chiến lược token/session**: hiện login/logout xóa toàn bộ token của user; quyết định single-device hay multi-device và sửa tests/docs.
- [x] **MEDIA-01 — Ảnh món upload**: Admin tải JPG/PNG/WebP tối đa 5 MB, file lưu trong `backend/storage/app/public/drinks`, thay ảnh dọn file cũ, Resource trả endpoint stream public và UI có preview. Bằng chứng: migration `image_path`, upload request/controller/routes, `AdminMenuView`, `DrinkTest`.
- [ ] **UI-02 — Self-host hoặc fallback font**: Google Fonts là network dependency; bảo đảm UI vẫn ổn offline/Docker demo.
- [ ] **OBS-01 — Logging/monitoring tích hợp ngoài**: log request id/provider latency/fallback/queue failure mà không lộ dữ liệu nhạy cảm.
- [ ] **PERF-01 — Cache menu/weather/recommendation có invalidation**: menu đã có Pinia + `localStorage` stale-while-revalidate 5 phút, preload từ màn auth, refresh sau CRUD admin và cache ảnh Nginx 7 ngày (2026-10-01); còn cache weather/recommendation và automated test cache key/TTL trước khi đánh dấu hoàn tất.

## 12. Các lệch/rủi ro đã biết

1. **Production cần secret ngoài Git:** deploy yêu cầu token Cloudflare/root `.env` và `APP_KEY` ổn định trong `backend/.env`; không có secret mặc định dùng được.
2. **Quyền riêng tư profile embedding chưa chốt:** menu đã có 50/50 vector nhưng hồ sơ thật vẫn 0 vector; chưa gửi `profile_text` có thể chứa allergy note sang OpenAI khi chưa có quyết định đồng ý/tối thiểu hóa dữ liệu.
3. **Latency LLM phụ thuộc provider:** smoke live khoảng 5,4 giây; timeout riêng đã tăng lên 15 giây và hệ thống vẫn fallback khi lỗi/chậm.
4. **Frontend UC-04 chưa có automated test:** backend có fake HTTP đầy đủ, nhưng banner mới hiện chỉ được kiểm tra bằng lint/build và cần Vitest/E2E.
5. **Hiệu quả recommendation chưa nghiệm thu bằng dữ liệu vận hành:** log API đã có, nhưng báo cáo cần lượt gợi ý và đơn hàng thật để đối chiếu conversion.
6. **FR1 chưa đủ:** profile text không tự sync ngay sau khi order thay đổi/hoàn tất.
7. **Context chưa đủ:** order snapshot có vị trí nhưng weather/temperature luôn null.
8. **Size bị thiếu:** UC-05 trong SPEC nhắc size nhưng DB/API/UI không có.
9. **Schema phải tiếp tục được đồng bộ:** mọi migration/model mới cần cập nhật `doc/database/DATABASE_SCHEMA.md` và `doc/database/tables/*.md` trong cùng task.
10. **Frontend history mất phần sau trang 1:** API paginate nhưng UI không điều hướng trang.
11. **Frontend không có automated tests:** chỉ có lint/build lịch sử.
12. **Artefact tham khảo:** `template_frontend/` và `template_frontend.zip` chỉ là nguồn thiết kế, không được copy vào image production nhờ `.dockerignore`.
13. **Dev runtime phụ thuộc Docker:** Docker hoạt động và đã dùng để test/build; host không cần cài riêng PHP/Node.
14. **Auth client tin user cache:** route guard dùng `auth_user` trong localStorage; quyền thật vẫn được backend bảo vệ, nhưng UI có thể tạm hiển thị sai tới khi API trả 401/403.
15. **Report conversion cần định nghĩa:** query hiện coi bất kỳ order status nào trong window là conversion; cần xác nhận nghiệp vụ.
16. **Notification là near-real-time qua polling:** độ trễ tối đa khoảng 10 giây; nếu cần push tức thời ở quy mô lớn thì chuyển sang WebSocket/SSE và bổ sung hạ tầng broadcast.

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
