# Triển khai Smart Drink từ GitHub

Hướng dẫn này dành cho lần triển khai mới bằng Docker Compose. Cấu hình trong repository chạy production qua **Cloudflare Tunnel**, với Vue được build thành static assets và Laravel chạy PHP-FPM. Không cần cài PHP, Composer hoặc Node trên máy host.

```text
Internet → Cloudflare → cloudflared → site (Vue/Nginx)
                                     └─ /api → webserver → app (Laravel)
                                                          ├─ MySQL
                                                          └─ Redis ← queue worker
```

Stack không publish cổng host; truy cập bằng tên miền đã cấu hình, không phải `localhost`. `drinks.hmmh.click` trong file mẫu là tên miền của bản triển khai hiện có: khi triển khai riêng, thay bằng tên miền bạn quản lý.

## 1. Chuẩn bị

- Git, Docker Engine hoặc Docker Desktop đang chạy với Linux containers, Docker Compose V2.
- Máy có kết nối Internet để tải image, cài dependency trong quá trình build và kết nối Cloudflare.
- Tên miền đã được quản lý DNS trên Cloudflare và quyền tạo Cloudflare Tunnel.
- API key OpenAI/Weather là tùy chọn. Khi chưa có key hoặc provider lỗi, ứng dụng vẫn đặt hàng được và gợi ý dùng fallback.

Kiểm tra:

```bash
git --version
docker version
docker compose version
```

Các lệnh bên dưới chạy tại root repository, trừ khi có ghi chú khác. `make` là tùy chọn; lệnh Docker Compose dùng được cả trên Linux/macOS và Windows PowerShell.

## 2. Clone repository và tạo file môi trường

```bash
git clone https://github.com/alisa12405/drink_app.git
cd drink_app
```

Linux/macOS:

```bash
cp .env.example .env
cp backend/.env.example backend/.env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
Copy-Item backend/.env.example backend/.env
```

Chỉ sao chép ở lần cài mới; khi cập nhật, giữ nguyên file môi trường đang dùng.

| File | Cần cấu hình |
|---|---|
| `.env` | `APP_URL`, `CLOUDFLARE_TUNNEL_TOKEN`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`; API key nếu sử dụng |
| `backend/.env` | `APP_KEY` cố định; các cấu hình Laravel riêng nếu cần |

Trong `.env`, đổi `APP_URL` thành URL HTTPS của bạn, ví dụ `https://drinks.example.com`. Đặt hai mật khẩu MySQL mạnh, khác nhau, thay các giá trị `change_me_*` trong file mẫu. Giữ `DB_HOST=db`, `DB_PORT=3306`, `REDIS_HOST=redis` và `REDIS_PORT=6379` vì đây là địa chỉ trong mạng Docker.

Compose truyền cấu hình DB, Redis và API key từ root `.env` cho cả `app` và `queue`, đồng thời ghi đè `APP_ENV=production`, `APP_DEBUG=false`. Vì vậy không cần sửa cấu hình SQLite/local của `backend/.env.example` để chạy Compose. Nếu cùng một biến có mặt ở cả hai file, giá trị trong phần `environment` của Compose được ưu tiên.

Không commit `.env`, `backend/.env`, token hoặc API key. Các file này đã được Git bỏ qua. Không đưa API key vào biến `VITE_*` vì frontend được gửi tới trình duyệt.

## 3. Tạo Cloudflare Tunnel

Theo [hướng dẫn Cloudflare Tunnel](https://developers.cloudflare.com/tunnel/get-started/), tạo một tunnel được quản lý trên dashboard, chọn hướng dẫn cài bằng Docker và lấy **chuỗi token**, không lấy toàn bộ lệnh mẫu. Điền token vào `CLOUDFLARE_TUNNEL_TOKEN` trong root `.env`.

Thêm published application route cho tên miền của bạn:

| Thuộc tính | Giá trị |
|---|---|
| Public hostname | Ví dụ `drinks.example.com`, khớp hostname trong `APP_URL` |
| Service type | `HTTP` |
| Service URL | `http://site:80` |

`site` là tên service Docker mà `cloudflared` truy cập nội bộ. Chỉ định route tới service này; không dùng `localhost` hoặc URL HTTPS công khai làm origin. Repository đã có service `cloudflared`, không cần chạy thêm lệnh `docker run` của dashboard.

## 4. Tạo APP_KEY trước lần khởi động đầu tiên

Sau khi đã điền token và mật khẩu, build image backend:

```bash
docker compose build app
```

Tạo key một lần mà không chạy migration hay khởi động database:

```bash
docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show
```

Lệnh in ra key dạng `base64:...`. Sao chép giá trị đó vào dòng `APP_KEY=` trong **`backend/.env`**. Đây là secret của bản triển khai; không đưa vào Git hoặc tài liệu. Lệnh `--show` không tự cập nhật file trên host.

`--entrypoint` ghi đè entrypoint mặc định theo [Docker Compose run](https://docs.docker.com/reference/cli/docker/compose/run/), nên bước tạo key chạy được khi `APP_KEY` còn trống. Entry point production sẽ từ chối khởi động nếu thiếu key.

Giữ nguyên key khi restart, cập nhật hoặc khôi phục dữ liệu. Source không bind mount vào container; `key:generate` thông thường trong container không ghi lại key vào file trên host.

## 5. Build và khởi động hệ thống

```bash
docker compose config --quiet
docker compose up -d --build --remove-orphans
docker compose ps
```

Nếu có `make`, `make deploy` tương đương lệnh `up` trên. Lần build đầu có thể mất nhiều phút. Image frontend tự chạy lint và production build; image backend tự cài Composer dependencies.

Khi `app` khởi động, entrypoint chạy `migrate --force` và tạo cache Laravel. Database mới có schema nhưng **chưa có menu hoặc tài khoản admin**. Production không tự seed và không tự import `database/dump.sql`.

Stack gồm 7 service: `app`, `queue`, `webserver`, `site`, `cloudflared`, `db`, `redis`. Các service có healthcheck cần đạt `healthy`; `app` và `cloudflared` cần ở trạng thái chạy, không restart liên tục. Xem log nếu một service chưa sẵn sàng:

```bash
docker compose logs --tail=100 app webserver site queue cloudflared
```

## 6. Khởi tạo menu và tài khoản quản trị

Nạp danh mục 50 món từ CSV có sẵn trong image:

```bash
docker compose exec app php artisan db:seed --class=DrinkSeeder --force
```

Seeder đồng bộ theo tên món, có thể cập nhật và khôi phục món trong danh mục; không xóa món tùy chỉnh ngoài danh mục. Chỉ chạy lại khi muốn đồng bộ dữ liệu mẫu.

Để tạo admin production với mật khẩu riêng:

1. Mở `https://<ten-mien-cua-ban>/register` và đăng ký tài khoản quản trị bằng email, mật khẩu bạn chọn. Đăng ký mặc định tạo role `customer`.
2. Mở Tinker:

   ```bash
   docker compose exec app php artisan tinker
   ```

3. Trong Tinker, thay email ví dụ bằng email vừa đăng ký rồi chạy:

   ```php
   $user = App\Models\User::where('email', 'admin@example.com')->firstOrFail();
   $user->role = App\Enums\UserRole::Admin;
   $user->save();
   exit
   ```

4. Đăng xuất, đăng nhập lại và mở `/admin/menu`.

Nếu chỉ dựng môi trường demo với database mới, có thể dùng `docker compose exec app php artisan db:seed --force` **thay cho** các bước khởi tạo trên. `DatabaseSeeder` tạo `admin@example.com` và `test@example.com`, đều có mật khẩu mẫu `password`, rồi nạp menu. Không dùng tài khoản mẫu trên bản public production; chạy lại toàn bộ seeder có thể lỗi email trùng.

## 7. Bật OpenAI và thời tiết (tùy chọn)

Điền `OPENAI_API_KEY`, `WEATHER_API_KEY` và `WEATHER_API_PROVIDER` trong root `.env`. Provider thời tiết hỗ trợ `openweather` hoặc `weatherapi`. Giữ các model, timeout và timezone trong `.env.example` nếu chưa có nhu cầu thay đổi.

Sau khi sửa môi trường, tạo lại container để nhận giá trị mới:

```bash
docker compose up -d --build --remove-orphans
docker compose exec app php scripts/check_api_keys.php
```

Khi script xác nhận credential hợp lệ, tạo embedding cho menu:

```bash
docker compose exec app php artisan recommendations:backfill-embeddings --drinks
docker compose logs --tail=100 queue
docker compose exec app php artisan queue:failed
```

Đợi worker xử lý job; kết quả `Queued ...` chỉ chứng minh đã dispatch. Kiểm tra số vector đã ghi bằng Tinker:

```bash
docker compose exec app php artisan tinker
```

```php
App\Models\Drink::whereNotNull('description_embedding')->count();
exit
```

Với danh mục mới nạp đủ 50 món, kết quả kỳ vọng là 50. Chỉ backfill menu bằng `--drinks`; việc backfill hàng loạt hồ sơ người dùng cần tuân theo quyết định riêng về dữ liệu cá nhân. Chi tiết tại [hướng dẫn gợi ý cá nhân hóa](doc/HUONG_DAN_TRIEN_KHAI_GOI_Y_CA_NHAN.md).

## 8. Kiểm tra sau triển khai

```bash
docker compose exec app php artisan migrate:status
docker compose exec webserver nginx -t
docker compose exec site nginx -t
docker compose exec site wget -qO- http://webserver/api/drinks
```

Mở tên miền trong trình duyệt và kiểm tra:

- `/`: menu hiển thị; `/api/drinks` trả JSON danh mục.
- Đăng ký/đăng nhập, thêm món vào giỏ, đặt đơn và xem lịch sử.
- Admin xem đơn mới, chuyển trạng thái và nhận thông báo.
- Khi có API key/vector, customer cập nhật sở thích rồi bấm nhận gợi ý; thiếu dịch vụ ngoài vẫn có fallback.

Kiểm tra tự động backend bằng `make test`, hoặc chạy tương đương:

```bash
docker compose exec app php artisan config:clear
docker compose exec app ./vendor/bin/phpunit
docker compose exec app php artisan config:cache
```

Test dùng cấu hình riêng trong `backend/phpunit.xml`. Nếu test thất bại, vẫn chạy lại lệnh `config:cache` cuối để phục hồi cache production.

## 9. Cập nhật, dừng và bảo toàn dữ liệu

Trước khi cập nhật, sao lưu MySQL và volume lưu ảnh, đồng thời lưu an toàn hai file môi trường. Named volume thực tế có thể được Compose thêm tiền tố project; xem bằng `docker volume ls` và `docker volume inspect`.

| Volume khai báo | Dữ liệu |
|---|---|
| `drink-app-db-data` | MySQL: người dùng, menu, đơn, rating, notification, vector |
| `drink-app-storage` | Laravel storage và ảnh món upload |
| `drink-app-redis-data` | Redis cache và queue |

Cập nhật code khi working tree sạch, giữ nguyên `.env`, `backend/.env` và `APP_KEY`:

```bash
git pull --ff-only
docker compose up -d --build --remove-orphans
docker compose ps
```

Image phải được build lại để nhận source mới. Restart đơn thuần không cập nhật code hoặc biến môi trường mới.

```bash
docker compose logs -f app queue
docker compose down
docker compose up -d
```

`down` giữ named volume. **Không dùng `down -v` hoặc `migrate:fresh` trên hệ thống có dữ liệu cần giữ**: các lệnh này xóa dữ liệu. Đổi mật khẩu trong `.env` không tự đổi mật khẩu của MySQL đã khởi tạo trong volume; cần cập nhật tài khoản MySQL tương ứng trước khi tạo lại app/queue.

## 10. Xử lý lỗi thường gặp

| Hiện tượng | Kiểm tra/cách xử lý |
|---|---|
| Compose báo thiếu tunnel token hoặc env file | Tạo cả hai file môi trường, điền token và chạy lại `config --quiet` |
| `APP_KEY is required` | Điền key trong `backend/.env`, rồi tạo lại container |
| `db` không healthy / `Access denied` | Xem log `db`, kiểm tra user/password và việc volume đã được khởi tạo trước đó |
| Tunnel restart hoặc domain không vào được | Kiểm tra token, trạng thái tunnel trên Cloudflare và route `http://site:80`; xem log `cloudflared site` |
| Giao diện hiện nhưng API lỗi | Xem log `app webserver site`, kiểm tra migration và `APP_URL` |
| Menu trống trên bản cài mới | Chạy `DrinkSeeder`; migration không tự seed |
| Gợi ý dùng fallback hoặc không có vector | Kiểm tra API key, log `queue`, `queue:failed` và số embedding đã ghi |
| Sửa code nhưng UI không đổi | Build lại stack bằng `up -d --build`; frontend là static build trong image |

Tài liệu thiết kế, schema và hướng dẫn chuyên sâu được tập hợp tại [doc/README.md](doc/README.md).
