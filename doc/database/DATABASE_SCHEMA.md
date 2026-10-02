# Database Schema Overview

> Tổng quan thiết kế database: tên bảng, mục đích, cấu trúc bảng, dữ liệu mẫu. Chi tiết đầy đủ (index, FK, business rule, ghi chú thiết kế) xem tại từng file trong [`tables/`](tables/). Bối cảnh nghiệp vụ: [`SPEC_smart-drink-recommendation-app.md`](../SPEC_smart-drink-recommendation-app.md).

## Mục lục

1. [users](#1-users)
2. [user_preferences](#2-user_preferences)
3. [drinks](#3-drinks)
4. [orders](#4-orders)
5. [order_items](#5-order_items)
6. [ratings](#6-ratings)
7. [recommendation_logs](#7-recommendation_logs)
8. `notifications` — thông báo bền cho Customer/Admin, dùng morph `notifiable`, có `data` JSON và `read_at`.

---

## 1. `users`

**Mục đích**: Lưu tài khoản người dùng (Customer & Admin), phục vụ đăng ký/đăng nhập (UC-01) và phân quyền.

**Cấu trúc bảng**:

| Cột | Kiểu | Null | Default | Mô tả |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED (PK, AI) | NO | | Khoá chính |
| `name` | VARCHAR(255) | NO | | Tên hiển thị |
| `email` | VARCHAR(255) | NO | | Email đăng nhập, UNIQUE |
| `email_verified_at` | TIMESTAMP | YES | NULL | Thời điểm xác thực email |
| `password` | VARCHAR(255) | NO | | Mật khẩu đã hash (bcrypt) |
| `role` | ENUM('customer','admin') | NO | 'customer' | Phân quyền Customer/Admin |
| `remember_token` | VARCHAR(100) | YES | NULL | Token "remember me" của Laravel |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | |

**Dữ liệu mẫu**:

```json
{
  "id": 1,
  "name": "Nguyễn Văn A",
  "email": "vana@example.com",
  "email_verified_at": "2026-08-20T10:00:00Z",
  "role": "customer",
  "created_at": "2026-08-20T10:00:00Z",
  "updated_at": "2026-08-20T10:00:00Z"
}
```

---

## 2. `user_preferences`

**Mục đích**: Lưu hồ sơ sở thích cá nhân hoá của user (explicit + tổng hợp implicit), là nguồn dữ liệu chính cho vector `profile_embedding` dùng trong Recommendation module (UC-02, FR1).

**Cấu trúc bảng**:

| Cột | Kiểu | Null | Default | Mô tả |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED (PK, AI) | NO | | Khoá chính |
| `user_id` | BIGINT UNSIGNED (FK → users.id, UNIQUE) | NO | | 1 user – 1 hồ sơ |
| `taste_tags` | JSON | YES | NULL | VD: `["ngọt","có_caffeine"]` |
| `sugar_level_default` | ENUM('0','30','50','70','100') | NO | '100' | % đường mặc định |
| `ice_level_default` | ENUM('no_ice','less_ice','normal_ice','extra_ice') | NO | 'normal_ice' | Mức đá mặc định |
| `allergy_notes` | TEXT | YES | NULL | Ghi chú dị ứng |
| `profile_text` | TEXT | YES | NULL | Đoạn text tổng hợp dùng để embed |
| `profile_embedding` | JSON | YES | NULL | Vector 1536 chiều (text-embedding-3-small) |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | |

**Dữ liệu mẫu**:

```json
{
  "id": 1,
  "user_id": 1,
  "taste_tags": ["ngọt", "có_caffeine", "trái_cây"],
  "sugar_level_default": "70",
  "ice_level_default": "normal_ice",
  "allergy_notes": "Dị ứng đậu phộng",
  "profile_text": "Khách hàng thích đồ uống ngọt vừa, có caffeine, hương trái cây. Từng đặt: Trà đào cam sả, Cà phê sữa đá. Đánh giá cao: Trà sữa trân châu (5 sao).",
  "profile_embedding": [0.0123, -0.0456, "...(1536 số thực)..."],
  "created_at": "2026-08-20T10:05:00Z",
  "updated_at": "2026-08-25T08:30:00Z"
}
```

---

## 3. `drinks`

**Mục đích**: Danh mục menu đồ uống, phục vụ xem menu (UC-03), Admin CRUD (UC-08), và là nguồn ứng viên cho Recommendation module.

**Cấu trúc bảng**:

| Cột | Kiểu | Null | Default | Mô tả |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED (PK, AI) | NO | | Khoá chính |
| `name` | VARCHAR(255) | NO | | Tên món |
| `description` | TEXT | YES | NULL | Mô tả món |
| `ingredients` | TEXT | YES | NULL | Nguyên liệu (mô tả dạng text) |
| `category` | VARCHAR(100) | NO | | VD: "trà sữa", "cà phê", "nước ép" |
| `price` | DECIMAL(10,2) | NO | | Đơn giá (VNĐ) |
| `calories` | INT UNSIGNED | YES | NULL | Số calo |
| `temperature_type` | ENUM('hot','cold','both') | NO | 'both' | Loại nhiệt độ phục vụ |
| `tags` | JSON | YES | NULL | VD: `["best_seller","ít_ngọt"]` |
| `image_url` | VARCHAR(255) | YES | NULL | Đường dẫn ảnh món |
| `image_path` | VARCHAR(255) | YES | NULL | File ảnh do Admin tải lên disk public |
| `is_available` | BOOLEAN | NO | true | Còn bán / đã ẩn khỏi menu |
| `description_embedding` | JSON | YES | NULL | Vector từ name+ingredients+tags |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Dữ liệu mẫu**:

```json
{
  "id": 12,
  "name": "Trà đào cam sả",
  "description": "Trà đen ủ lạnh kết hợp đào ngâm, cam tươi và sả thơm mát.",
  "ingredients": "Trà đen, đào ngâm, cam tươi, sả, đường",
  "category": "trà trái cây",
  "price": 45000.00,
  "calories": 180,
  "temperature_type": "cold",
  "tags": ["best_seller", "trái_cây", "giải_khát"],
  "image_url": "/images/drinks/tra-dao-cam-sa.jpg",
  "is_available": true,
  "description_embedding": [0.0211, -0.0187, "...(1536 số thực)..."],
  "created_at": "2026-08-15T09:00:00Z",
  "updated_at": "2026-08-24T14:20:00Z",
  "deleted_at": null
}
```

---

## 4. `orders`

**Mục đích**: Lưu đơn hàng của customer hoặc khách vãng lai, kèm snapshot ngữ cảnh lúc đặt (UC-05, UC-06, UC-09).

**Cấu trúc bảng**:

| Cột | Kiểu | Null | Default | Mô tả |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED (PK, AI) | NO | | Khoá chính |
| `user_id` | BIGINT UNSIGNED (FK → users.id) | YES | NULL | Chủ đơn đăng nhập; `NULL` với khách vãng lai |
| `customer_name` | VARCHAR(100) | YES | NULL | Snapshot tên customer hoặc tên guest nhập lúc checkout |
| `status` | ENUM('pending','confirmed','done','cancelled') | NO | 'pending' | Trạng thái đơn |
| `total_price` | DECIMAL(10,2) | NO | | Tổng tiền = Σ(order_items.subtotal) |
| `context_snapshot` | JSON | YES | NULL | `{hour, weather, temperature, occasion}` lúc đặt |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | Thời điểm đổi trạng thái gần nhất |

**Dữ liệu mẫu**:

```json
{
  "id": 501,
  "user_id": 1,
  "status": "done",
  "total_price": 90000.00,
  "context_snapshot": {
    "hour": 15,
    "weather": "sunny",
    "temperature": 34.5,
    "occasion": "sau khi tập gym"
  },
  "created_at": "2026-08-25T15:10:00Z",
  "updated_at": "2026-08-25T15:40:00Z"
}
```

---

## 5. `order_items`

**Mục đích**: Chi tiết từng món trong 1 đơn hàng, kèm snapshot đơn giá tại thời điểm đặt.

**Cấu trúc bảng**:

| Cột | Kiểu | Null | Default | Mô tả |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED (PK, AI) | NO | | Khoá chính |
| `order_id` | BIGINT UNSIGNED (FK → orders.id) | NO | | Đơn hàng chứa món này |
| `drink_id` | BIGINT UNSIGNED (FK → drinks.id) | NO | | Món được đặt |
| `quantity` | SMALLINT UNSIGNED | NO | 1 | Số lượng |
| `sugar_level` | ENUM('0','30','50','70','100') | NO | '100' | % đường đã chọn |
| `ice_level` | ENUM('no_ice','less_ice','normal_ice','extra_ice') | NO | 'normal_ice' | Mức đá đã chọn |
| `note` | VARCHAR(255) | YES | NULL | Ghi chú thêm (VD: "ít đá hơn bình thường") |
| `unit_price` | DECIMAL(10,2) | NO | | Đơn giá snapshot tại thời điểm đặt |
| `subtotal` | DECIMAL(10,2) | NO | | `unit_price * quantity` |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | |

**Dữ liệu mẫu**:

```json
{
  "id": 900,
  "order_id": 501,
  "drink_id": 12,
  "quantity": 2,
  "sugar_level": "70",
  "ice_level": "normal_ice",
  "note": null,
  "unit_price": 45000.00,
  "subtotal": 90000.00,
  "created_at": "2026-08-25T15:10:00Z",
  "updated_at": "2026-08-25T15:10:00Z"
}
```

---

## 6. `ratings`

**Mục đích**: Lưu đánh giá/feedback của user cho món đã uống (UC-07), làm input implicit cho hồ sơ sở thích.

**Cấu trúc bảng**:

| Cột | Kiểu | Null | Default | Mô tả |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED (PK, AI) | NO | | Khoá chính |
| `user_id` | BIGINT UNSIGNED (FK → users.id) | NO | | Người đánh giá |
| `drink_id` | BIGINT UNSIGNED (FK → drinks.id) | NO | | Món được đánh giá |
| `order_id` | BIGINT UNSIGNED (FK → orders.id) | NO | | Đơn hàng liên quan |
| `rating` | TINYINT UNSIGNED | NO | | 1–5 sao |
| `comment` | TEXT | YES | NULL | Bình luận |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | |

Ràng buộc: UNIQUE `(user_id, order_id, drink_id)`.

**Dữ liệu mẫu**:

```json
{
  "id": 210,
  "user_id": 1,
  "drink_id": 12,
  "order_id": 501,
  "rating": 5,
  "comment": "Trà đào rất ngon, vị cam sả hài hoà, sẽ đặt lại!",
  "created_at": "2026-08-25T18:00:00Z",
  "updated_at": "2026-08-25T18:00:00Z"
}
```

---

## 7. `recommendation_logs`

**Mục đích**: Ghi log mỗi lượt gợi ý (context, danh sách gợi ý, món user thực sự chọn) để đánh giá chất lượng gợi ý sau này (FR8).

**Cấu trúc bảng**:

| Cột | Kiểu | Null | Default | Mô tả |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED (PK, AI) | NO | | Khoá chính |
| `user_id` | BIGINT UNSIGNED (FK → users.id) | NO | | User nhận gợi ý |
| `context_snapshot` | JSON | YES | NULL | Ngữ cảnh tại thời điểm gợi ý |
| `candidate_drink_ids` | JSON | YES | NULL | Top 10 ID sau pre-filter + cosine similarity |
| `final_ranked_ids` | JSON | YES | NULL | Top 5 ID sau khi LLM re-rank |
| `llm_explanation` | TEXT | YES | NULL | Giải thích của LLM (có thể là văn bản tổng hợp hoặc chuỗi JSON `{drink_id: explanation}` nếu cần giải thích riêng từng món) |
| `created_at` | TIMESTAMP | YES | NULL | Bảng log immutable, không có `updated_at` |

**Dữ liệu mẫu**:

```json
{
  "id": 3050,
  "user_id": 1,
  "context_snapshot": { "hour": 15, "weather": "sunny", "temperature": 34.5 },
  "candidate_drink_ids": [12, 7, 19, 3, 25, 8, 14, 21, 6, 30],
  "final_ranked_ids": [12, 19, 7, 25, 3],
  "llm_explanation": "{\"12\":\"Trời nóng, trà đào cam sả mát lạnh giải khát tốt.\",\"19\":\"Phù hợp sở thích trái cây và caffeine nhẹ của bạn.\"}",
  "created_at": "2026-08-25T15:08:30Z"
}
```
