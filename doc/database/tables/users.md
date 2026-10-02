# Bảng `users`

## Mục đích

Lưu tài khoản người dùng của hệ thống, phục vụ:
- UC-01: Đăng ký / đăng nhập.
- Phân biệt 2 actor chính trong hệ thống: **Customer** (khách hàng) và **Admin** (quản trị viên) — xem mục 1.1 SPEC.

## Cấu trúc bảng

| Cột | Kiểu | Null | Default | Ràng buộc | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | Khoá chính |
| `name` | VARCHAR(255) | NO | | | Tên hiển thị |
| `email` | VARCHAR(255) | NO | | UNIQUE | Email đăng nhập |
| `email_verified_at` | TIMESTAMP | YES | NULL | | Thời điểm xác thực email (nếu bật email verification) |
| `password` | VARCHAR(255) | NO | | | Mật khẩu, hash bằng bcrypt (Laravel `hashed` cast) |
| `role` | ENUM('customer','admin') | NO | `'customer'` | INDEX | Phân quyền |
| `remember_token` | VARCHAR(100) | YES | NULL | | Token "remember me" |
| `created_at` | TIMESTAMP | YES | NULL | | |
| `updated_at` | TIMESTAMP | YES | NULL | | |

## Index & Constraints

- PRIMARY KEY: `id`
- UNIQUE: `email`
- INDEX: `role` (dùng khi filter danh sách admin/customer, VD trang quản lý user)

## Quan hệ (Relationships)

| Quan hệ | Bảng liên quan | Loại |
|---|---|---|
| `hasOne` | `user_preferences` | 1 – 1 |
| `hasMany` | `orders` | 1 – N |
| `hasMany` | `ratings` | 1 – N |
| `hasMany` | `recommendation_logs` | 1 – N |

## Business rules liên quan

- Một `email` chỉ thuộc về 1 tài khoản (unique).
- `role = 'admin'` mới được truy cập các API dưới `/api/admin/*` (Menu CRUD, quản lý đơn hàng, báo cáo — UC-08, UC-09, UC-10). Việc phân quyền nên hiện thực bằng Laravel Policy/Middleware (`role:admin`), không hardcode danh sách email trong code.
- Khi xoá 1 user (nếu có tính năng này), cần cân nhắc: nếu user còn `orders`/`ratings` thì nên chặn xoá (`ON DELETE RESTRICT` ở các bảng con) để bảo toàn lịch sử giao dịch — chỉ nên vô hiệu hoá tài khoản (VD thêm cờ `is_active` nếu cần trong tương lai) thay vì xoá cứng.

## Ghi chú thiết kế

- **Cột `role` là bổ sung so với SPEC gốc** (mục 2.2 SPEC chỉ liệt kê `id, name, email, password, created_at, updated_at`). Được thêm sau khi xác nhận với người yêu cầu, vì SPEC mục 1.1 mô tả rõ 2 actor Customer/Admin nhưng không có cách nào phân biệt họ trong bảng `users` như mô tả gốc. Phương án thay thế đã cân nhắc nhưng không chọn: tạo bảng `admins` riêng (phức tạp hoá quan hệ không cần thiết ở quy mô đồ án).
- `email_verified_at` và `remember_token` là 2 cột chuẩn của Laravel Auth scaffolding (đã có sẵn trong migration hiện tại `0001_01_01_000000_create_users_table.php`), giữ nguyên vì không xung đột với SPEC.
- Model tương ứng: `app/Models/User.php` (đã có sẵn, cần bổ sung `role` vào `#[Fillable]` và có thể thêm accessor `isAdmin(): bool`).
