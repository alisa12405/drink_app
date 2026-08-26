# Bảng `order_items`

## Mục đích

Lưu chi tiết từng món trong 1 đơn hàng (đơn hàng có thể gồm nhiều món khác nhau, mỗi món có tuỳ chọn riêng) — phục vụ UC-05.

## Cấu trúc bảng

| Cột | Kiểu | Null | Default | Ràng buộc | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | Khoá chính |
| `order_id` | BIGINT UNSIGNED | NO | | FK → `orders.id`, INDEX | Đơn hàng chứa dòng này |
| `drink_id` | BIGINT UNSIGNED | NO | | FK → `drinks.id`, INDEX | Món được đặt |
| `quantity` | SMALLINT UNSIGNED | NO | `1` | | Số lượng |
| `sugar_level` | ENUM('0','30','50','70','100') | NO | `'100'` | | % đường đã chọn cho món này |
| `ice_level` | ENUM('no_ice','less_ice','normal_ice','extra_ice') | NO | `'normal_ice'` | | Mức đá đã chọn cho món này |
| `note` | VARCHAR(255) | YES | NULL | | Ghi chú thêm của khách |
| `unit_price` | DECIMAL(10,2) | NO | | | Đơn giá snapshot tại thời điểm đặt |
| `subtotal` | DECIMAL(10,2) | NO | | | `unit_price * quantity` |
| `created_at` | TIMESTAMP | YES | NULL | | |
| `updated_at` | TIMESTAMP | YES | NULL | | |

## Index & Constraints

- PRIMARY KEY: `id`
- INDEX: `order_id`, `drink_id`
- FOREIGN KEY: `order_id → orders.id` **ON DELETE CASCADE** (xoá đơn thì xoá luôn các dòng chi tiết thuộc đơn đó — không có ý nghĩa tồn tại độc lập).
- FOREIGN KEY: `drink_id → drinks.id` **ON DELETE RESTRICT** (không cho xoá cứng món còn được tham chiếu — kết hợp với cơ chế soft delete ở bảng `drinks`, trong thực tế FK này gần như không bao giờ bị vi phạm vì `drinks` không xoá cứng).

## Quan hệ (Relationships)

| Quan hệ | Bảng liên quan | Loại |
|---|---|---|
| `belongsTo` | `orders` | N – 1 |
| `belongsTo` | `drinks` | N – 1 |

## Business rules liên quan

- Khi tạo đơn hàng (UC-05): với mỗi món trong giỏ hàng, `unit_price` được **copy từ `drinks.price` tại thời điểm đặt** (không tham chiếu động), `subtotal = unit_price * quantity`. `orders.total_price` = tổng `subtotal` của tất cả `order_items` thuộc đơn đó.
- `sugar_level`/`ice_level` mặc định lấy từ `user_preferences.sugar_level_default`/`ice_level_default` khi thêm vào giỏ, nhưng user có thể tuỳ chỉnh riêng cho từng món/từng đơn — do đó 2 cột này **độc lập** với bảng `user_preferences`, không đọc lại từ đó sau khi đơn đã tạo.
- Không cho phép sửa `order_items` của đơn đã ở trạng thái `done` (thừa hưởng business rule từ `orders.status`, mục 1.5 SPEC).

## Ghi chú thiết kế

- **`unit_price` và `subtotal` là 2 cột bổ sung so với SPEC gốc** (mục 2.2 SPEC chỉ liệt kê `order_id, drink_id, quantity, sugar_level, ice_level, note`), được thêm sau khi xác nhận với người yêu cầu. Lý do: nếu không lưu snapshot giá, khi Admin đổi `drinks.price` sau này, mọi phép tính/hiển thị lại giá của đơn hàng cũ (VD trong báo cáo thống kê UC-10, hoá đơn lịch sử UC-06) sẽ bị sai lệch do phải join động với giá **hiện tại** của `drinks`. Phương án thay thế đã cân nhắc nhưng không chọn: không lưu giá, tính on-the-fly qua join — chấp nhận rủi ro sai lệch lịch sử.
- **`created_at`/`updated_at` là bổ sung theo convention Laravel** (SPEC không liệt kê timestamp nào cho bảng này) — giữ để nhất quán và hỗ trợ audit/debug, không ảnh hưởng nghiệp vụ.
- Model tương ứng: `app/Models/OrderItem.php` (hiện là stub trống, cần bổ sung `$fillable`).
