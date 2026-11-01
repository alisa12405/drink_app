# Triển khai `drinks.hmmh.click` qua Cloudflare Tunnel

> Đây là ghi chú triển khai cho tên miền hiện có. Khi triển khai riêng từ GitHub, xem [DEPLOY.md](../DEPLOY.md) và thay hostname bằng tên miền bạn quản lý. Chạy các lệnh từ root repository.

Kiến trúc triển khai:

```text
Internet -> Cloudflare -> cloudflared -> site (Nginx)
                                       ├── /       Vue static build
                                       └── /api/*  Laravel Nginx/PHP-FPM
```

Cloudflare Tunnel chỉ tạo kết nối outbound từ máy chạy Docker. Không cần mở port 80/443 trên router, và không trỏ A record vào IP nhà/máy chủ.

## 0. Trạng thái vận hành hiện tại

Hệ thống đang hoạt động trên Docker build kết nối qua Cloudflare Tunnel:
- **Tên miền**: [https://drinks.hmmh.click](https://drinks.hmmh.click)
- **Tài khoản quản trị viên (Admin)**:
  - **Email**: `admin1@drinks.hmmh.click`
  - **Mật khẩu**: `Admin@3618`
  - **Vai trò**: `admin`
  - **Truy cập**: `/admin/users` (quản trị tài khoản), `/admin/menu` (thực đơn và ảnh món), `/admin/orders` (xử lý đơn), `/admin/reports` (báo cáo doanh thu).

## 1. Tạo tunnel và public hostname

1. Đảm bảo domain `hmmh.click` đang dùng nameserver của Cloudflare.
2. Vào Cloudflare dashboard -> **Networking -> Tunnels** -> tạo tunnel mới, ví dụ `drink-app`.
3. Chọn cách cài đặt Docker và sao chép token trong lệnh mẫu. Chỉ lấy chuỗi token sau `--token`; không commit token vào Git.
4. Trong tunnel, thêm **Published application**:
   - Subdomain: `drinks`
   - Domain: `hmmh.click`
   - Type: `HTTP`
   - Service URL: `http://site:80`

Cloudflare sẽ tạo CNAME cho `drinks.hmmh.click`. Nếu dashboard báo bản ghi đã tồn tại, xóa bản ghi A/AAAA/CNAME cũ của chính `drinks` rồi thêm Published application lại. Không xóa các bản ghi khác của domain.

## 2. Cấu hình máy chạy Docker

Trong file `.env` ở root project, thêm/cập nhật:

```dotenv
APP_URL=https://drinks.hmmh.click
CLOUDFLARE_TUNNEL_TOKEN=<token-cua-tunnel>
```

File `.env` đã nằm trong `.gitignore`. Không đặt token thật vào `.env.example`, Dockerfile, ảnh chụp màn hình hay commit.

## 3. Build và khởi động

```bash
make deploy
```

Nếu máy không có `make`:

```bash
docker compose up -d --build --remove-orphans
```

Stack production không publish cổng local. Xem trạng thái sau khi build:

```bash
docker compose ps
docker compose logs -f site cloudflared
```

Khi tunnel báo `Healthy`, truy cập `https://drinks.hmmh.click`.

## 4. Xử lý lỗi nhanh

- Cloudflare `1016`/route không vào được app: kiểm tra Service URL phải là `http://site:80`, không phải URL public và không phải `localhost`.
- Tunnel restart liên tục: token trong `.env` đang thiếu/sai/đã bị rotate.
- Cloudflare không cho thêm hostname: `drinks` đang có A/AAAA/CNAME cũ.
- Trang hiện nhưng API lỗi: chạy `docker compose logs app webserver site` và kiểm tra `APP_URL=https://drinks.hmmh.click`.
- Chỉ để public service `site`; không tạo Published application cho MySQL, Redis hoặc backend nội bộ.
- Sau khi sửa code, chạy lại `make deploy`; production không bind mount source.
