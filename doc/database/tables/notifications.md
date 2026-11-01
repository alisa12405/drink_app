# Bảng `notifications`

## Mục đích

Lưu trữ thông báo bền vững cho người dùng (Customer & Admin) trong database — phục vụ tính năng thông báo trực tuyến (NOTIFY-01, FR11).
- Admin nhận thông báo khi có đơn hàng mới (`NewOrderNotification`).
- Customer nhận thông báo khi đặt hàng thành công (`OrderPlacedNotification`) và khi trạng thái đơn hàng thay đổi (`OrderStatusUpdatedNotification`).

## Cấu trúc bảng

| Cột | Kiểu | Null | Default | Ràng buộc | Mô tả |
|---|---|---|---|---|---|
| `id` | CHAR(36) / UUID | NO | | PK | Khóa chính dạng UUID chuẩn của Laravel Notifications |
| `type` | VARCHAR(255) | NO | | | Tên class notification (VD: `App\Notifications\NewOrderNotification`) |
| `notifiable_type` | VARCHAR(255) | NO | | INDEX | Class của model nhận thông báo (`App\Models\User`) |
| `notifiable_id` | BIGINT UNSIGNED | NO | | INDEX | ID của user nhận thông báo |
| `data` | TEXT / JSON | NO | | | Payload thông báo (order_id, title, message, status...) |
| `read_at` | TIMESTAMP | YES | NULL | | Thời điểm người dùng đọc thông báo (NULL nếu chưa đọc) |
| `created_at` | TIMESTAMP | YES | NULL | | Thời điểm phát sinh thông báo |
| `updated_at` | TIMESTAMP | YES | NULL | | Thời điểm cập nhật trạng thái |

## Index & Constraints

- PRIMARY KEY: `id` (UUID)
- INDEX: `(notifiable_type, notifiable_id)` (Laravel polymorphic index) phục vụ truy vấn danh sách thông báo theo từng user.

## Quan hệ (Relationships)

| Quan hệ | Bảng liên quan | Loại |
|---|---|---|
| `morphTo` | `users` (qua `notifiable_type`, `notifiable_id`) | N – 1 |

## Business rules liên quan

- Mỗi thông báo chỉ thuộc về duy nhất một người dùng nhận (owner); API `/api/notifications` chỉ trả thông báo của chính user đang xác thực Sanctum.
- Khách vãng lai (`user_id = null`) không lưu thông báo database vì không có tài khoản xác thực.
- Khách hàng có thể đánh dấu đã đọc từng thông báo qua `PATCH /api/notifications/{id}/read` hoặc đánh dấu tất cả qua `PATCH /api/notifications/read-all`.
- Frontend thực hiện polling mỗi 10 giây để lấy 30 thông báo gần nhất kèm số lượng chưa đọc, hiển thị chuông thông báo và bật toast khi có thông báo mới.

## Ghi chú thiết kế

- Sử dụng cơ chế Database Notification chuẩn của Laravel (`Illuminate\Notifications\DatabaseNotification`).
- Model `User` sử dụng trait `Notifiable` để gửi và truy vấn quan hệ `notifications()`.
