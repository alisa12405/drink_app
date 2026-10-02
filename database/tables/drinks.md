# Bảng `drinks`

## Mục đích

Danh mục menu đồ uống của quán — phục vụ UC-03 (xem menu), UC-08 (Admin CRUD), và là nguồn dữ liệu ứng viên chính cho Recommendation module.

## Cấu trúc bảng

| Cột | Kiểu | Null | Default | Ràng buộc | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | Khoá chính |
| `name` | VARCHAR(255) | NO | | | Tên món |
| `description` | TEXT | YES | NULL | | Mô tả món (hiển thị cho khách) |
| `ingredients` | TEXT | YES | NULL | | Nguyên liệu, dạng mô tả text |
| `category` | VARCHAR(100) | NO | | INDEX | Danh mục, VD "trà sữa", "cà phê", "nước ép", "trà trái cây" |
| `price` | DECIMAL(10,2) | NO | | | Đơn giá (VNĐ) |
| `calories` | INT UNSIGNED | YES | NULL | | Số calo ước tính |
| `temperature_type` | ENUM('hot','cold','both') | NO | `'both'` | INDEX | Loại nhiệt độ phục vụ được |
| `tags` | JSON | YES | NULL | | Mảng string, VD `["best_seller","ít_ngọt"]` |
| `image_url` | VARCHAR(255) | YES | NULL | | Đường dẫn/URL ảnh món |
| `image_path` | VARCHAR(255) | YES | NULL | | Đường dẫn file ảnh do Admin tải lên trên disk `public` |
| `is_available` | BOOLEAN | NO | `true` | INDEX | Còn bán hay đã ẩn khỏi menu/gợi ý |
| `description_embedding` | JSON | YES | NULL | | Vector 1536 chiều từ `name + ingredients + tags` |
| `created_at` | TIMESTAMP | YES | NULL | | |
| `updated_at` | TIMESTAMP | YES | NULL | | |
| `deleted_at` | TIMESTAMP | YES | NULL | INDEX | Soft delete |

## Index & Constraints

- PRIMARY KEY: `id`
- INDEX: `category`, `temperature_type`, `is_available`, `deleted_at` — đều là các cột dùng để filter/pre-filter thường xuyên (GET /api/drinks?category=, pre-filter trong luồng gợi ý mục 2.4 bước 3).
- Sử dụng `SoftDeletes` trait của Laravel (cột `deleted_at`).

## Quan hệ (Relationships)

| Quan hệ | Bảng liên quan | Loại |
|---|---|---|
| `hasMany` | `order_items` | 1 – N |
| `hasMany` | `ratings` | 1 – N |

## Business rules liên quan

- Gợi ý (Recommendation) chỉ được tính trên món có `is_available = true` (mục 1.5 SPEC) — mọi query pre-filter trong `RecommendationService` phải có điều kiện này.
- Pre-filter theo ngữ cảnh (mục 2.4 bước 3): VD nhiệt độ ngoài trời > 30°C → chỉ lấy `temperature_type IN ('cold', 'both')`.
- Mỗi lần Admin **tạo/sửa** món (FR7): dispatch `UpdateDrinkEmbeddingJob` (queued) → gọi OpenAI Embeddings trên đoạn text ghép từ `name + ingredients + tags` → cập nhật `description_embedding`. Đồng thời **invalidate cache Redis** của embedding món đó (mục 3 SPEC: "Dùng Redis cache cho... embedding của drinks, invalidate khi món được sửa").
- Khi Admin **xoá** món (UC-08): thực hiện **soft delete** (`$drink->delete()` với `SoftDeletes`), không xoá cứng — để `order_items`/`ratings` cũ vẫn còn tham chiếu hợp lệ tới `drink_id`. Món bị soft-delete mặc định không xuất hiện trong mọi query (Laravel tự động thêm `WHERE deleted_at IS NULL`), kể cả khi `is_available = true`.
- Khi Admin tải ảnh mới, hệ thống lưu file JPG/PNG/WebP tối đa 5 MB vào `storage/app/public/drinks`, cập nhật `image_path` và xóa file upload cũ của chính món đó. `DrinkResource` ưu tiên URL ảnh upload hơn `image_url` có sẵn.
- `is_available = false` là cách "ẩn tạm thời" món khỏi menu/gợi ý **mà không xoá** (VD: hết nguyên liệu tạm thời) — độc lập với soft delete. Một món có thể `is_available = false` nhưng vẫn `deleted_at IS NULL` (chưa bị xoá, chỉ đang tạm ẩn).

## Ghi chú thiết kế

- **Soft delete (`deleted_at`) là bổ sung so với SPEC gốc**, được thêm sau khi xác nhận với người yêu cầu để tránh vỡ khoá ngoại/dữ liệu lịch sử khi Admin xoá món đã từng được đặt hoặc đánh giá. Phương án thay thế đã cân nhắc: (a) chỉ dùng `is_available=false`, không có thao tác xoá thật; (b) xoá cứng và chấp nhận rủi ro orphan reference — cả hai đều không được chọn.
- **`category` giữ nguyên là cột string đơn giản** theo đúng SPEC (không tách bảng `categories` riêng), giữ đúng phạm vi SPEC yêu cầu ("Không tự đổi công nghệ, đặt tên bảng/API nếu không được yêu cầu").
- `ingredients` dùng `TEXT` tự do (không phải `JSON` list có cấu trúc) vì SPEC không yêu cầu structured data cho nguyên liệu, chỉ cần đủ nội dung để ghép vào `description_embedding`. Nếu sau này cần tính năng "lọc theo nguyên liệu" hoặc "cảnh báo dị ứng tự động" (đối chiếu với `user_preferences.allergy_notes`), nên cân nhắc đổi sang `JSON` mảng nguyên liệu chuẩn hoá.
- `description_embedding` dùng kiểu `JSON` theo quyết định chung (xem `README.md` mục 5, điểm 4).
- Model tương ứng: `app/Models/Drink.php` đã dùng `SoftDeletes`, khai báo `$fillable` và cast các trường JSON/boolean.
