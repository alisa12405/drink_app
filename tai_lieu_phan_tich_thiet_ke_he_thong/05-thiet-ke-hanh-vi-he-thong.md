# 05. Thiết kế hành vi hệ thống

## 5.1. Biểu đồ trình tự đăng nhập

```mermaid
sequenceDiagram
    actor U as Người dùng
    participant V as LoginView
    participant S as Auth Store
    participant A as Axios/API
    participant C as AuthController
    participant DB as MySQL

    U->>V: Nhập email, mật khẩu
    V->>S: login(email, password)
    S->>A: POST /auth/login
    A->>C: Request JSON
    C->>DB: Tìm user theo email
    C->>C: Hash::check
    alt Thông tin hợp lệ
        C->>DB: Xóa token cũ, tạo token auth
        C-->>A: 200 {data: user, token}
        A-->>S: Response
        S->>S: Lưu token/user vào localStorage
        S-->>V: Thành công
        V-->>U: Điều hướng trang chủ
    else Sai thông tin
        C-->>A: 422 validation error
        A-->>V: Hiển thị lỗi
    end
```

## 5.2. Biểu đồ trình tự cập nhật sở thích

```mermaid
sequenceDiagram
    actor U as User
    participant V as PreferenceView
    participant C as PreferenceController
    participant P as UserPreference
    participant S as UserProfileTextService
    participant Q as Queue
    participant W as QueueWorker
    participant E as EmbeddingService

    U->>V: Chọn tag/đường/đá/dị ứng
    V->>C: PUT /api/preferences
    C->>P: updateOrCreate dữ liệu hợp lệ
    C->>S: sync(preference, user)
    S->>S: Ghép explicit + recent orders + ratings cao
    alt profile_text thay đổi
        S->>P: Update profile_text
        S->>Q: Dispatch embedding job
        Q-->>W: Consume job
        W->>E: embed(profile_text)
        E-->>W: Vector
        W->>P: Update profile_embedding
    end
    C-->>V: 200 PreferenceResource
    V-->>U: Hiển thị hồ sơ đã lưu
```

## 5.3. Biểu đồ trình tự đặt hàng

```mermaid
sequenceDiagram
    actor U as Khách hàng
    participant H as Home/Cart
    participant V as CheckoutView
    participant C as OrderController
    participant W as WeatherService
    participant DB as MySQL

    U->>H: Thêm món vào giỏ
    H->>H: Pinia khởi tạo đường/đá từ preference
    U->>V: Mở checkout, chỉnh tùy chọn
    V->>C: POST /api/orders
    C->>C: StoreOrderRequest validation
    alt Input hợp lệ
        opt Có lat/lon và user đồng ý
            C->>W: Lấy weather/temperature
            W-->>C: Context hoặc null fallback
        end
        C->>DB: BEGIN
        C->>DB: INSERT order pending + context
        loop Mỗi item
            C->>DB: Đọc drink khả dụng và giá tại thời điểm đặt
            C->>DB: INSERT order_item + price snapshot
        end
        C->>DB: UPDATE total_price
        C->>DB: COMMIT
        C-->>V: 201 OrderResource
        V->>H: clear cart
        V-->>U: Mã đơn + tổng tiền
    else Món/input không hợp lệ
        C-->>V: 422
        V-->>U: Thông báo lỗi, giữ giỏ
    end
```

## 5.4. Biểu đồ trình tự hủy và xử lý đơn

```mermaid
sequenceDiagram
    actor CUS as Customer
    actor ADM as Admin
    participant API as OrderController
    participant O as Order

    CUS->>API: PATCH /orders/{id}/cancel
    API->>API: Kiểm tra owner-or-admin
    alt Order pending
        API->>O: status = cancelled
        API-->>CUS: 200 OrderResource
    else Không pending
        API-->>CUS: 422
    end

    ADM->>API: PATCH /admin/orders/{id}/status
    API->>O: canTransitionTo(target)
    alt Chuyển hợp lệ
        API->>O: Update status
        API-->>ADM: 200 OrderResource
    else Bỏ bước/đổi trạng thái cuối
        API-->>ADM: 422
    end
```

## 5.5. Biểu đồ trình tự đánh giá

```mermaid
sequenceDiagram
    actor U as Customer
    participant V as OrderHistoryView
    participant C as RatingController
    participant DB as MySQL
    participant S as UserProfileTextService
    participant Q as Queue

    U->>V: Chọn sao và nhập nhận xét
    V->>C: POST /api/ratings
    C->>DB: Tìm order
    C->>C: Kiểm tra owner + done + drink thuộc order
    alt Hợp lệ
        C->>DB: Upsert rating theo user/order/drink
        C->>S: sync profile_text
        S-->>Q: Dispatch nếu text đổi
        C-->>V: RatingResource
        V-->>U: Đã lưu/cập nhật
    else Không hợp lệ
        C-->>V: 403 hoặc 422
    end
```

## 5.6. Biểu đồ trình tự báo cáo

```mermaid
sequenceDiagram
    actor A as Admin
    participant V as AdminReportsView
    participant C as ReportController
    participant DB as MySQL

    A->>V: Chọn from/to và áp dụng
    par Báo cáo bán chạy
        V->>C: GET best-selling-drinks
        C->>DB: Aggregate done orders + ratings
        DB-->>C: quantity/revenue/average_rating
        C-->>V: data + meta
    and Hiệu quả gợi ý
        V->>C: GET recommendation-effectiveness
        C->>DB: Đọc logs và đối chiếu order_items
        DB-->>C: Dữ liệu chuyển đổi
        C-->>V: totals/rate/by_position
    end
    V-->>A: Bảng và thẻ thống kê
```

## 5.7. Biểu đồ trình tự recommendation

```mermaid
sequenceDiagram
    actor U as Customer
    participant F as Frontend
    participant C as RecommendationController
    participant R as RecommendationService
    participant W as WeatherService
    participant E as EmbeddingService
    participant L as LLM
    participant DB as MySQL

    U->>F: Yêu cầu gợi ý, tùy chọn cấp vị trí
    F->>C: GET /api/recommendations
    Note over F,C: Vị trí và occasion là tùy chọn
    C->>R: recommend(user, context)
    opt Có lat/lon
        R->>W: getCurrentWeather
        W-->>R: weather hoặc null fallback
    end
    R->>DB: Lấy preference + drinks available
    R->>R: Pre-filter theo context
    R->>E: cosine(profile, drink vectors)
    E-->>R: Top 10
    alt LLM sẵn sàng
        R->>L: Structured re-rank top 10
        L-->>R: Top 5 + explanations
    else Lỗi/thiếu vector
        R->>R: Rule-based fallback cùng schema
    end
    R->>DB: INSERT recommendation_log
    R-->>C: Kết quả
    C-->>F: Món + score + explanation
    F-->>U: Hiển thị và cho thêm vào giỏ
```

## 5.8. Biểu đồ hoạt động đặt hàng

```mermaid
flowchart TD
    A([Bắt đầu]) --> B[Xem menu]
    B --> C[Thêm món vào giỏ]
    C --> D{Giỏ rỗng?}
    D -- Có --> B
    D -- Không --> E[Mở checkout]
    E --> F[Chỉnh quantity/đường/đá/note]
    F --> G[Chọn dine_in/takeaway và occasion]
    G --> H[Gửi POST /orders]
    H --> I{Validation và transaction thành công?}
    I -- Không --> J[Hiển thị lỗi, giữ giỏ]
    J --> F
    I -- Có --> K[Xóa giỏ]
    K --> L[Hiển thị xác nhận đơn]
    L --> M([Kết thúc])
```

## 5.9. Biểu đồ hoạt động quản lý trạng thái đơn

```mermaid
flowchart TD
    A([Admin mở danh sách]) --> B[Lọc/phân trang]
    B --> C[Chọn đơn]
    C --> D{Trạng thái của đơn}
    D -- pending --> E[Chọn confirmed hoặc cancelled]
    D -- confirmed --> F[Chọn done hoặc cancelled]
    D -- done/cancelled --> G[Không còn thao tác]
    E --> H{canTransitionTo?}
    F --> H
    H -- Có --> I[Cập nhật trạng thái]
    H -- Không --> J[Trả 422]
    I --> B
    J --> C
    G --> B
```

## 5.10. Biểu đồ trạng thái đơn hàng

```mermaid
stateDiagram-v2
    [*] --> pending: Tạo đơn
    pending --> confirmed: Admin xác nhận
    pending --> cancelled: Customer/Admin hủy
    confirmed --> done: Admin hoàn tất
    confirmed --> cancelled: Admin hủy ngoại lệ
    done --> [*]
    cancelled --> [*]
```

Không có chuyển trạng thái ngược; không cho phép `pending → done`.

## 5.11. Biểu đồ trạng thái tài khoản/phiên

```mermaid
stateDiagram-v2
    [*] --> ChuaDangNhap
    ChuaDangNhap --> DaDangNhap: Register/Login thành công
    DaDangNhap --> DaDangNhap: Request có Bearer token hợp lệ
    DaDangNhap --> ChuaDangNhap: Logout hoặc API trả 401
```

Trong phạm vi phiên bản này, mô hình tài khoản chỉ xét trạng thái có hoặc không có phiên hợp lệ. Khóa tài khoản và xác minh email được để ngoài phạm vi.
