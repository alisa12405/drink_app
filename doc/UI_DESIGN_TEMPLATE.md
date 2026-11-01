# UI Design Template — tổng hợp từ `template_frontend/`

> File này tổng hợp lại toàn bộ khuôn mẫu thiết kế (design tokens, component pattern, page layout) tìm thấy trong thư mục `template_frontend/` (file mẫu Figma Make, tên gốc "Food Delivery Website Design — Community"), để AI Agent/Dev tham chiếu **trước khi bắt đầu code UI** cho frontend Vue của dự án. Xem kế hoạch triển khai theo từng trang ở mục 6.

> ⚠️ **Luật bắt buộc**: Mỗi khi có **thay đổi lớn về UI** (thêm/sửa trang, đổi cấu trúc component, đổi design token, đổi luồng thao tác...), PHẢI cập nhật lại file này ngay trong cùng lượt làm việc — sửa/ghi đè nội dung mô tả cũ cho khớp với code mới, **xoá hẳn phần mô tả cũ đã lỗi thời** (không giữ song song 2 bản mô tả cũ/mới gây rối). File này phải luôn phản ánh đúng trạng thái hiện tại của code, không phải nhật ký lịch sử thay đổi.

---

## 1. Nguồn gốc & phạm vi sử dụng

- **Nguồn**: `template_frontend/` — export từ Figma Make, stack gốc là **React + Vite + Tailwind CSS v4 + shadcn/ui (Radix) + lucide-react**. Toàn bộ UI nằm gọn trong 1 file `src/app/App.tsx` (913 dòng), phần `src/app/components/ui/*.tsx` là bộ thư viện component shadcn/ui đầy đủ (chưa được `App.tsx` dùng hết, chỉ dùng làm sẵn có nếu cần mở rộng sau — accordion, dialog, sheet, table, calendar, chart, carousel, v.v.).
- **Bản quyền**: theo `ATTRIBUTIONS.md` — component từ shadcn/ui (MIT license), ảnh minh hoạ từ Unsplash (Unsplash license). Ảnh mẫu (`src/imports/*.png`) là ảnh chụp UI thực tế do Figma render, chỉ dùng tham khảo bố cục, **không đưa nguyên ảnh/asset vào sản phẩm cuối**.
- **Khác biệt kỹ thuật quan trọng**: Frontend dự án hiện tại dùng **Vue 3 (Composition API) + Vue Router + Pinia**, KHÔNG dùng React. ⇒ Template chỉ dùng để tham chiếu **bố cục, màu sắc, spacing, hành vi tương tác, cấu trúc component**; khi lập trình phải viết lại bằng Vue SFC (`<script setup>` + `<template>`), thay `lucide-react` bằng `lucide-vue-next` (hoặc bộ icon SVG inline tương đương), thay `useState` bằng `ref`/`reactive`.
- **Khác biệt nghiệp vụ cần điều chỉnh**: template là web đặt đồ ăn có **giao hàng** (delivery/pickup/dine-in, địa chỉ Kuwait, phí ship, KNET, mã giảm giá...), còn SPEC dự án (`SPEC_smart-drink-recommendation-app.md`) mô tả **quán trà sữa/cà phê tại chỗ**, đặt đồ uống với size/đường/đá, **thanh toán giả lập** (không có địa chỉ giao hàng, không có nhiều phương thức thanh toán thật). Khi áp dụng cần bỏ/đơn giản hoá các phần: tab Delivery/Pickup/Dine-in, form địa chỉ giao hàng, chọn khu vực, KNET/thẻ tín dụng thật, mã giảm giá (chưa có trong SPEC).

---

## 2. Design tokens (rút từ `theme.css`, `fonts.css`)

> **Cập nhật sau khi triển khai Bước 0**: dự án đã **đổi màu chủ đạo (primary) từ cam `#f97316` (bản gốc, thiết kế cho nhà hàng) sang nâu trà/caramel `#b45309`** — phù hợp với quán trà sữa/cà phê và đồng bộ với các màu đã dùng rải rác trong code cũ. Toàn bộ token bên dưới (cột "Giá trị") là giá trị **đã áp dụng thực tế** trong `frontend/src/assets/theme.css`, không phải giá trị gốc của template. Các quyết định này đã được xác nhận với người dùng trước khi code (xem mục 6).

### 2.1 Bảng màu (light mode — dùng làm mặc định)

| Token | Giá trị đã dùng trong dự án | Ý nghĩa dùng |
|---|---|---|
| `--background` | `#faf6f0` (kem ấm) | Nền trang |
| `--foreground` | `#3f2a1d` (nâu đậm) | Màu chữ chính |
| `--card` | `#ffffff` | Nền card/panel |
| `--primary` | `#b45309` (nâu trà/caramel) | Màu thương hiệu, nút chính, trạng thái active |
| `--primary-hover` | `#92400e` | Trạng thái hover của nút/link primary |
| `--primary-foreground` | `#ffffff` | Chữ trên nền primary |
| `--secondary` | `#fff7ed` | Nền phụ, hover nhẹ, badge nhạt |
| `--secondary-foreground` | `#9a3412` | Chữ trên nền secondary |
| `--accent` | `#fed7aa` | Viền/nhấn nhẹ (badge, trạng thái được chọn) |
| `--accent-foreground` | `#9a3412` | Chữ trên nền accent |
| `--muted` | `#f1ede6` | Nền input/khối trung tính |
| `--muted-foreground` | `#6b5848` | Chữ phụ, placeholder |
| `--destructive` / `--destructive-bg` | `#b91c1c` / `#fef2f2` | Chữ lỗi / nền khối thông báo lỗi |
| `--success` / `--success-bg` | `#15803d` / `#f0fdf4` | Chữ thành công / nền khối thông báo thành công |
| `--border` | `#eadfce` | Viền card/input |
| `--input-background` | `#fffdf8` | Nền input |
| `--ring` | `#d97706` | Màu focus ring cho input |
| `--radius` | `0.625rem` (10px) | Bo góc cơ bản (`radius-sm/md/lg/xl` suy ra từ đây) |

Toàn bộ token trên được khai báo bằng CSS variables trong `frontend/src/assets/theme.css` và map sang Tailwind qua khối `@theme inline` (cú pháp CSS-first config của Tailwind v4), nên có thể dùng trực tiếp làm class Tailwind: `bg-primary`, `text-foreground`, `border-border`, `bg-destructive-bg`, v.v.

Màu bổ trợ dùng trực tiếp trong `App.tsx` (không phải token nhưng lặp lại nhiều lần, nên đưa thành palette phụ):
- Xanh lá thành công: `text-green-600`, nền `bg-green-50`/`bg-green-100` (trạng thái "Open Now", "100% Secure", đơn hàng thành công).
- Vàng/cam đánh giá sao: `text-amber-500`.
- Xám trung tính cho text phụ: `text-gray-400/500/600`, viền `border-gray-100/200`.

### 2.2 Typography & Animations

- Font chữ hiển thị chuẩn tiếng Việt 100%: **Plus Jakarta Sans** (tiêu đề/heading/logo) kết hợp **Be Vietnam Pro** (nội dung/body và bổ trợ).
  - Khắc phục hoàn toàn lỗi vỡ/mất nét dấu tiếng Việt (huyền, sắc, hỏi, ngã, nặng, ư, ơ, đ) gặp phải với Poppins trước đây.
  - Được preconnect và load đầy đủ các weights 300, 400, 500, 600, 700, 800 từ Google Fonts.
- Font tiêu đề (`--font-heading`): `'Plus Jakarta Sans', 'Be Vietnam Pro', system-ui, sans-serif`.
- Font thân trang (`--font-sans`): `'Be Vietnam Pro', 'Plus Jakarta Sans', system-ui, sans-serif`.
- Cỡ chữ gốc: `16px` (`--font-size`), heading dùng `font-weight: 700/800` với `letter-spacing: -0.015em`.
- **Hiệu ứng chuyển cảnh mượt mà**:
  - Chuyển trang (`page-fade`): router view chuyển trang trượt ngang trái/phải mượt mà 28px (`translateX`) kèm mờ dần (`mode="out-in"`).
  - Giỏ hàng (`CartDrawer`): hiệu ứng trượt slide-over mượt mà từ mép phải (`translateX(100%)` → `0`) kèm nền mờ backdrop fade.
  - Đổi danh mục đồ uống (`grid-fade`): lưới món fade out/in và các thẻ món lướt vào dạng bậc thang (`staggered cascading wave`) với độ trễ 30ms mỗi thẻ.
  - Điều hướng tab & menu sidebar: animation trượt từ cạnh trái/phải (`drawer-slide`) với nền mờ backdrop.
  - Hover & active tactile: nút và thẻ có hiệu ứng co/giãn phản hồi xúc giác (`active:scale-95`).

### 2.3 Bo góc & bóng đổ (pattern lặp lại toàn bộ template)

- Card/panel: `rounded-2xl` (16px) + `shadow-sm` + `border border-gray-100`.
- Nút bo tròn nhỏ (icon button, stepper +/-): `rounded-full`.
- Input/select/textarea: `rounded-xl` (12px), `border-gray-200`, focus: `ring-2 ring-orange-200`.
- Nút hành động chính: nền `bg-orange-500` (hover `bg-orange-600`), chữ trắng, bo `rounded-xl`, có thể thêm `shadow-md shadow-orange-200` cho CTA quan trọng nhất (Place Order).

---

## 3. Catalog các component pattern (trích từ `App.tsx`)

> Các màn hình sản phẩm không hiển thị mã use case `UC-0x` trong tiêu đề, mô tả hoặc trạng thái rỗng. Mã UC chỉ dùng trong tài liệu phân tích. Sidebar trái của `AppNavbar` là điều hướng chức năng chung; `CategorySidebar` riêng trên trang menu vẫn dùng để lọc danh mục đồ uống.

| Component | Vai trò | Đặc điểm chính cần giữ lại |
|---|---|---|
| `AppLogo` | Logo thương hiệu vector SVG | Logo hình ly đồ uống cách điệu (sóng sữa/trà, trân châu boba, đá lạnh, ống hút, lá trà tươi và ngôi sao AI thông minh), phối dải màu caramel/amber. Dùng chung cho header, favicon thẻ trình duyệt (`favicon.svg`) và trang xác thực. |
| `AppNavbar` | Header sticky glassmorphism và sidebar điều hướng | Header bán trong suốt (`backdrop-blur-md`), nút mở sidebar chức năng, logo SVG, link nhanh trên desktop (Menu/Sở thích/Lịch sử/Quản trị), chuông thông báo, user chip và nút giỏ hàng kèm số lượng món. Sidebar chứa link theo role, tự đóng khi chọn mục hoặc bấm Escape. |
| `WelcomeBanner` | Banner giới thiệu & chào mừng | Nền gradient caramel đậm đà, hiệu ứng ánh sáng mờ, lời chào cá nhân hóa, huy hiệu tính năng (pha chế 5–10p, 100% nguyên liệu chọn lọc) và đồ họa ly đồ uống vector cách điệu. |
| `RecommendationBanner` | Khối gợi ý AI cá nhân hóa | Thẻ thời gian và thời tiết theo vị trí với kính mờ (glassmorphism); chọn ngữ cảnh (học tập, thư giãn...); nút gọi gợi ý top 5; hiển thị câu lý do tổng quát và lưới 5 món có thứ hạng (#1, #2, #3...) kèm lý do riêng và nút thêm nhanh vào giỏ. |
| `CategorySidebar` | Danh mục món uống | Lọc theo danh mục (Tất cả, Trà sữa, Cà phê, Nước ép...); desktop là cột cố định có header, mobile/tablet là thanh cuộn ngang mượt mà. |
| `DrinkCard` | Thẻ món ăn dạng lưới (tối thiểu 3–4 cột trên desktop) | Ảnh món phóng to mượt khi rê chuột; huy hiệu "Bán chạy" (flame), huy hiệu nhiệt độ (Nóng/Đá/Nóng&Đá); tên món, mô tả 2 dòng, calo; giá nổi bật và nút "+ Chọn món" tactile. |
| `CartDrawer` | Giỏ hàng dạng slide-over drawer (trượt từ phải) | Thay thế panel giỏ hàng cố định chiếm chỗ; mở từ nút giỏ hàng trên navbar hoặc thanh giỏ hàng nổi phía dưới. Hiển thị danh sách món (ảnh, tên, stepper tăng giảm, nút xóa), tổng tiền, ghi chú tùy chỉnh và nút thanh toán lớn. |
| `FloatingCartPill` | Thanh giỏ hàng nổi dưới chân trang | Xuất hiện tự động khi giỏ có món (`totalQuantity > 0`) trên trang menu, cho phép mở nhanh `CartDrawer` mà không che khuất danh sách sản phẩm. |
| `DrinkDetailModal` | Thẻ modal xem chi tiết món & lý do gợi ý AI | Khi bấm vào bất kỳ món gợi ý hoặc món trong menu: hiển thị trọn vẹn 100% tên món, mô tả, ảnh lớn, huy hiệu nhiệt độ, calo; khối nổi bật "Lý do AI gợi ý món này cho bạn" (không bị cắt chữ); bộ chọn mức đường (0–100%), mức đá, ghi chú riêng và bộ đếm số lượng kèm nút thêm vào giỏ. |
| `ProgressSteps` | Thanh 3 bước Cart → Checkout → Complete | Dùng trên trang Checkout/Confirmation; bước đã qua/hiện tại tô màu primary, bước sau màu xám; có đường nối giữa các bước. |
| `SectionHeader` | Tiêu đề đánh số cho từng khối form trong Checkout | Số thứ tự trong hình tròn primary + tiêu đề in đậm font Poppins. |
| `FormField` | Input có label nhỏ phía trên | Style input dùng chung toàn app (border-border, rounded-xl, focus ring primary). |
| `RadioCard` | Nhóm lựa chọn dạng thẻ bo viền | Dùng cho chọn hình thức nhận đồ (Dùng tại quán / Mang đi) hoặc phương thức thanh toán giả lập. |
| `ConfirmationPage` | Trang xác nhận đặt hàng thành công | Icon check tròn lớn, tiêu đề, mã đơn hàng, tổng tiền, nút quay về Menu hoặc xem Lịch sử. |

---

## 4. Page layout templates

### 4.1 Trang Menu (Home)

```
AppNavbar (drawer toggle | SVG logo | nav desktop links | thông báo / auth | giỏ hàng)
WelcomeBanner (hero banner với thông điệp & minh họa đồ uống vector)
RecommendationBanner (AI Gợi ý cá nhân hóa + thời tiết & thời gian thời gian thực)
┌────────────────┬─────────────────────────────────────────────────────────┐
│ CategorySidebar│ Toolbar: Tiêu đề danh mục, số lượng món & ô tìm kiếm    │
│ (danh mục món) ├─────────────────────────────────────────────────────────┤
│                │ Grid DrinkCard: Tối thiểu 3–4 món một hàng trên desktop │
│                │ (md:grid-cols-3, xl:grid-cols-4)                        │
└────────────────┴─────────────────────────────────────────────────────────┘
[Floating Cart Pill khi có món trong giỏ] ──> mở CartDrawer (slide-over trượt từ phải)
```

### 4.2 Trang Giỏ hàng / Checkout

```
AppNavbar dùng chung + hàng ProgressSteps riêng bên dưới
┌───────────────────────────────────────┬───────────────────────┐
│ 1. Món đã chọn (SectionHeader)        │  Order Summary panel  │
│ 2. Thông tin khách hàng               │  - danh sách món       │
│ 3. Hình thức nhận đồ (dine-in/mang đi)│  - subtotal/thuế/tổng  │
│ 4. Ghi chú thêm                       │  - nút "Đặt hàng"      │
│ 5. Phương thức thanh toán (giả lập)   │                        │
│ Link "Quay lại menu"                  │                        │
└───────────────────────────────────────┴───────────────────────┘
```

### 4.3 Trang xác nhận đơn hàng

```
AppNavbar dùng chung + ProgressSteps bước 3
Card giữa màn hình: icon ✅ lớn, "Đặt hàng thành công!",
mô tả ngắn, mã đơn hàng, nút "Về trang Menu"
```

---

## 5. Bảng ánh xạ: Template → Use case dự án → View Vue

| Trang/Component mẫu | Use case liên quan | View Vue hiện có | Trạng thái & Tính năng |
|---|---|---|---|
| Navbar, HeroBanner, CategorySidebar, DrinkCard, CartDrawer | UC-03 (menu), UC-04 (gợi ý), UC-05 (đặt hàng) | ✅ `HomeView.vue` dùng `AppNavbar`, `WelcomeBanner`, `RecommendationBanner`, `CategorySidebar`, `DrinkCard`, `CartDrawer` | Lưới sản phẩm tối thiểu 3–4 món/hàng trên desktop; ô tìm kiếm món; giỏ hàng slide-over drawer thay thế panel cố định; thanh giỏ hàng nổi phía dưới. |
| ProgressSteps, SectionHeader, FormField, radio-card, Order Summary | UC-05 (đặt đồ uống, thanh toán giả lập) | ✅ `CheckoutView.vue` (route `/checkout`) | Tùy chỉnh mức đường, mức đá, ghi chú cho từng món; chọn hình thức nhận đồ (tại quán/mang đi); đặt hàng bằng tài khoản hoặc tên khách vãng lai. |
| ConfirmationPage | Kết thúc UC-05 | ✅ Trạng thái `step === 'confirmed'` ngay trong `CheckoutView.vue` | Hiển thị mã đơn, tổng tiền, điều hướng về trang chủ hoặc lịch sử đơn hàng. |
| — (không có mẫu tương ứng) | UC-01 Đăng ký/đăng nhập | `LoginView.vue`, `RegisterView.vue` | Thiết kế giao diện hiện đại với nền ambient glow, card bo góc, SVG logo và thông báo lỗi trực quan. |
| — | UC-02 Sở thích | `PreferenceView.vue` | Dùng lại pattern `FormField` + radio-card (mức đường/đá) + `SectionHeader`. |
| Danh sách item dạng card + rating | UC-06 (lịch sử đơn), UC-07 (đánh giá) | `OrderHistoryView.vue` | Dùng pattern card đơn hàng tương tự Order Summary; thêm sao đánh giá (đã có), style lại theo token màu. |
| — (khác domain, cần thiết kế riêng) | UC-08/09/10/11 (Admin) | `AdminMenuView.vue`, `AdminOrdersView.vue`, `AdminReportsView.vue`, `AdminUsersView.vue` | Template không có trang admin — tự thiết kế bảng dữ liệu (table), filter, form dùng chung token màu/border/radius cho nhất quán; có thể tham khảo thêm `components/ui/table.tsx`, `card.tsx` trong `template_frontend` để lấy ý tưởng bố cục bảng/thẻ. |

---

## 6. Kế hoạch triển khai lập trình UI theo trang (trước khi thiết kế UI)

> Thứ tự đề xuất, làm từng bước, mỗi bước xong mới sang bước sau. Tất cả các bước dùng chung bảng màu/token ở mục 2.

- [x] **Bước 0 — Thiết lập nền tảng thiết kế dùng chung** ✅ Đã hoàn thành
  - **Quyết định đã chốt** (xác nhận với người dùng trước khi code):
    - Cài **Tailwind CSS v4** vào `frontend/` qua plugin `@tailwindcss/vite` (thay vì viết CSS thuần) để tái sử dụng trực tiếp class từ template.
    - Đổi màu chủ đạo từ cam sang **nâu trà/caramel `#b45309`** cho phù hợp quán trà sữa/cà phê (xem bảng màu đã cập nhật ở mục 2.1).
    - Giữ tên thương hiệu **"Smart Drink"**, logo dùng icon 🧋.
  - **Đã cài đặt** (trong container `drink-app-frontend`): `tailwindcss`, `@tailwindcss/vite` (devDependencies), `lucide-vue-next` (icon — chưa dùng ở Bước 1, sẵn sàng cho các bước sau).
  - **Đã cấu hình**: `frontend/vite.config.js` (thêm plugin `tailwindcss()`); `frontend/src/assets/fonts.css` (import Google Fonts Inter + Poppins); `frontend/src/assets/theme.css` (CSS variables theo mục 2.1 + khối `@theme inline` map sang Tailwind); `frontend/src/assets/main.css` (import theo đúng thứ tự `fonts.css` → `tailwindcss` → `theme.css` — thứ tự này bắt buộc để tránh lỗi PostCSS "@import statements must precede all other statements"); đã xoá `frontend/src/assets/base.css` (theme mặc định cũ của Vue CLI, không còn dùng).
  - **Component dùng chung đã tạo** tại `frontend/src/components/ui/`: `AppLogo.vue`, `BaseCard.vue`, `FormField.vue`, `SectionHeader.vue`. Có tạo thêm `BaseButton.vue` (ngoài danh sách gốc) để chuẩn hoá nút bấm (variant `primary`/`secondary`/`ghost`) dùng chung cho Login/Register và các trang sau.

- [x] **Bước 1 — Trang Đăng nhập/Đăng ký (UC-01)** ✅ Đã hoàn thành
  - **Quyết định đã chốt**: làm cả 2 trang (Đăng nhập thiết kế lại + Đăng ký mới, đúng đầy đủ UC-01); **bỏ** nút "Điền nhanh tài khoản demo" (chỉ phục vụ dev-test, không đưa vào UI sản phẩm).
  - Thiết kế lại `frontend/src/views/LoginView.vue`: card trắng giữa màn hình (`BaseCard`), `AppLogo` phía trên, input theo `FormField`, nút submit theo `BaseButton`, link sang trang Đăng ký.
  - Tạo mới `frontend/src/views/RegisterView.vue`: form Họ tên/Email/Mật khẩu/Xác nhận mật khẩu, hiển thị lỗi validate theo từng field (khớp response 422 của `RegisterRequest` backend: `name`, `email`, `password`), link quay lại Đăng nhập.
  - Thêm route `/register` (`meta: { guest: true }`) vào `frontend/src/router/index.js`.
  - Thêm hàm `register()` vào `frontend/src/stores/auth.js` (gọi `authApi.register`, lưu token/user giống `login()`).
  - Đã kiểm tra: dev server Vite biên dịch không lỗi, không có lỗi linter, tất cả module (view/component/store/router) load HTTP 200 qua dev server.

- [x] **Bước 2 — Trang chủ / Menu (UC-03, UC-04)** ✅ Đã hoàn thành
  - Dựng lại `frontend/src/views/HomeView.vue` theo layout 4.1: `AppNavbar` (dùng lại từ Bước 0/1) + `WelcomeBanner` (hero rút gọn, không dùng ảnh nền thật để tránh phụ thuộc mạng ngoài — dùng gradient `primary`→`primary-hover` + emoji 🧋) + `CategorySidebar` + lưới `DrinkCard` + `CartSidebar` cố định bên phải (`lg:sticky`).
  - `RecommendationBanner` tải public `GET /api/recommendation-context` để hiện đồng hồ, thời tiết và một câu gợi ý chung ngắn theo giờ/nhiệt độ; không tự gọi LLM khi mở trang. Vị trí trình duyệt được lấy mới mỗi lần yêu cầu. Nút “Nhận gợi ý của bạn” mới gọi `GET /api/recommendations`, hiển thị một câu tóm tắt riêng do OpenAI sinh cùng top 5 và lý do từng món; khi OpenAI lỗi thì hiện câu tóm tắt dự phòng từ backend. Badge cá nhân hóa/dự phòng nằm cạnh câu tóm tắt top 5, không nằm cạnh câu gợi ý chung. Guest nhận popup đăng nhập; customer chưa có preference nhận popup chọn khai báo trước hoặc dùng fallback.
  - **Component mới tạo**:
    - `frontend/src/components/layout/AppNavbar.vue` — header rộng tối đa 1800px, nút mở sidebar trái trên desktop/mobile, logo, chuông thông báo và icon giỏ hàng. Sidebar dùng chung cho Menu/Sở thích/Lịch sử/3 mục Admin, tự highlight theo route; guest có Đăng nhập/Đăng ký. Logo thu gọn thành biểu tượng dưới 360px để header không tràn. CSS link mặc định nằm trong `@layer base` để các utility màu chữ, đặc biệt nút đăng ký nền caramel/chữ trắng, được ưu tiên. `NotificationCenter.vue` mở panel từ header, poll 10 giây và hiển thị toast trượt vào ở góc dưới khi có thông báo mới.
    - `frontend/src/components/menu/CategorySidebar.vue` — danh sách danh mục (v-model), danh mục lấy động từ dữ liệu `drinks` trả về (không hardcode), kèm emoji theo danh mục.
    - `frontend/src/components/menu/DrinkCard.vue` — thẻ món cỡ lớn theo lưới responsive 2–3 cột: khung ảnh cao 176–208px và dùng `object-contain` để hiển thị trọn sản phẩm, không crop/zoom; nếu không có `image_url` thì hiển thị emoji theo danh mục; badge "Phổ biến" nếu `tags` chứa `best_seller`; nút thêm vào giỏ.
    - `frontend/src/components/menu/CartSidebar.vue` — panel giỏ hàng đầy đủ chức năng cũ (tăng/giảm số lượng, xoá, chọn đường/đá theo `SUGAR_OPTIONS`/`ICE_OPTIONS`, ghi chú, trường "dịp", tổng tiền, nút đặt hàng, thông báo lỗi/thành công) — chuyển từ code inline trong `HomeView.vue` cũ sang component riêng, `HomeView.vue` chỉ giữ state + logic gọi API. *(Đã đơn giản hoá thêm ở Bước 3, xem bên dưới.)*
    - `frontend/src/components/menu/WelcomeBanner.vue` — hero hỗ trợ cả tên user và lời chào guest; `RecommendationBanner.vue` — context public + recommendation theo yêu cầu + popup xác thực/sở thích.
  - Thêm helper `getCategoryEmoji()` vào `frontend/src/constants/drinkOptions.js` để map danh mục (trà sữa/trà trái cây/cà phê/nước ép...) sang emoji dùng chung cho `CategorySidebar`, `DrinkCard`, `CartSidebar`.
  - Khung nội dung menu mở rộng tối đa 1800px để tận dụng khoảng trống hai bên; màn rộng dùng lưới 3 cột và phân trang 12 món/trang (4 hàng), màn nhỏ hơn tự hạ còn 2/1 cột. Đổi danh mục tự về trang 1 và chuyển trang sẽ cuộn về đầu lưới.
  - Thẻ món nâng nhẹ lên khi rê chuột (`translateY(-0.25rem)`), tăng bóng và đổi màu viền; hiệu ứng tắt dịch chuyển khi người dùng bật chế độ giảm chuyển động.
  - Giữ nguyên toàn bộ logic nghiệp vụ đã có (thêm giỏ, đổi số lượng, chọn đường/đá, đặt hàng qua `ordersApi.create`) — chỉ tách UI ra component và áp token màu/spacing mới, không đổi hành vi.
  - Đã kiểm tra: dev server Vite biên dịch không lỗi, không có lỗi linter, tất cả view/component mới trả HTTP 200 qua dev server, API `GET /api/drinks` vẫn trả dữ liệu bình thường cho trang menu.

- [x] **Bước 3 — Giỏ hàng & Checkout (UC-05)** ✅ Đã hoàn thành (gộp luôn Bước 4)
  - **Kiến trúc**: state giỏ hàng nằm trong **Pinia store `frontend/src/stores/cart.js`** (`useCartStore`: `items`, `occasion`, `totalQuantity`/`totalPrice`, action `addItem/increase/decrease/remove/clear`) để chia sẻ giữa trang Menu và trang Checkout qua điều hướng route.
  - **Trang Menu (`HomeView.vue`)**: `CartSidebar.vue` chỉ hiển thị **danh sách món rút gọn** (emoji, tên, thành tiền, tăng/giảm số lượng, xoá) + dòng gợi ý "chọn đường/đá và ghi chú ở bước Giỏ hàng & Thanh toán tiếp theo". Nút "Tiến hành đặt hàng" chỉ emit `checkout` để điều hướng sang `/checkout` (không đặt hàng trực tiếp tại đây).
  - **Trang `frontend/src/views/CheckoutView.vue`** (route public `/checkout`, yêu cầu giỏ không rỗng) — gồm 2 trạng thái nội bộ (`step: 'form' | 'confirmed'`):
    - Trạng thái `form`: `AppNavbar` + hàng `ProgressSteps` riêng (Giỏ hàng → Thanh toán → Hoàn tất), 5 khối `SectionHeader` đánh số:
      1. **Món đã chọn** — mỗi món: ảnh/emoji, tên, thành tiền, tăng/giảm số lượng, xoá, chọn đường/đá (`SUGAR_OPTIONS`/`ICE_OPTIONS`), ô ghi chú riêng — thao tác trực tiếp trên `cart.items` qua `useCartStore`.
      2. **Thông tin khách hàng** — customer hiển thị tên/email; guest nhập tên 2–100 ký tự và được thông báo đơn không có lịch sử/rating.
      3. **Hình thức nhận đồ** — `RadioCard` chọn Dùng tại quán/Mang đi (`ORDER_TYPE_OPTIONS` trong `constants/drinkOptions.js`); gửi riêng qua trường `order_type` (`dine_in`/`takeaway`, enum `App\Enums\OrderType`) lên `POST /api/orders`, lưu vào khoá `context_snapshot.order_type` (không cần migration vì cột `context_snapshot` là JSON).
      4. **Ghi chú thêm** — `FormField` nhập dịp/ghi chú tự do, gửi qua trường `occasion` (chỉ còn đúng nghĩa ghi chú, không gộp chung với hình thức nhận đồ nữa).
      5. **Phương thức thanh toán (giả lập)** — `RadioCard` Tiền mặt/QR, chỉ hiển thị minh hoạ, không gửi backend (chưa có cột `payment_method`).
      Panel "Đơn hàng của bạn" bên phải (sticky khi cuộn, xem phần Layout & sticky bên dưới) có nút "Xác nhận đặt hàng" gọi `ordersApi.create`.
    - Trạng thái `confirmed`: khối xác nhận theo mẫu `ConfirmationPage`; nút lịch sử chỉ hiện với tài khoản đăng nhập.
    - Guard: tự động điều hướng về trang chủ nếu vào `/checkout` khi giỏ hàng rỗng, hoặc nếu người dùng xoá hết món ngay tại trang này (qua `watch` trên `cart.items.length`).
  - **Component dùng chung** tại `frontend/src/components/ui/`: `ProgressSteps.vue`, `RadioCard.vue`.
  - **Layout & sticky cart khi cuộn trang**: cả `CartSidebar.vue` (trang Menu) và panel "Đơn hàng của bạn" (`CheckoutView.vue`) dùng `position: sticky` để card trôi theo khi cuộn. **Lưu ý kỹ thuật quan trọng**: container flex cha (bọc main content + sidebar) **không được đặt `align-items: flex-start`/`self-start`** trên `<aside>` chứa phần tử sticky — nếu đặt, `<aside>` sẽ chỉ cao bằng đúng nội dung của nó, không có "khoảng trống" để phần tử sticky bên trong trôi theo khi cuộn (containing block của sticky chính là `<aside>`, sticky chỉ trôi được trong phạm vi chiều cao của containing block). Cách đúng: để flex container dùng `align-items: stretch` mặc định (không set align-items), giúp `<aside>` giãn theo chiều cao cột nội dung chính; phần tử sticky thật sự (div bên trong `<aside>`) vẫn giữ kích thước tự nhiên và có đủ khoảng trống để trôi. Áp dụng: trang Menu kích hoạt sticky từ `lg:` (≥1024px, do có 3 cột: danh mục + lưới món + giỏ hàng cần nhiều chỗ), trang Checkout kích hoạt từ `md:` (≥768px, chỉ 2 cột nên đủ chỗ ở màn hình nhỏ hơn).
  - **Hiển thị hình thức nhận đồ cho admin/khách**: badge (🏠 Dùng tại quán / 🥤 Mang đi) từ `context_snapshot.order_type` qua helper `getOrderTypeInfo()` (`constants/drinkOptions.js`), hiển thị ở `AdminOrdersView.vue` (UC-09, cạnh badge trạng thái đơn, kèm ghi chú `occasion` nếu có) và `OrderHistoryView.vue` (lịch sử đơn của khách).
  - Route `home` và `checkout` là public; các route preference/history/admin vẫn giữ guard. Checkout tự quay về home khi giỏ rỗng.
  - Test: `OrderTest.php` có test riêng cho `order_type` (lưu đúng vào `context_snapshot`, validate enum từ chối giá trị lạ) — full suite `OrderTest`/`AdminOrderTest` 17/17 pass. Đã build thử frontend + grep CSS biên dịch để xác nhận các class `sticky`/`top-24` ở đúng breakpoint mong muốn, không bị purge.

> Ghi chú đánh số: Bước 4 gốc ("Xác nhận đơn hàng") đã được gộp vào Bước 3 ở trên (làm luôn Confirmation cùng lúc với Checkout). Các bước còn lại được đánh số lại liền mạch từ đây.

- [x] **Bước 4 — Lịch sử đơn hàng & Đánh giá (UC-06, UC-07)** ✅ Đã hoàn thành
  - Viết lại `frontend/src/views/OrderHistoryView.vue` từ CSS thuần (scoped style thủ công) sang Tailwind + bộ component dùng chung: `AppNavbar` (đồng bộ điều hướng với `HomeView`, có link Menu/Sở thích/Lịch sử đơn hàng + link Admin nếu là admin), `BaseCard` cho từng đơn hàng, `BaseButton` cho nút "Đặt đồ ngay"/"Gửi đánh giá"/"Huỷ đơn". **Giữ nguyên 100% logic nghiệp vụ cũ** (`loadOrders`, `cancelOrder`, `ratingForm`/`setStars`/`submitRating`) — chỉ thay lớp UI.
  - Mỗi đơn hàng hiển thị: mã đơn + hình thức nhận đồ, thời gian và badge trạng thái; đơn `done` cho gửi đánh giá một lần. Sau khi gửi, sao/nhận xét/nút gửi bị khóa và UI ghi rõ đánh giá không thể chỉnh sửa; chân đơn có tổng tiền và nút hủy khi còn `pending`.
  - Trạng thái rỗng (chưa có đơn nào) dùng icon `PackageOpen` + nút "Đặt đồ ngay" điều hướng về Menu, đồng bộ phong cách với trạng thái rỗng của `CartSidebar.vue`.
  - **Mở rộng component dùng chung**: thêm variant `danger` và prop `fullWidth` (mặc định `true`, giữ nguyên hành vi cũ) vào `BaseButton.vue` — cần thiết cho nút "Huỷ đơn" (màu đỏ, không chiếm full width vì nằm cạnh dòng tổng tiền); các bước Admin sau (6/7) có thể tái dùng.
  - **Backend**: thêm field `drink_category` vào `OrderItemResource.php` (lấy từ quan hệ `drink` đã `whenLoaded`) để frontend hiển thị đúng emoji theo danh mục món trong lịch sử đơn — không đổi hành vi API cũ, chỉ thêm field mới.
  - Đã chạy `OrderTest`/`AdminOrderTest`/`RatingTest` (25/25 pass) sau khi đổi `OrderItemResource`; build thử frontend không lỗi, không có lỗi linter, route `/orders` trả HTTP 200 qua dev server.

- [x] **Bước 5 — Trang Sở thích cá nhân (UC-02)** ✅ Đã hoàn thành
  - Viết lại `frontend/src/views/PreferenceView.vue` từ CSS thuần sang Tailwind + bộ component dùng chung: `AppNavbar` (đồng bộ điều hướng, highlight "Sở thích của tôi"), `BaseCard` + `SectionHeader` chia 3 khối đánh số (1. Sở thích vị giác, 2. Mức đường & đá mặc định, 3. Ghi chú dị ứng), `BaseButton` cho nút lưu. **Giữ nguyên logic gọi API cũ** (`preferencesApi.show/update`), chỉ đổi cách người dùng nhập liệu (xem bên dưới) và lớp UI.
  - **Sở thích vị giác đổi từ input text tự do (phân tách bằng dấu phẩy) sang chip chọn nhanh** (tham khảo pattern `Badge` trong `template_frontend`): danh sách preset `TASTE_TAG_PRESETS` (constants/drinkOptions.js — đồng bộ với vocabulary tag của danh mục 50 món: `sweet`, `less_sweet`, `coffee`, `no_milk`, `no_added_sugar`, `fruit_tea`, `refreshing`, `fresh`, `matcha`, `chocolate`, `milky`, `oat_milk`), bấm để bật/tắt; vẫn cho phép thêm tag tuỳ ý ngoài preset qua ô nhập + nút "Thêm" (hiển thị dạng chip có nút xoá `X`). Dữ liệu gửi lên backend vẫn là mảng string y hệt format cũ (`taste_tags`), không đổi API.
  - **Mức đường/đá mặc định đổi từ `<select>` sang `RadioCard`** (component đã có từ Bước 3), hiển thị dạng lưới thẻ bo viền thay vì dropdown.
  - **Ghi chú dị ứng**: mở rộng `FormField.vue` thêm prop `multiline`/`rows` để render `<textarea>` thay vì `<input>` khi cần (dùng chung được cho các trường nhiều dòng khác sau này), không ảnh hưởng các chỗ đang dùng `FormField` dạng input thường.
  - Khối "Hồ sơ tổng hợp dùng để gợi ý" (`profile_text` trả về từ backend) giữ nguyên vị trí cuối trang, style lại thành card nền `muted` viền nét đứt + icon `Sparkles`, làm rõ đây là dữ liệu suy ra tự động (không phải form nhập).
  - Đã chạy `PreferenceTest` (7/7 pass, không đổi backend); build frontend không lỗi, không lint error, route `/preferences` trả HTTP 200 qua dev server.

- [x] **Bước 6 — Admin: Quản lý menu (UC-08)** ✅ Đã hoàn thành
  - Viết lại `frontend/src/views/AdminMenuView.vue` từ CSS thuần sang Tailwind + bộ component dùng chung: `AppNavbar` (đồng bộ điều hướng, highlight "Quản lý menu", đủ link Menu/Sở thích/Lịch sử/3 mục Admin), `BaseCard` cho panel danh sách + panel form, `SectionHeader` cho tiêu đề form, `FormField` cho các trường nhập liệu, `RadioCard` cho chọn nhiệt độ phục vụ (`TEMPERATURE_OPTIONS`, có icon 🔥/🧊/🔥🧊), `BaseButton` cho toàn bộ nút bấm (variant `secondary`/`ghost`/`danger`). **Giữ nguyên 100% logic nghiệp vụ cũ** (`loadDrinks`, `editDrink`, `onSubmit`, `toggleAvailability`, `deleteDrink`) và toàn bộ payload/validate khớp `StoreDrinkRequest`/`UpdateDrinkRequest` backend — chỉ thay lớp UI.
  - Giao diện thực tế không hiển thị mã “UC-08 · Admin”. Form cho chọn ảnh từ máy, xem trước, kiểm tra JPG/PNG/WebP tối đa 5 MB và tải qua endpoint riêng sau khi lưu món.
  - Layout 2 cột `grid lg:grid-cols-[1.3fr_1fr]`: panel trái là danh sách món dạng thẻ (emoji theo category qua `getCategoryEmoji()`, tên, danh mục/giá/nhiệt độ, tags, badge Đang bán/Ngừng bán màu `success`/`destructive`), có ô tìm kiếm lọc theo danh mục (icon `Search`) + nút Lọc; mỗi thẻ có 3 nút thao tác Sửa/Ngừng bán/Xoá. Panel phải là form thêm/sửa món, đổi tiêu đề động theo trạng thái đang sửa, có nút "Huỷ sửa" khi đang ở chế độ sửa.
  - Thẻ món đang được chọn để sửa được highlight bằng viền `border-primary` + nền `bg-secondary/40`, đồng bộ pattern highlight active dùng ở `CategorySidebar.vue`.
  - Đã kiểm tra: build frontend production không lỗi, không có lỗi linter, route `/admin/menu` trả HTTP 200 qua dev server.

- [x] **Bước 7 — Admin: Quản lý đơn hàng (UC-09)** ✅ Đã hoàn thành
  - Viết lại `frontend/src/views/AdminOrdersView.vue` từ CSS thuần sang Tailwind + bộ component dùng chung: `AppNavbar` (highlight "Quản lý đơn hàng"), `BaseCard` cho toolbar lọc + từng đơn hàng, `BaseButton` cho toàn bộ nút thao tác. **Giữ nguyên 100% logic nghiệp vụ cũ** (`loadOrders`, `changeStatus`, phân trang) — chỉ thay lớp UI.
  - Toolbar lọc trạng thái (select) + tổng số đơn nằm trong 1 `BaseCard` gọn phía trên danh sách. Mỗi đơn hàng là 1 `BaseCard`: header có mã đơn + tên/email khách + thời gian, góc phải là badge hình thức nhận đồ (`getOrderTypeInfo()`) xếp trên badge trạng thái màu theo `ORDER_STATUS_BADGE_CLASSES` (pending=vàng nhạt, confirmed=xanh dương, done=xanh lá, cancelled=đỏ); ghi chú `occasion` (nếu có) hiển thị dạng khối nét đứt; danh sách món có emoji theo category; chân đơn có tổng tiền + nút hành động tiếp theo (Xác nhận/Hoàn tất dùng `BaseButton` variant `success` mới thêm, Huỷ đơn dùng variant `danger`).
  - **Mở rộng component dùng chung**: thêm variant `success` (nền `success-bg`, chữ `success`) vào `BaseButton.vue` — dùng cho các hành động "tiến trình thuận" (Xác nhận, Hoàn tất), tách biệt với `primary` (thương hiệu) và `danger` (huỷ).
  - Phân trang (Trước/Sau) dùng `BaseButton` variant `ghost` có viền.
  - Đã kiểm tra: build frontend production không lỗi, không có lỗi linter, route `/admin/orders` trả HTTP 200 qua dev server, full test suite backend (50/50) vẫn pass (không đổi backend).

- [x] **Bước 8 — Admin: Báo cáo (UC-10)** ✅ Đã hoàn thành
  - `AdminReportsView.vue` dùng Tailwind + bộ component dùng chung: `AppNavbar` (highlight "Báo cáo"), `BaseCard` cho toolbar lọc ngày + 3 thẻ báo cáo, `SectionHeader` cho tiêu đề, `FormField` (`type="date"`) cho khoảng ngày, `BaseButton` cho nút "Áp dụng". Khoảng ngày mặc định là đầu tháng đến hôm nay; nếu hôm nay là ngày đầu tháng thì bắt đầu từ đầu tháng trước.
  - Thẻ "Tổng quan doanh thu" cộng doanh thu của mọi đơn `done` trong khoảng ngày, độc lập với giới hạn top món; hai phần còn lại hiển thị món bán chạy và hiệu quả gợi ý.
  - Panel "Món bán chạy nhất": bảng Tailwind (`border-border`, hover row `bg-secondary/30`), cột thứ hạng đổi thành huy chương 🥇🥈🥉 cho top 3, tên món kèm emoji theo category (`getCategoryEmoji()`), doanh thu tô màu `primary` in đậm.
  - Panel "Hiệu quả gợi ý": 3 khối số liệu lớn dạng card (nền `secondary/40`, icon `Sparkles`/`TrendingUp`/`Target` từ `lucide-vue-next`, số lớn `text-3xl font-heading`) cho Lượt gợi ý/Lượt dẫn đến mua hàng/Tỉ lệ chuyển đổi; danh sách phân bố theo vị trí gợi ý hiển thị dạng list nền `muted`.
  - Đã kiểm tra: build frontend production không lỗi, không có lỗi linter, route `/admin/reports` trả HTTP 200 qua dev server, full test suite backend (50/50) vẫn pass (không đổi backend).

- [x] **Bước 9 — Admin: Quản lý tài khoản (UC-11)** ✅ Đã hoàn thành
  - `AdminUsersView.vue` hiển thị danh sách tài khoản có phân trang, tìm kiếm theo tên/email, lọc vai trò và trạng thái; admin sửa tên/email/vai trò và kích hoạt hoặc vô hiệu hóa tài khoản.
  - Thu hồi token khi vô hiệu hóa; chặn đăng nhập và request đã xác thực từ tài khoản bị tắt. Không xóa cứng để giữ lịch sử giao dịch; không cho tự hạ quyền/vô hiệu hóa chính mình hoặc vô hiệu hóa admin đang hoạt động cuối cùng.
  - Tái dùng `AppNavbar`, `BaseCard`, `SectionHeader`, `FormField`, `BaseButton`; bảng có cuộn ngang trên màn hình nhỏ.

> Toàn bộ 10 trang trong kế hoạch (Bước 0-9) đã hoàn thành việc áp dụng thiết kế Tailwind + bộ component dùng chung đồng bộ token màu/spacing/typography theo mục 2.

---

### 6.1 Tối ưu trải nghiệm tải menu và đồng bộ ảnh giỏ hàng

- `stores/menu.js` lưu danh sách món vào Pinia và `localStorage` trong 5 phút theo mô hình stale-while-revalidate: dữ liệu đã có được hiển thị ngay, còn request cập nhật chạy nền và được gộp nếu nhiều view cùng gọi.
- `LoginView.vue` và `RegisterView.vue` tải trước menu công khai. Khi xác thực xong, `HomeView.vue` thường có thể hiển thị món ngay; lần truy cập đầu chưa có cache dùng skeleton thay vì màn hình trống.
- Các thao tác thêm/sửa/ẩn/xoá món trong `AdminMenuView.vue` chủ động làm mới cache menu công khai để tránh hiển thị dữ liệu cũ.
- `DrinkThumbnail.vue` dùng chung `image_url` của thẻ món cho giỏ hàng tại Menu và Checkout; chỉ quay về emoji danh mục nếu món không có ảnh hoặc ảnh tải lỗi.
- Nginx production cache `/images/` trong 7 ngày; asset có hash vẫn cache bất biến 1 năm như trước.

## 7. Lưu ý khi chuyển đổi React → Vue

- `useState` → `ref()`/`reactive()`; `useEffect` (nếu có, template hiện không dùng) → `onMounted`/`watch`.
- `className` (JSX) → `class` (Vue template); chuỗi Tailwind class giữ nguyên được nếu Tailwind được cài cho Vue.
- Props/callback dạng `{ onAdd, onCheckout }` → `defineProps` + `emit` trong Vue SFC.
- Icon `lucide-react` (`<ShoppingCart size={22} />`) → `lucide-vue-next` (`<ShoppingCart :size="22" />`) hoặc SVG tương đương.
- Không copy nguyên văn code JSX từ `App.tsx`; dùng làm tài liệu tham chiếu bố cục/class để viết lại component Vue tương ứng.
