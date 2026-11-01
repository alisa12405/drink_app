# Smart Drink — Ứng dụng đặt đồ uống & gợi ý cá nhân hóa thông minh

> Dự án web app đặt đồ uống tích hợp gợi ý cá nhân hóa dựa trên sở thích, lịch sử mua hàng, đánh giá và ngữ cảnh thời gian thực (thời gian, thời tiết, nhiệt độ) sử dụng OpenAI Embeddings (`text-embedding-3-small`) và LLM Re-ranking (`gpt-4o-mini`).

[![Laravel](https://img.shields.io/badge/Laravel-13.x-red.svg)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue-3.x-green.svg)](https://vuejs.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.x-blue.svg)](https://tailwindcss.com)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED.svg)](https://www.docker.com)
[![Status](https://img.shields.io/badge/Deployment-Production-success.svg)](https://drinks.hmmh.click)

---

## 1. Trạng thái triển khai & Tài khoản vận hành

Dự án hiện đang chạy thực tế bằng **Docker build** tại tên miền: **[https://drinks.hmmh.click](https://drinks.hmmh.click)** (kết nối an toàn qua Cloudflare Tunnel).

### Thông tin tài khoản quản trị (Admin)
Hệ thống Docker build hiện tại đã được cấu hình và vận hành với tài khoản quản trị viên:
- **URL đăng nhập**: [https://drinks.hmmh.click/login](https://drinks.hmmh.click/login)
- **Tài khoản**: `admin1@drinks.hmmh.click`
- **Mật khẩu**: `Admin@3618`
- **Vai trò**: `admin` (toàn quyền quản trị tài khoản người dùng, menu đồ uống, xử lý đơn hàng và xem báo cáo doanh thu).

*(Khách hàng có thể tự đăng ký tài khoản mới tại `/register` hoặc sử dụng chế độ Khách vãng lai để đặt món trực tiếp).*

---

## 2. Tính năng chính

### 2.1. Khách vãng lai (Guest)
- Xem menu 50 món uống đa dạng kèm bộ lọc danh mục (trà sữa, cà phê, trà trái cây, nước ép...).
- Xem banner ngữ cảnh thời gian thực (giờ máy chủ, thời tiết, nhiệt độ) và nhận gợi ý chung theo khung giờ mà không cần đăng nhập.
- Thêm món vào giỏ hàng, tùy chỉnh mức đường, đá, ghi chú và loại đơn (dùng tại chỗ / mang đi).
- Đặt hàng nhanh bằng tên khách hàng (`customer_name`); đơn hàng lưu snapshot giá và ngữ cảnh.

### 2.2. Khách hàng (Customer)
- Đăng ký, đăng nhập bảo mật qua Laravel Sanctum token.
- Khai báo hồ sơ sở thích cá nhân: mức đường/đá mặc định, các tag hương vị yêu thích (`ít_ngọt`, `trái_cây`, `có_caffeine`...), ghi chú dị ứng.
- **Gợi ý cá nhân hóa thông minh (UC-04)**: Nhận top 5 đồ uống phù hợp nhất dựa trên vector ngữ nghĩa kết hợp bối cảnh thời tiết và LLM re-rank; kèm câu tóm tắt tổng quan và lý do giải thích cho từng món. Tự động chuyển sang rule-based fallback khi thiếu vị trí hoặc dịch vụ ngoài gián đoạn.
- Xem lịch sử đơn hàng phân trang, theo dõi trạng thái đơn hàng.
- Hủy đơn hàng khi còn ở trạng thái `pending`.
- Đánh giá món uống sau khi hoàn thành đơn (1–5 sao kèm bình luận); đánh giá được khóa sau khi gửi và tự động cập nhật vào profile text để tối ưu gợi ý.
- Trung tâm thông báo thời gian thực (polling 10 giây và popup toast) khi đặt hàng thành công hoặc trạng thái đơn thay đổi.

### 2.3. Quản trị viên (Admin)
- **Quản lý tài khoản (`/admin/users`)**: Tìm kiếm theo tên/email, lọc theo vai trò (`customer`/`admin`) và trạng thái; chỉnh sửa thông tin, đổi vai trò, kích hoạt hoặc vô hiệu hóa tài khoản (thu hồi token ngay lập tức); bảo vệ an toàn cho admin hiện tại và admin active cuối cùng.
- **Quản lý thực đơn (`/admin/menu`)**: Thêm, sửa, soft-delete món uống; bật/tắt trạng thái phục vụ (`is_available`); tải lên ảnh món (JPG/PNG/WebP tối đa 5 MB) lưu disk public với cơ chế tự dọn ảnh cũ; tự động phát job tính lại vector embedding khi thay đổi nội dung món.
- **Quản lý đơn hàng (`/admin/orders`)**: Theo dõi danh sách đơn hàng real-time, lọc theo trạng thái và khách hàng; xem chi tiết từng món và snapshot ngữ cảnh; thực hiện chuyển trạng thái theo quy trình (`pending → confirmed → done`, hoặc hủy đơn). Nhận chuông thông báo khi có đơn mới.
- **Báo cáo & Thống kê (`/admin/reports`)**: Thống kê tổng doanh thu toàn khoảng lọc độc lập với danh sách món; bảng xếp hạng món bán chạy kèm số lượng bán, doanh thu và điểm đánh giá trung bình; báo cáo hiệu quả chuyển đổi từ hệ thống gợi ý.

---

## 3. Kiến trúc hệ thống & Công nghệ

```text
Internet
   │
   ▼ (HTTPS)
Cloudflare Edge
   │ (Cloudflare Tunnel)
   ▼
[site] Nginx Container (Vue 3 Static Build)
   │
   ├── /       ──> Giao diện Vue 3 SPA
   └── /api/*  ──> [webserver] Nginx Reverse Proxy
                           │
                           ▼
                  [app] Laravel 13 (PHP-FPM 8.3)
                           ├── [db] MySQL 8.0 (Dữ liệu giao dịch, JSON vector)
                           ├── [redis] Redis 7 (Cache, Session, Queue)
                           ├── [queue] Laravel Queue Worker (Embeddings)
                           ├── OpenAI API (Embeddings + Structured Rerank)
                           └── Weather API (OpenWeather / WeatherAPI)
```

| Tầng | Công nghệ sử dụng | Ghi chú |
|---|---|---|
| **Backend** | Laravel 13, PHP 8.3, Sanctum 4.3, PHPUnit 12 | Clean architecture: Form Request, Controller, Service, Resource, Queued Job |
| **Frontend** | Vue 3.5 (Composition API), Pinia 4, Vue Router 5, Axios | SPA tối ưu hóa, Tailwind CSS v4, Lucide Vue Next, Google Fonts (Inter + Poppins) |
| **Database** | MySQL 8.0 | 8 bảng nghiệp vụ (users, preferences, drinks, orders, items, ratings, logs, notifications) |
| **Cache/Queue** | Redis 7 Alpine | Cache thời tiết (TTL 30p), queue worker xử lý embedding bất đồng bộ |
| **AI / NLP** | OpenAI `text-embedding-3-small` + `gpt-4o-mini` | Embeddings 1536 chiều, Structured Outputs re-rank top ứng viên |
| **Weather** | OpenWeather API / WeatherAPI | Tự động chuẩn hóa thời tiết và nhiệt độ theo tọa độ địa lý |
| **Deployment** | Docker Compose (7 services), Cloudflare Tunnel | Build production bất biến, không publish port thô ra ngoài Internet |

---

## 4. Hướng dẫn cài đặt & Triển khai nhanh

Hướng dẫn chi tiết từng bước xem tại [DEPLOY.md](DEPLOY.md).

### 4.1. Chuẩn bị môi trường
- Máy chủ đã cài đặt **Docker** và **Docker Compose V2**.
- Tên miền quản lý trên Cloudflare và token Cloudflare Tunnel.

### 4.2. Clone mã nguồn và cấu hình
```bash
git clone https://github.com/alisa12405/drink_app.git
cd drink_app

# Sao chép file cấu hình mẫu
cp .env.example .env
cp backend/.env.example backend/.env
```

Điền các thông tin trong `.env`:
- `APP_URL=https://drinks.hmmh.click`
- `CLOUDFLARE_TUNNEL_TOKEN=<token-tunnel-cua-ban>`
- `DB_PASSWORD` & `DB_ROOT_PASSWORD`
- `OPENAI_API_KEY` & `WEATHER_API_KEY` (tùy chọn)

### 4.3. Sinh APP_KEY và Khởi động
```bash
# Build image backend
docker compose build app

# Sinh key mã hóa Laravel một lần vào backend/.env
docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show
# (Sao chép key in ra vào dòng APP_KEY= trong backend/.env)

# Khởi chạy toàn bộ 7 service
docker compose up -d --build --remove-orphans
docker compose ps
```

### 4.4. Khởi tạo dữ liệu
```bash
# Nạp danh mục 50 món uống
docker compose exec app php artisan db:seed --class=DrinkSeeder --force

# (Tùy chọn) Tính vector embedding ban đầu cho 50 món uống
docker compose exec app php artisan recommendations:backfill-embeddings --drinks
```

---

## 5. Cấu trúc thư mục dự án

```text
drink_app/
├── README.md                         # Tài liệu giới thiệu tổng quan dự án (file này)
├── DEPLOY.md                         # Hướng dẫn chi tiết triển khai từ GitHub
├── AGENTS.md                         # Quy tắc và task tracker dành cho AI coding agents
├── docker-compose.yml                # Cấu hình 7 container production
├── Makefile                          # Lệnh tắt deploy, test, logs, shell
├── database/                         # File dump SQL và danh mục menu XLSX/CSV
├── docker/                           # Dockerfiles cho PHP, Nginx, Vue, MySQL
├── doc/                              # Thư mục toàn bộ tài liệu dự án
│   ├── README.md                     # Danh mục tài liệu tổng hợp
│   ├── SPEC_smart-drink-recommendation-app.md  # Đặc tả nghiệp vụ và use cases
│   ├── UI_DESIGN_TEMPLATE.md         # Quy chuẩn thiết kế giao diện & design tokens
│   ├── README-DOCKER.md              # Hướng dẫn vận hành Docker chuyên sâu
│   ├── CLOUDFLARE_DEPLOYMENT.md      # Cấu hình Cloudflare Tunnel
│   ├── HUONG_DAN_TRIEN_KHAI_GOI_Y_CA_NHAN.md  # Tài liệu thuật toán gợi ý (UC-04)
│   └── database/                     # Thiết kế cơ sở dữ liệu và chi tiết từng bảng
├── backend/                          # Mã nguồn Laravel 13 API
│   ├── app/                          # Controllers, Services, Models, Jobs, Resources
│   ├── database/                     # Migrations, seeders, factories
│   ├── routes/api.php                # Định nghĩa toàn bộ REST API endpoints
│   └── tests/                        # PHPUnit feature & unit tests
└── frontend/                         # Mã nguồn Vue 3 SPA
    ├── src/views/                    # Các màn hình chính (Home, Menu, Checkout, Admin...)
    ├── src/components/               # Layout, components giao diện tái sử dụng
    ├── src/stores/                   # State management Pinia (auth, cart, menu)
    └── src/services/api.js           # Client gọi API thống nhất
```

---

## 6. Danh mục tài liệu tham khảo

- [Hướng dẫn triển khai chi tiết](DEPLOY.md)
- [Đặc tả nghiệp vụ hệ thống (SPEC)](doc/SPEC_smart-drink-recommendation-app.md)
- [Thiết kế cơ sở dữ liệu (Database Schema)](doc/database/DATABASE_SCHEMA.md)
- [Hướng dẫn vận hành Docker](doc/README-DOCKER.md)
- [Thuật toán và vận hành gợi ý cá nhân hóa](doc/HUONG_DAN_TRIEN_KHAI_GOI_Y_CA_NHAN.md)
- [Quy chuẩn thiết kế giao diện UI](doc/UI_DESIGN_TEMPLATE.md)
- [Hướng dẫn dành cho Coding Agent](AGENTS.md)
