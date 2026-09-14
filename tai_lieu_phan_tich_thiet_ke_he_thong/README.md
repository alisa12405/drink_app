# Tài liệu phân tích và thiết kế hệ thống Smart Drink

## 1. Mục đích

Bộ tài liệu được xây dựng ở giai đoạn **trước khi lập trình**, làm cơ sở thống nhất phạm vi nghiệp vụ, mô hình dữ liệu, kiến trúc, giao diện và hợp đồng API cho hệ thống **Smart Drink — ứng dụng đặt đồ uống thông minh hỗ trợ gợi ý cá nhân hóa**.

Quy trình xây dựng tài liệu:

```text
Khảo sát đơn vị → Xác định vấn đề → Thu thập yêu cầu
→ Phân tích tác nhân/use case → Thiết kế kiến trúc, dữ liệu, API và giao diện
→ Đặc tả kiểm thử chấp nhận → Lập trình
```

Các tên bảng, enum, module và luồng nghiệp vụ được lựa chọn phù hợp với phạm vi dự án Smart Drink. Tài liệu không dùng kết quả triển khai để chứng minh thiết kế; thay vào đó, mỗi quyết định được giải thích bằng nhu cầu khảo sát, quy tắc nghiệp vụ và yêu cầu hệ thống.

## 2. Phạm vi nguồn tham khảo

- `SPEC_smart-drink-recommendation-app.md`: mục tiêu và yêu cầu nghiệp vụ.
- `database/*.md`, `database/tables/*.md`: định hướng thiết kế dữ liệu.
- `UI_DESIGN_TEMPLATE.md`: định hướng bố cục và design system.
- `README-DOCKER.md` cùng tài liệu backend/frontend: định hướng công nghệ và môi trường.
- `chuong_3.md`: cách tổ chức chương khảo sát, use case và các mô hình thiết kế.

Toàn bộ `do_an_mau.*` được loại khỏi nguồn nội dung. Phần khảo sát sử dụng cửa hàng cà phê nhỏ Mộc Nhiên Coffee làm tình huống nghiên cứu của bài tập lớn; hồ sơ đơn vị, nhân sự, phỏng vấn và dữ liệu Google Forms được xây dựng thống nhất với bài toán Smart Drink.

## 3. Phạm vi hệ thống được thiết kế

Hệ thống gồm hai phân hệ người dùng:

- Khách hàng: xác thực, khai báo sở thích, xem menu, nhận gợi ý, quản lý giỏ, đặt/hủy/xem đơn và đánh giá món.
- Quản trị viên: quản lý menu, xử lý trạng thái đơn và xem báo cáo.

Các dịch vụ hỗ trợ gồm Recommendation Engine, OpenAI API, Weather API, Redis cache/queue và queue worker. Thanh toán chỉ được mô phỏng; không thiết kế giao hàng, kho, khuyến mại hoặc cổng thanh toán thật trong phiên bản này.

## 4. Danh mục tài liệu

| File | Nội dung |
|---|---|
| [01-khao-sat-va-yeu-cau-he-thong.md](01-khao-sat-va-yeu-cau-he-thong.md) | Khảo sát Mộc Nhiên Coffee, phỏng vấn, Google Forms, đánh giá và đề xuất hệ thống. |
| [02-tac-nhan-va-ca-su-dung.md](02-tac-nhan-va-ca-su-dung.md) | Tác nhân, sơ đồ use case tổng quát và quan hệ giữa các chức năng. |
| [03-dac-ta-ca-su-dung.md](03-dac-ta-ca-su-dung.md) | Phân rã UC-01 đến UC-10 thành các UC con và đặc tả riêng. |
| [04-kien-truc-va-thiet-ke-thanh-phan.md](04-kien-truc-va-thiet-ke-thanh-phan.md) | Kiến trúc tổng thể, phân lớp, thành phần và luồng dữ liệu dự kiến. |
| [05-thiet-ke-hanh-vi-he-thong.md](05-thiet-ke-hanh-vi-he-thong.md) | Biểu đồ trình tự, hoạt động và trạng thái. |
| [06-thiet-ke-lop-va-trien-khai.md](06-thiet-ke-lop-va-trien-khai.md) | Biểu đồ lớp và mô hình triển khai Docker đề xuất. |
| [07-thiet-ke-co-so-du-lieu.md](07-thiet-ke-co-so-du-lieu.md) | ERD, từ điển dữ liệu vật lý, khóa và ràng buộc. |
| [08-thiet-ke-api-giao-dien-va-an-toan.md](08-thiet-ke-api-giao-dien-va-an-toan.md) | Hợp đồng REST API, thiết kế màn hình, an toàn, hiệu năng và truy vết yêu cầu. |
| [09-bieu-do-thanh-phan.md](09-bieu-do-thanh-phan.md) | Biểu đồ thành phần chi tiết, giao diện, phụ thuộc và ánh xạ UC. |

## 5. Quy ước

- “Hệ thống” trong tài liệu là hệ thống được đề xuất xây dựng.
- “Phải” biểu thị yêu cầu bắt buộc; “nên” biểu thị yêu cầu ưu tiên nhưng có thể điều chỉnh.
- Sơ đồ Mermaid là mô hình thiết kế logic, dùng làm đầu vào lập trình và kiểm thử.
- API và dữ liệu dùng `snake_case`; enum là hợp đồng chung giữa cơ sở dữ liệu, backend và frontend.
- Tích hợp ngoài phải có timeout, xử lý lỗi và phương án fallback.

## 6. Cách đọc

Đọc file 01–03 để nắm bài toán và nghiệp vụ; file 04–07 để triển khai kiến trúc, hành vi và dữ liệu; file 08 để xây dựng API, giao diện và an toàn; file 09 để kiểm tra ranh giới và phụ thuộc giữa các thành phần.
