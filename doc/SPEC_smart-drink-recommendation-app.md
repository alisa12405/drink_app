# SPEC: Ứng dụng đặt đồ uống thông minh (Smart Drink Ordering + Recommendation)

> File này được viết để một AI Agent trong IDE (Copilot/Cursor/Claude Code...) đọc và hiểu toàn bộ nghiệp vụ + thiết kế hệ thống, từ đó sinh code chính xác theo đúng kiến trúc đã chốt. Không tự đổi công nghệ, đặt tên bảng/API nếu không được yêu cầu.

## 0. Tổng quan dự án

- **Tên đề tài**: Xây dựng ứng dụng đặt đồ uống thông minh hỗ trợ gợi ý món dựa trên sở thích và ngữ cảnh người dùng.
- **Nhóm**: 4 sinh viên, phân vai Backend / AI-Recommendation / Frontend / DevOps-QA (có thể xoay ca theo sprint).
- **Loại hệ thống**: Web app đặt đồ uống (dạng quán trà sữa/cafe), có engine gợi ý cá nhân hoá theo sở thích và ngữ cảnh (thời gian, thời tiết, nhiệt độ, lịch sử mua).
- **Tech stack đã chốt**: Laravel 13 (PHP 8.3) backend, MySQL 8, Redis 7, Vue 3 frontend, OpenAI API (`text-embedding-3-small` + `gpt-4o-mini`), Docker + Docker Compose, Git/GitHub.

---

## 1. Phân tích nghiệp vụ

### 1.1. Tác nhân (Actors)

| Actor | Vai trò |
|---|---|
| Khách vãng lai (Guest) | Xem menu, xem thời gian/thời tiết và gợi ý chung, quản lý giỏ, đặt đồ uống bằng tên; phải đăng nhập nếu muốn top 5 cá nhân hóa, lịch sử hoặc đánh giá |
| Khách hàng (Customer) | Đăng ký/đăng nhập, khai báo sở thích, xem menu, nhận gợi ý, đặt đồ uống, đánh giá món |
| Quản trị viên (Admin) | Quản lý menu (thêm/sửa/xoá món), quản lý đơn hàng, xem báo cáo, quản lý user |
| Hệ thống gợi ý (Recommendation Engine) | Actor hệ thống — tự động sinh gợi ý dựa trên profile + ngữ cảnh, không do người dùng trực tiếp điều khiển |
| Dịch vụ ngoài (External Services) | OpenAI API, Weather API — cung cấp dữ liệu/đầu ra cho hệ thống |

### 1.2. Danh sách Use Case chính

1. UC-01 Đăng ký / đăng nhập
2. UC-02 Khai báo & cập nhật sở thích (vị, loại đồ uống ưa thích, mức đường/đá, dị ứng)
3. UC-03 Xem menu đồ uống (có lọc theo danh mục)
4. UC-04 Nhận gợi ý cá nhân hoá theo ngữ cảnh hiện tại
5. UC-05 Đặt đồ uống (thêm vào giỏ, chọn size/đường/đá, thanh toán giả lập)
6. UC-06 Xem lịch sử đơn hàng
7. UC-07 Đánh giá/feedback món đã uống (rating + comment) — dữ liệu này quay lại làm input cho hồ sơ sở thích
8. UC-08 (Admin) Quản lý menu — CRUD món uống, mô tả, nguyên liệu, giá, ảnh
9. UC-09 (Admin) Quản lý đơn hàng — xem, đổi trạng thái (pending/confirmed/done/cancelled)
10. UC-10 (Admin) Xem báo cáo thống kê món bán nhiều nhất, hiệu quả gợi ý

### 1.3. Yêu cầu chức năng (Functional Requirements)

- FR1: Hệ thống phải lưu và cập nhật hồ sơ sở thích của từng user (explicit từ form khai báo + implicit từ lịch sử đặt hàng/đánh giá).
- FR2: Hệ thống phải lấy được ngữ cảnh hiện tại của người dùng tại thời điểm request gợi ý: giờ trong ngày (server time), thời tiết + nhiệt độ (qua Weather API theo vị trí), và tuỳ chọn "dịp" do user chọn tay (ví dụ "đang học bài", "sau khi tập gym").
- FR3: Hệ thống phải lọc sơ bộ (pre-filter) danh sách món theo ngữ cảnh trước khi tính điểm tương đồng.
- FR4: Hệ thống phải tính độ tương đồng ngữ nghĩa giữa vector hồ sơ user và vector từng món bằng cosine similarity.
- FR5: Hệ thống phải gọi LLM để re-rank top ứng viên và sinh giải thích ngôn ngữ tự nhiên kèm theo mỗi gợi ý.
- FR6: Mọi vector embedding (menu, user profile) phải được cache/lưu trữ, không tính lại nếu dữ liệu nguồn không đổi.
- FR7: Admin có thể CRUD món uống; mỗi lần thêm/sửa món phải trigger tính lại embedding cho món đó.
- FR8: Hệ thống phải ghi log mỗi lượt gợi ý (context, danh sách được gợi ý, món user thực sự chọn) để phục vụ đánh giá chất lượng gợi ý sau này.
- FR11: Hệ thống phải lưu thông báo trong database; Admin nhận thông báo khi có đơn mới, Customer nhận thông báo khi đặt hàng và khi trạng thái đơn thay đổi.
- FR12: Mỗi user chỉ được gọi gợi ý cá nhân hóa tối đa 5 lần/phút; API trả `429` và thời gian chờ khi vượt giới hạn.
- FR13: Admin được tải ảnh JPG/PNG/WebP tối đa 5 MB cho món; file được lưu bền và ảnh cũ được dọn khi thay thế.
- FR9: Khách vãng lai phải xem được menu và tạo đơn bằng tên mà không cần tài khoản; `user_id` của đơn để trống, không cung cấp lịch sử/hủy/rating. Top 5 cá nhân hóa vẫn yêu cầu đăng nhập.
- FR10: Khi mở menu, hệ thống chỉ hiển thị thời gian, thời tiết (nếu có vị trí) và một gợi ý chung. Chỉ gọi API top 5 sau khi người dùng chủ động bấm; nếu chưa có preference thì hỏi người dùng muốn khai báo trước hay tiếp tục bằng fallback.

### 1.4. Yêu cầu phi chức năng (Non-Functional Requirements)

- Thời gian phản hồi API gợi ý nên dưới ~2-3 giây (chấp nhận gọi LLM bất đồng bộ có loading state ở frontend).
- Chi phí gọi OpenAI API phải được tối ưu: không gọi Chat Completion cho toàn bộ menu, chỉ gọi trên tập ứng viên đã lọc (xem mục 2.4).
- Hệ thống phải chạy được hoàn toàn trong Docker Compose trên máy dev của bất kỳ thành viên nào.
- Dữ liệu nhạy cảm (OpenAI API key, DB password) phải nằm trong `.env`, không hardcode, không commit lên Git.

### 1.5. Quy tắc nghiệp vụ (Business Rules)

- Một user chỉ có một hồ sơ sở thích (`user_preferences`), được cập nhật (không tạo mới) sau mỗi đơn hàng/đánh giá.
- Mỗi món trong một đơn chỉ được đánh giá một lần; đánh giá đã gửi bị khóa và không cho sửa.
- Gợi ý chỉ được tính trên các món còn `is_available = true`.
- Nếu không xác định được ngữ cảnh (ví dụ không cấp quyền vị trí), hệ thống fallback dùng gợi ý dựa trên lịch sử mua + thời gian hệ thống, bỏ qua yếu tố thời tiết.
- Đơn hàng khi đã ở trạng thái `done` không được sửa món, chỉ có thể huỷ trước khi `confirmed`.

---

## 2. Phân tích thiết kế hệ thống

### 2.1. Kiến trúc tổng thể

```
[Frontend Vue/React]
        |  REST API (JSON)
        v
[Laravel Backend]
    ├── Auth module (Sanctum/JWT)
    ├── Menu module (CRUD)
    ├── Order module
    ├── Preference module
    ├── Recommendation module  ──> gọi OpenAI Embeddings + gpt-4o-mini
    ├── Context module         ──> gọi Weather API
    └── Cache layer (Redis)
        |
        v
     [MySQL]
```

Nguyên tắc thiết kế: tách riêng **Recommendation module** khỏi Order/Menu module để dễ test và dễ thay đổi thuật toán gợi ý sau này mà không ảnh hưởng luồng đặt hàng.

### 2.2. Thiết kế cơ sở dữ liệu (bảng chính)

**users**
- id, name, email, password, created_at, updated_at

**user_preferences**
- id, user_id (FK), taste_tags (JSON — ví dụ ["ngọt","có_caffeine"]), sugar_level_default, ice_level_default, allergy_notes, profile_text (đoạn text tổng hợp dùng để embed), profile_embedding (JSON/LONGTEXT lưu vector), updated_at

**drinks**
- id, name, description, ingredients, category, price, calories, temperature_type (hot/cold/both), tags (JSON), image_url, is_available, description_embedding (JSON/LONGTEXT lưu vector), updated_at

**orders**
- id, user_id (FK, nullable cho guest), customer_name, status (pending/confirmed/done/cancelled), total_price, context_snapshot (JSON — lưu lại ngữ cảnh lúc đặt: giờ, nhiệt độ, thời tiết), created_at

**order_items**
- id, order_id (FK), drink_id (FK), quantity, sugar_level, ice_level, note

**ratings**
- id, user_id (FK), drink_id (FK), order_id (FK), rating (1-5), comment, created_at

**recommendation_logs**
- id, user_id (FK), context_snapshot (JSON), candidate_drink_ids (JSON), final_ranked_ids (JSON), llm_explanation (TEXT), created_at

> Vector được lưu dạng JSON/LONGTEXT trong MySQL (không dùng pgvector) vì quy mô đồ án nhỏ; cosine similarity tính trong PHP ở application layer, không cần vector DB riêng.

### 2.3. Thiết kế API chính (REST)

| Method | Endpoint | Chức năng |
|---|---|---|
| POST | /api/auth/register, /api/auth/login | Đăng ký/đăng nhập |
| GET | /api/drinks | Lấy menu (filter theo category) |
| POST/PUT | /api/admin/drinks | Admin CRUD món (trigger tính lại embedding) |
| GET/PUT | /api/preferences | Xem/cập nhật hồ sơ sở thích user |
| GET | /api/recommendations?lat=&lon= | Trả về danh sách gợi ý kèm giải thích, dựa trên ngữ cảnh hiện tại |
| POST | /api/orders | Tạo đơn hàng, lưu context_snapshot |
| GET | /api/orders/history | Lịch sử đơn hàng |
| POST | /api/ratings | Gửi đánh giá món một lần, trigger cập nhật lại profile_embedding của user |
| GET/PATCH | /api/notifications* | Danh sách, đọc một hoặc đọc tất cả thông báo của user hiện tại |
| POST | /api/admin/drinks/{id}/image | Admin tải ảnh món lên storage |

### 2.4. Luồng xử lý gợi ý (Recommendation Flow — sequence)

1. Frontend gọi `GET /api/recommendations` kèm vị trí (lat/lon) và thời gian client.
2. Backend gọi Weather API lấy nhiệt độ/thời tiết hiện tại → tạo `context_object` = {hour, weather, temperature}.
3. Backend **pre-filter** bảng `drinks` bằng SQL/điều kiện Laravel (ví dụ nhiệt độ > 30°C → `temperature_type IN ('cold','both')`) → thu được tập ứng viên (candidate set, ví dụ 15-20 món).
4. Backend lấy `profile_embedding` của user (đã cache sẵn, không gọi lại OpenAI nếu chưa có thay đổi).
5. Với mỗi món trong candidate set, backend tính `cosine_similarity(user_vector, drink_vector)` bằng PHP thuần → sắp xếp giảm dần, lấy top 10.
6. Backend gọi `gpt-4o-mini` (Structured Outputs) với input = {context_object, user_preferences_text, top10_drinks} → model trả JSON gồm danh sách đã re-rank (ví dụ top 5) + lời giải thích ngắn cho từng món.
7. Backend lưu log vào `recommendation_logs`, trả kết quả về frontend.
8. Frontend hiển thị danh sách gợi ý kèm giải thích tự nhiên.

### 2.5. Trigger tính embedding

- Khi Admin tạo/sửa món (`drinks`): job (queue Laravel) gọi OpenAI Embeddings API với `text-embedding-3-small` trên đoạn `description` ghép từ name + ingredients + tags → lưu vào `description_embedding`.
- Khi user cập nhật preferences hoặc gửi rating mới: job cập nhật lại `profile_text` (ghép sở thích khai báo + lịch sử mua gần nhất + đánh giá) → gọi Embeddings API → lưu `profile_embedding`. Nên chạy dạng queued job, không block request.

### 2.6. Cấu trúc thư mục Laravel gợi ý cho AI Agent khi sinh code

```
app/
  Models/ (User, UserPreference, Drink, Order, OrderItem, Rating, RecommendationLog)
  Services/
    EmbeddingService.php      # gọi OpenAI Embeddings, tính cosine similarity
    RecommendationService.php # orchestrate: pre-filter -> similarity -> gọi LLM re-rank
    WeatherService.php        # gọi Weather API, cache theo toạ độ + khung giờ
  Http/Controllers/Api/
    DrinkController.php
    PreferenceController.php
    RecommendationController.php
    OrderController.php
    RatingController.php
  Jobs/
    UpdateDrinkEmbeddingJob.php
    UpdateUserProfileEmbeddingJob.php
```

---

## 3. Ghi chú cho AI Agent khi lập trình

- Luôn dùng Service class riêng cho logic gọi OpenAI/Weather, không gọi trực tiếp trong Controller.
- Embedding và LLM call luôn wrap try/catch, có fallback (nếu OpenAI lỗi thì trả gợi ý theo rule-based đơn giản, không để app crash).
- Không gọi OpenAI Chat Completion trên toàn bộ menu — chỉ gọi trên candidate set đã lọc + đã rút top theo cosine similarity (đúng theo mục 2.4, bước 5-6).
- Dùng Redis cache cho: kết quả Weather API (theo toạ độ, TTL ~30 phút), embedding của `drinks` (invalidate khi món được sửa).
- Không tự thêm CI/CD hoặc testing framework nào trừ khi được yêu cầu — phạm vi file này chỉ tập trung nghiệp vụ + thiết kế hệ thống + code chức năng.
- Khi sinh migration, đặt tên bảng/cột đúng như mục 2.2, dùng snake_case theo convention Laravel.
