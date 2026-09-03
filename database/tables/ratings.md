# Bảng `ratings`

## Mục đích

Lưu đánh giá/feedback của user cho món đã uống — phục vụ UC-07. Dữ liệu này quay lại làm input implicit cho `user_preferences` (mục 1.5 SPEC, FR1).

## Cấu trúc bảng

| Cột | Kiểu | Null | Default | Ràng buộc | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | Khoá chính |
| `user_id` | BIGINT UNSIGNED | NO | | FK → `users.id`, INDEX | Người đánh giá |
| `drink_id` | BIGINT UNSIGNED | NO | | FK → `drinks.id`, INDEX | Món được đánh giá |
| `order_id` | BIGINT UNSIGNED | NO | | FK → `orders.id`, INDEX | Đơn hàng liên quan (xác nhận user đã thực sự mua món này) |
| `rating` | TINYINT UNSIGNED | NO | | CHECK (1 ≤ `rating` ≤ 5) | Số sao đánh giá |
| `comment` | TEXT | YES | NULL | | Bình luận |
| `created_at` | TIMESTAMP | YES | NULL | | |
| `updated_at` | TIMESTAMP | YES | NULL | | Cho phép user sửa lại đánh giá |

## Index & Constraints

- PRIMARY KEY: `id`
- UNIQUE: `(user_id, order_id, drink_id)` — 1 user chỉ đánh giá **1 lần** cho 1 món trong 1 đơn hàng cụ thể; muốn sửa nội dung đánh giá thì `UPDATE` dòng hiện có, không tạo dòng mới.
- INDEX: `drink_id` (dùng để tính rating trung bình/hiển thị review của 1 món), `user_id`.
- FOREIGN KEY: `user_id → users.id`, `drink_id → drinks.id`, `order_id → orders.id` — đều **ON DELETE RESTRICT** (bảo toàn dữ liệu đánh giá lịch sử).

## Quan hệ (Relationships)

| Quan hệ | Bảng liên quan | Loại |
|---|---|---|
| `belongsTo` | `users` | N – 1 |
| `belongsTo` | `drinks` | N – 1 |
| `belongsTo` | `orders` | N – 1 |

## Business rules liên quan

- Chỉ được đánh giá món **đã thực sự đặt và hoàn thành** — nên validate ở tầng ứng dụng: tồn tại 1 `order_items` với `order_id` + `drink_id` tương ứng, và `orders.status = 'done'`.
- Mỗi lần có rating mới hoặc rating được sửa: dispatch `UpdateUserProfileEmbeddingJob` để cập nhật lại `user_preferences.profile_text` + `profile_embedding` (vòng lặp feedback — mục 2.5 SPEC).
- Rating trung bình của 1 món (dùng để hiển thị ở menu, hoặc làm tín hiệu phụ trợ trong pre-filter/re-rank) nên được **tính động** (`AVG(rating) GROUP BY drink_id`) hoặc cache ở Redis, không lưu cột đếm sẵn trong `drinks` để tránh phải đồng bộ 2 nguồn dữ liệu — có thể bổ sung sau nếu cần tối ưu hiệu năng.
- UC-10 trả về `average_rating` cho từng món bán chạy. Giá trị này là trung bình các dòng `ratings` có `created_at` nằm trong khoảng `from`/`to` đã chọn; món chưa có đánh giá trong khoảng đó trả về `null`.

## Ghi chú thiết kế

- **Ràng buộc UNIQUE `(user_id, order_id, drink_id)` là bổ sung so với SPEC gốc**, được thêm sau khi xác nhận với người yêu cầu để tránh spam đánh giá trùng lặp cho cùng 1 món trong cùng 1 đơn hàng. Phương án thay thế đã cân nhắc nhưng không chọn: không ràng buộc, cho phép lưu toàn bộ lịch sử đánh giá kể cả trùng lặp.
- **`updated_at` là bổ sung theo convention Laravel** (SPEC chỉ liệt kê `created_at`) — cần thiết để hỗ trợ tính năng "sửa đánh giá" mà không tạo dòng mới (nhất quán với ràng buộc UNIQUE ở trên).
- Model tương ứng: `app/Models/Rating.php` (hiện là stub trống, cần bổ sung `$fillable`).
