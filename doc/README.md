# Tài liệu Smart Drink

Hướng dẫn triển khai từ bản clone GitHub: [DEPLOY.md](../DEPLOY.md).

| Tài liệu | Nội dung |
|---|---|
| [SPEC](SPEC_smart-drink-recommendation-app.md) | Phạm vi, use case và quy tắc nghiệp vụ |
| [UI design](UI_DESIGN_TEMPLATE.md) | Design system và trạng thái giao diện |
| [Docker](README-DOCKER.md) | Kiến trúc production và lệnh vận hành |
| [Cloudflare](CLOUDFLARE_DEPLOYMENT.md) | Ghi chú triển khai tên miền hiện có |
| [Gợi ý cá nhân hóa](HUONG_DAN_TRIEN_KHAI_GOI_Y_CA_NHAN.md) | Recommendation, embedding, weather và kiểm chứng |
| [Database](database/README.md) | Thiết kế dữ liệu và liên kết chi tiết từng bảng |
| [Schema](database/DATABASE_SCHEMA.md) | Cột, quan hệ và dữ liệu mẫu |
| [Frontend](frontend/README.md) | Build và kiểm tra Vue |
| [Backend](backend/README.md) | README Laravel đi kèm scaffold |

Các đường dẫn source/lệnh trong tài liệu được hiểu tương đối với root repository, trừ khi có ghi chú khác. Các ghi nhận triển khai theo ngày là bằng chứng lịch sử; làm theo `DEPLOY.md` cho bản cài mới và đối chiếu code/cấu hình hiện tại.

`AGENTS.md`, `backend/AGENTS.md`, `backend/CLAUDE.md` và các skill của agent giữ tại vị trí ban đầu để công cụ tự nhận diện. SQL dump và bảng tính menu giữ trong `database/` vì là dữ liệu đầu vào, không phải hướng dẫn.
