# Tài liệu Smart Drink

> Ứng dụng đặt đồ uống thông minh có gợi ý cá nhân hóa theo sở thích và ngữ cảnh.
> Bản triển khai production đang chạy trên **Docker build** tại [https://drinks.hmmh.click](https://drinks.hmmh.click) với tài khoản quản trị `admin1@drinks.hmmh.click` / `Admin@3618`.

Hướng dẫn triển khai từ bản clone GitHub: [DEPLOY.md](../DEPLOY.md).

| Tài liệu | Nội dung |
|---|---|
| [SPEC](SPEC_smart-drink-recommendation-app.md) | Phạm vi, use case, quy tắc nghiệp vụ và danh sách API |
| [UI design](UI_DESIGN_TEMPLATE.md) | Design system, màu sắc, typography và trạng thái các màn hình Vue |
| [Docker](README-DOCKER.md) | Kiến trúc 7 container production, lệnh vận hành và tài khoản admin |
| [Cloudflare](CLOUDFLARE_DEPLOYMENT.md) | Cấu hình Cloudflare Tunnel cho tên miền `drinks.hmmh.click` |
| [Gợi ý cá nhân hóa](HUONG_DAN_TRIEN_KHAI_GOI_Y_CA_NHAN.md) | Recommendation engine (UC-04), embedding, weather và kiểm chứng |
| [Database](database/README.md) | Thiết kế dữ liệu, ERD và liên kết chi tiết 8 bảng nghiệp vụ |
| [Schema](database/DATABASE_SCHEMA.md) | Cột, kiểu dữ liệu, quan hệ và dữ liệu mẫu của các bảng |
| [Frontend](frontend/README.md) | Hướng dẫn build và kiểm tra lint/build Vue 3 |
| [Backend](backend/README.md) | README Laravel đi kèm scaffold |

Các đường dẫn source/lệnh trong tài liệu được hiểu tương đối với root repository, trừ khi có ghi chú khác. Các ghi nhận triển khai theo ngày là bằng chứng lịch sử; làm theo `DEPLOY.md` cho bản cài mới và đối chiếu code/cấu hình hiện tại.

`AGENTS.md`, `backend/AGENTS.md`, `backend/CLAUDE.md` và các skill của agent giữ tại vị trí ban đầu để công cụ tự nhận diện. SQL dump và bảng tính menu giữ trong `database/` vì là dữ liệu đầu vào, không phải hướng dẫn.
