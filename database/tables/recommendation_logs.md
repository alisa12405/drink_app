# Bảng `recommendation_logs`

## Mục đích

Ghi log mỗi lượt gợi ý được sinh ra: ngữ cảnh lúc đó, danh sách ứng viên, danh sách sau re-rank, và giải thích của LLM — phục vụ FR8 ("ghi log mỗi lượt gợi ý... để phục vụ đánh giá chất lượng gợi ý sau này") và UC-10 (Admin xem báo cáo hiệu quả gợi ý).

## Cấu trúc bảng

| Cột | Kiểu | Null | Default | Ràng buộc | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | Khoá chính |
| `user_id` | BIGINT UNSIGNED | NO | | FK → `users.id`, INDEX | User nhận gợi ý |
| `context_snapshot` | JSON | YES | NULL | | Ngữ cảnh tại thời điểm gợi ý: `{hour, weather, temperature}` |
| `candidate_drink_ids` | JSON | YES | NULL | | Mảng ID món sau pre-filter + cosine similarity (top 10 — bước 5 mục 2.4) |
| `final_ranked_ids` | JSON | YES | NULL | | Mảng ID món sau khi LLM re-rank (top 5 — bước 6 mục 2.4) |
| `llm_explanation` | TEXT | YES | NULL | | Giải thích do LLM sinh ra |
| `created_at` | TIMESTAMP | YES | NULL | INDEX | Thời điểm sinh gợi ý |

> Bảng này **không có cột `updated_at`** — đây là bảng log mang tính append-only/immutable, một bản ghi chỉ được tạo, không bao giờ sửa (đúng theo mô tả SPEC mục 2.2, chỉ liệt kê `created_at`).

## Index & Constraints

- PRIMARY KEY: `id`
- INDEX: `user_id` (xem lịch sử gợi ý của 1 user), `created_at` (thống kê/báo cáo theo khoảng thời gian — UC-10).
- FOREIGN KEY: `user_id → users.id` **ON DELETE RESTRICT** (giữ nguyên dữ liệu log phục vụ phân tích/báo cáo, ngay cả khi cân nhắc vô hiệu hoá tài khoản).

## Quan hệ (Relationships)

| Quan hệ | Bảng liên quan | Loại |
|---|---|---|
| `belongsTo` | `users` | N – 1 |

## Business rules liên quan

- Mỗi lần gọi `GET /api/recommendations` thành công (có trả kết quả, kể cả khi rơi vào fallback do không xác định được ngữ cảnh — mục 1.5 SPEC) → ghi 1 dòng log mới. Nếu request lỗi hoàn toàn (không sinh được gợi ý nào) thì không bắt buộc phải ghi log.
- `candidate_drink_ids` và `final_ranked_ids` lưu **thứ tự đã sắp xếp** (index 0 = ưu tiên cao nhất) — không phải tập hợp không thứ tự, để phục vụ phân tích "vị trí gợi ý nào thường được user chọn thực sự" (đối chiếu chéo với `order_items` qua `user_id` + thời gian gần nhất sau `created_at` của log — dùng để đo lường hiệu quả gợi ý ở UC-10).
- Bảng này **chỉ dùng để đọc/phân tích**, không có ràng buộc nghiệp vụ nào phụ thuộc ngược từ nó vào các bảng khác (không có FK nào trỏ ngược tới `recommendation_logs`).

## Ghi chú thiết kế

- `llm_explanation` khai báo kiểu `TEXT` đúng theo SPEC. SPEC mục 2.4 bước 6 mô tả LLM trả về "giải thích ngắn cho **từng món**" (nhiều giải thích), trong khi mục 2.2 mô tả cột `llm_explanation` là 1 cột `TEXT` duy nhất — có sự chưa khớp nhỏ giữa 2 mục. Cách xử lý đề xuất (không đổi cấu trúc cột, chỉ là quy ước lưu trữ): lưu `llm_explanation` dưới dạng **chuỗi JSON dạng text** `{"<drink_id>": "<giải thích>", ...}` để giữ được giải thích riêng cho từng món mà vẫn đúng kiểu cột `TEXT` như SPEC quy định; nếu chỉ cần 1 đoạn giải thích tổng hợp thì lưu văn bản thuần bình thường. Đây là quy ước ở tầng ứng dụng, không phải thay đổi schema.
- `candidate_drink_ids`/`final_ranked_ids`/`context_snapshot` dùng kiểu `JSON` theo quyết định chung (xem `README.md` mục 5, điểm 4).
- Model tương ứng: `app/Models/RecommendationLog.php` đã khai báo `$fillable`, cast JSON sang `array` và đặt `const UPDATED_AT = null` cho log append-only.
