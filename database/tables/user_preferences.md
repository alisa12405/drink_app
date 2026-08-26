# Bảng `user_preferences`

## Mục đích

Lưu hồ sơ sở thích cá nhân hoá của từng user — nền tảng cho Recommendation module. Kết hợp dữ liệu **explicit** (user tự khai báo qua form — UC-02) và **implicit** (tổng hợp từ lịch sử đặt hàng + đánh giá — FR1).

## Cấu trúc bảng

| Cột | Kiểu | Null | Default | Ràng buộc | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | Khoá chính |
| `user_id` | BIGINT UNSIGNED | NO | | FK → `users.id`, UNIQUE | Chủ sở hữu hồ sơ |
| `taste_tags` | JSON | YES | NULL | | Mảng string, VD `["ngọt","có_caffeine"]` |
| `sugar_level_default` | ENUM('0','30','50','70','100') | NO | `'100'` | | % đường mặc định khi đặt hàng |
| `ice_level_default` | ENUM('no_ice','less_ice','normal_ice','extra_ice') | NO | `'normal_ice'` | | Mức đá mặc định |
| `allergy_notes` | TEXT | YES | NULL | | Ghi chú dị ứng (free text) |
| `profile_text` | TEXT | YES | NULL | | Đoạn text tổng hợp dùng làm input cho Embeddings API |
| `profile_embedding` | JSON | YES | NULL | | Vector 1536 chiều (`text-embedding-3-small`) |
| `created_at` | TIMESTAMP | YES | NULL | | |
| `updated_at` | TIMESTAMP | YES | NULL | | Cập nhật mỗi lần preferences/rating thay đổi |

## Index & Constraints

- PRIMARY KEY: `id`
- UNIQUE: `user_id` — thực thi quy tắc nghiệp vụ "một user chỉ có một hồ sơ sở thích, được **cập nhật** (không tạo mới) sau mỗi đơn hàng/đánh giá" (mục 1.5 SPEC).
- FOREIGN KEY: `user_id → users.id` **ON DELETE CASCADE** (hồ sơ sở thích không có ý nghĩa tồn tại độc lập nếu user bị xoá).

## Quan hệ (Relationships)

| Quan hệ | Bảng liên quan | Loại |
|---|---|---|
| `belongsTo` | `users` | N – 1 (thực chất 1-1 do UNIQUE) |

## Business rules liên quan

- **Không bao giờ `INSERT` thêm dòng thứ 2** cho cùng 1 `user_id` — luôn `updateOrCreate(['user_id' => $id], [...])` ở tầng Laravel.
- Mỗi khi `taste_tags`, `sugar_level_default`, `ice_level_default`, `allergy_notes` thay đổi (UC-02), hoặc có `rating` mới (UC-07): ghép lại `profile_text` (sở thích khai báo + lịch sử mua gần nhất + đánh giá gần nhất) → dispatch `UpdateUserProfileEmbeddingJob` (queued, không block request) → job gọi OpenAI Embeddings, cập nhật `profile_embedding`.
- `profile_embedding` **không được tính lại** nếu `profile_text` không đổi (FR6) — nên so sánh hash/nội dung `profile_text` trước khi gọi API tốn phí.
- Nếu `profile_embedding` là `NULL` (user mới, chưa từng tương tác) → Recommendation module fallback theo mục 1.5 SPEC: dùng gợi ý theo lịch sử mua + thời gian hệ thống, bỏ qua bước cosine similarity.

## Ghi chú thiết kế

- **`created_at` là bổ sung theo convention Laravel** (`$table->timestamps()`); SPEC mục 2.2 chỉ liệt kê `updated_at`. Giữ cả hai để nhất quán với các bảng khác và để biết chính xác thời điểm hồ sơ được tạo lần đầu — không ảnh hưởng đến logic nghiệp vụ.
- Giá trị `ENUM` cho `sugar_level_default`/`ice_level_default` là **quy ước đề xuất** (không có trong SPEC), lấy theo chuẩn phổ biến của quán trà sữa VN (0/30/50/70/100% đường; không đá/ít đá/đá bình thường/nhiều đá). Có thể đổi tự do khi hiện thực hoá — không ảnh hưởng cấu trúc bảng, chỉ là danh sách giá trị enum.
- `profile_embedding` dùng kiểu `JSON` (không phải `LONGTEXT`) theo quyết định chung áp dụng cho mọi cột vector trong hệ thống (xem `README.md` mục 5, điểm 4).
- Model tương ứng: `app/Models/UserPreference.php` (hiện là stub trống, cần bổ sung `$fillable`, cast `taste_tags`/`profile_embedding` sang `array`).
