# Triển khai Smart Drink bằng Docker và Cloudflare Tunnel

Đường chạy mặc định là production tại `https://drinks.hmmh.click`:

```text
Internet → Cloudflare Tunnel → site (Vue/Nginx) → webserver → app (Laravel/PHP-FPM)
                                                ├─ MySQL
                                                └─ Redis/queue worker
```

MySQL, Redis, PHP-FPM và Nginx nội bộ không publish cổng ra máy host. Frontend dùng `/api`, nên không còn URL localhost trong production. Source code cũng không bind mount vào container; mỗi lần triển khai sẽ build image bất biến từ code hiện tại.

## Chuẩn bị

1. Sao chép `.env.example` thành `.env`, điền mật khẩu, API key và `CLOUDFLARE_TUNNEL_TOKEN`.
2. Giữ `APP_KEY` hợp lệ trong `backend/.env`. Có thể tạo một lần bằng `php artisan key:generate` trong môi trường Laravel; không thay key sau khi đã có dữ liệu mã hóa/session.
3. Trong Cloudflare Zero Trust, cấu hình public hostname `drinks.hmmh.click` trỏ đến service `http://site:80`.

Không commit `.env` hoặc `backend/.env`.

## Triển khai

```bash
make deploy
make ps
make logs
```

`make deploy` tương đương `docker compose up -d --build --remove-orphans`. Lệnh này chạy migration an toàn bằng `php artisan migrate --force`, tạo cache Laravel, khởi động worker và loại bỏ container development cũ. Dữ liệu vẫn nằm trong các named volume:

- `drink-app-db-data`: dữ liệu MySQL.
- `drink-app-redis-data`: cache/queue Redis.
- `drink-app-storage`: ảnh món và Laravel storage dùng chung giữa app/worker.

Không dùng `docker compose down -v` trong vận hành thông thường vì tùy chọn `-v` xóa dữ liệu volume.

## Kiểm tra

```bash
docker compose config --quiet
docker compose ps
docker compose exec app php artisan about
docker compose exec app php artisan test
```

Kiểm tra ngoài Internet tại `https://drinks.hmmh.click` và `https://drinks.hmmh.click/api/drinks`.

## Cập nhật dữ liệu mẫu theo yêu cầu

Production không tự import `database/dump.sql` và không tự seed mỗi lần khởi động. Khi thật sự cần đồng bộ danh mục, chạy thủ công:

```bash
docker compose exec app php artisan db:seed --class='Database\Seeders\DrinkSeeder' --force
```

## Lệnh vận hành

```bash
make restart
make logs-cloudflare
make sh
make test
make down
```

Sau khi sửa code, luôn chạy lại `make deploy`; restart đơn thuần không đưa source mới vào image production.
