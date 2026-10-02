# Smart Drink frontend

Ứng dụng Vue 3 được build thành static assets trong image `site`. Trình duyệt gọi API bằng đường dẫn tương đối `/api`, vì vậy giao diện và Laravel dùng chung origin `https://drinks.hmmh.click` và không cần cấu hình CORS hoặc URL localhost.

Các lệnh kiểm tra cục bộ (khi đã cài Node 22):

```bash
cd frontend
npm ci --legacy-peer-deps
npm run lint
npm run build
```

Trong deployment chính, `docker/frontend/Dockerfile` tự chạy lint và production build. Không có Vite dev server trong `docker-compose.yml`.
