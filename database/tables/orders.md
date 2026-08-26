# Bảng `orders`

## Mục đích

Lưu đơn hàng của khách — phục vụ UC-05 (đặt đồ uống), UC-06 (xem lịch sử đơn hàng), UC-09 (Admin quản lý đơn hàng).

## Cấu trúc bảng

| Cột | Kiểu | Null | Default | Ràng buộc | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | Khoá chính |
| `user_id` | BIGINT UNSIGNED | NO | | FK → `users.id`, INDEX | Chủ đơn hàng |
| `status` | ENUM('pending','confirmed','done','cancelled') | NO | `'pending'` | INDEX | Trạng thái đơn hàng |
| `total_price` | DECIMAL(10,2) | NO | | | Tổng tiền = Σ(`order_items.subtotal`) |
| `context_snapshot` | JSON | YES | NULL | | Ngữ cảnh lúc đặt: `{hour, weather, temperature, occasion}` |
| `created_at` | TIMESTAMP | YES | NULL | INDEX | Thời điểm tạo đơn |
| `updated_at` | TIMESTAMP | YES | NULL | | Thời điểm đổi trạng thái gần nhất |

## Index & Constraints

- PRIMARY KEY: `id`
- INDEX: `user_id` (lấy lịch sử đơn hàng của 1 user — UC-06), `status` (Admin lọc đơn theo trạng thái — UC-09), `created_at` (sắp xếp/thống kê theo thời gian — UC-10).
- FOREIGN KEY: `user_id → users.id` **ON DELETE RESTRICT** (không cho xoá user nếu còn đơn hàng, bảo toàn dữ liệu tài chính/lịch sử).

## Quan hệ (Relationships)

| Quan hệ | Bảng liên quan | Loại |
|---|---|---|
| `belongsTo` | `users` | N – 1 |
| `hasMany` | `order_items` | 1 – N |
| `hasMany` | `ratings` | 1 – N |

## Business rules liên quan

- `status` chỉ được chuyển theo chiều: `pending → confirmed → done`, hoặc `pending/confirmed → cancelled`. Không có chiều ngược lại.
- Đơn hàng ở trạng thái `done` **không được sửa món** (không cho thêm/sửa/xoá `order_items`); chỉ được huỷ (`cancelled`) khi còn ở trạng thái **trước** `confirmed` (mục 1.5 SPEC). Cụ thể: huỷ hợp lệ khi `status = 'pending'`; khi đã `confirmed` trở đi thì không tự huỷ được nữa (cần Admin can thiệp qua UC-09 nếu có ngoại lệ).
- `context_snapshot` được ghi **1 lần duy nhất tại thời điểm tạo đơn** (bước 2 mục 2.4 SPEC), không cập nhật lại sau đó — dùng để phân tích hành vi mua hàng theo ngữ cảnh (VD: thống kê "giờ nào bán chạy nhất", input cho việc đánh giá & cải thiện thuật toán gợi ý sau này — UC-10).
- `total_price` nên được tính và ghi tại thời điểm tạo đơn (snapshot), không tính lại on-the-fly bằng cách join `order_items` mỗi lần đọc — tránh sai lệch nếu logic tính giá thay đổi trong tương lai.

## Ghi chú thiết kế

- **`updated_at` là bổ sung theo convention Laravel** (SPEC mục 2.2 chỉ liệt kê `created_at`); được thêm để có thể biết chính xác thời điểm đơn hàng chuyển trạng thái gần nhất (hữu ích cho UC-09 — Admin xem/đổi trạng thái đơn). Không ảnh hưởng đến cấu trúc nghiệp vụ.
- FK `user_id` dùng `ON DELETE RESTRICT` thay vì `CASCADE` — khác với `user_preferences` — vì đơn hàng là dữ liệu giao dịch/tài chính cần được bảo toàn ngay cả khi tài khoản user không còn hoạt động (nên có cơ chế vô hiệu hoá tài khoản thay vì xoá cứng, xem `tables/users.md`).
- Cấu trúc gợi ý cho `context_snapshot`:

```json
{
  "hour": 15,
  "weather": "sunny",
  "temperature": 34.5,
  "occasion": "sau khi tập gym",
  "lat": 10.7769,
  "lon": 106.7009
}
```

  Trong đó `occasion` là tuỳ chọn do user chọn tay (FR2), `lat`/`lon` là tuỳ chọn nếu cần lưu lại vị trí chính xác lúc đặt. Nếu không xác định được ngữ cảnh (không cấp quyền vị trí — mục 1.5 SPEC), `weather`/`temperature`/`lat`/`lon` có thể là `null`, chỉ còn `hour`.
- Model tương ứng: `app/Models/Order.php` (hiện là stub trống, cần bổ sung `$fillable`, cast `context_snapshot` sang `array`, cast `status` sang PHP `enum` nếu dùng Laravel 11 native enum casting).
