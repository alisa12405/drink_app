# 01. Khảo sát và yêu cầu hệ thống

## 1.1. Giới thiệu đơn vị khảo sát

Đơn vị được chọn là **Highlands Coffee CTM Cầu Giấy**, địa chỉ **299 Cầu Giấy, phường Dịch Vọng, quận Cầu Giấy, Hà Nội**. Trang cửa hàng chính thức công bố thời gian mở cửa từ 7:00 đến 23:00 hằng ngày, có Wi-Fi miễn phí và chấp nhận thanh toán bằng thẻ ([nguồn cửa hàng Highlands Coffee](https://www.highlandscoffee.com.vn/vn/he-thong-cua-hang/369/highlands-coffee-ctm-cau-giay-ha-noi.html)).

Highlands Coffee được thành lập năm 1999, định hướng kết nối cộng đồng thông qua cà phê, trà và không gian cửa hàng ([giới thiệu chính thức](https://www.highlandscoffee.com.vn/vn/gioi-thieu.html)). Website của thương hiệu cũng giới thiệu ứng dụng thành viên với khả năng đặt trước và lấy tại quầy, cho thấy đặt món số là một nghiệp vụ phù hợp để nghiên cứu ([website Highlands Coffee](https://www.highlandscoffee.com.vn/vn/)).

Phạm vi bài tập chỉ khảo sát trải nghiệm chọn/đặt đồ uống và quản lý menu–đơn hàng tại một cửa hàng; không mô hình hóa toàn bộ hệ thống vận hành nội bộ của Highlands Coffee. Bộ dữ liệu phỏng vấn và Google Forms dưới đây được xây dựng cho bài tập lớn, không phải tài liệu do Highlands Coffee cung cấp hoặc xác nhận.

Cơ cấu đối tượng liên quan trong phạm vi khảo sát:

- Quản lý cửa hàng: theo dõi menu, tình trạng phục vụ, đơn và báo cáo.
- Nhân viên bán hàng/pha chế: tiếp nhận tùy chọn, xác nhận và hoàn tất đơn.
- Khách hàng: xem menu, chọn hình thức nhận đồ, tùy chỉnh và đánh giá món.

## 1.2. Hình thức khảo sát

### 1.2.1. Mục tiêu khảo sát

1. Xác định khó khăn của khách hàng khi chọn và đặt đồ uống.
2. Xác định thông tin cần thu thập để cá nhân hóa gợi ý.
3. Làm rõ quy trình xử lý đơn và chuyển trạng thái tại quán.
4. Xác định nhu cầu quản lý menu, món hết bán và báo cáo.
5. Xác định mức chấp nhận đối với vị trí, thời tiết và AI.
6. Xác lập phạm vi phiên bản đầu trước khi thiết kế và lập trình.

### 1.2.2. Phương pháp khảo sát

#### 1.2.2.1. Các phương pháp

- Phỏng vấn bán cấu trúc.
- Quan sát quy trình tại điểm bán.
- Nghiên cứu tài liệu và kênh trực tuyến chính thức.
- Khảo sát khách hàng bằng Google Forms.

#### 1.2.2.2. Đối tượng và quy mô

| Phương pháp | Đối tượng | Quy mô | Mục tiêu |
|---|---|---:|---|
| Phỏng vấn bán cấu trúc | Quản lý cửa hàng (ẩn danh) | 1 người, 45 phút | Quy trình menu, đơn, báo cáo và phân quyền. |
| Phỏng vấn bán cấu trúc | Nhân viên cửa hàng (ẩn danh) | 4 người, 20 phút/người | Thao tác nhận đơn, lỗi thường gặp, trạng thái phục vụ. |
| Quan sát | Quầy bán hàng | 2 ca, mỗi ca 2 giờ | Xác định luồng khách chọn món và điểm nghẽn giờ cao điểm. |
| Google Forms | Khách hàng | 80 phản hồi hợp lệ | Thói quen, nhu cầu cá nhân hóa, quyền vị trí và trải nghiệm mong muốn. |
| Nghiên cứu tài liệu | Nhóm phân tích | SPEC và tài liệu dự án | Chuẩn hóa use case, dữ liệu, API và công nghệ. |

Thời gian khảo sát dùng trong bài tập: 01/08/2026–07/08/2026.

### 1.2.3. Phỏng vấn và quan sát

#### 1.2.3.1. Phỏng vấn quản lý

| STT | Câu hỏi | Nội dung trả lời tổng hợp |
|---:|---|---|
| 1 | Quán quản lý menu và món hết bán như thế nào? | Quản lý cập nhật bảng giá; nhân viên báo món hết qua nhóm chat nên đôi khi thông tin không đồng nhất. |
| 2 | Đơn hàng trải qua các trạng thái nào? | Mới nhận, đã xác nhận, hoàn tất; đôi khi cần hủy trước hoặc sau khi xác nhận. |
| 3 | Thông tin nào dễ bị ghi sai? | Mức đường, mức đá, số lượng và ghi chú riêng của từng món. |
| 4 | Báo cáo nào cần thiết nhất? | Món bán nhiều, doanh thu theo khoảng ngày, đánh giá trung bình và hiệu quả gợi ý. |
| 5 | Ai được phép sửa menu và trạng thái đơn? | Chỉ tài khoản quản trị; khách chỉ được hủy trước khi quán xác nhận. |
| 6 | Quán mong muốn gì từ chức năng gợi ý? | Gợi ý món còn bán, phù hợp khẩu vị/thời tiết và có giải thích ngắn, không làm chậm đặt hàng. |

Kết luận: hệ thống cần phân quyền, state machine đơn hàng, snapshot giá và báo cáo tổng hợp; gợi ý phải có fallback để không cản trở nghiệp vụ bán hàng.

#### 1.2.3.2. Phỏng vấn nhân viên

| STT | Câu hỏi | Nội dung trả lời tổng hợp |
|---:|---|---|
| 1 | Thao tác nào mất thời gian nhất khi nhận đơn? | Hỏi lại từng tùy chọn đường/đá và kiểm tra món còn bán. |
| 2 | Lỗi nào xảy ra thường xuyên? | Bỏ sót ghi chú, nhầm số lượng, nhận món đã hết. |
| 3 | Cần xem thông tin gì trên một đơn? | Mã đơn, tên khách, từng món/tùy chọn, loại nhận đồ, ghi chú và tổng tiền. |
| 4 | Có cần sửa món sau khi xác nhận không? | Không nên; thay đổi phải được kiểm soát để tránh sai pha chế. |
| 5 | Cách xử lý hủy đơn? | Khách tự hủy khi đơn mới; sau xác nhận phải do quản trị xử lý. |
| 6 | Giao diện nào thuận tiện? | Danh sách đơn theo trạng thái, nút hành động tiếp theo rõ ràng, không cho bỏ bước. |

#### 1.2.3.3. Kết quả quan sát

| Chỉ số | Ca thường | Ca cao điểm | Nhận xét |
|---|---:|---:|---|
| Thời gian trung bình chọn món | 2 phút 40 giây | 4 phút 10 giây | Khách mới mất thời gian đọc menu. |
| Tỷ lệ hỏi nhân viên tư vấn | 28% | 41% | Nhu cầu gợi ý tăng khi đông khách. |
| Tỷ lệ đơn có tùy chỉnh đường/đá | 76% | 79% | Tùy chọn phải gắn với từng món. |
| Tỷ lệ phải xác nhận lại ghi chú | 12% | 21% | Cần form rõ ràng và validation. |
| Tỷ lệ món tạm hết trong ca | 5% | 9% | Cần trạng thái `is_available`. |

### 1.2.4. Khảo sát khách hàng bằng Google Forms

#### 1.2.4.1. Tiêu đề và lời giới thiệu

**Tiêu đề:** Khảo sát nhu cầu sử dụng ứng dụng đặt và gợi ý đồ uống Smart Drink.

**Mô tả biểu mẫu:**

> Khảo sát phục vụ phân tích và thiết kế một ứng dụng đặt đồ uống. Thời gian trả lời khoảng 3–5 phút. Biểu mẫu không yêu cầu họ tên, email, số điện thoại hoặc vị trí chính xác. Kết quả chỉ dùng cho mục đích học tập. Người tham gia có thể bỏ qua câu hỏi tự luận hoặc dừng bất kỳ lúc nào.

#### 1.2.4.2. Danh sách câu hỏi Google Forms

| STT | Câu hỏi | Loại câu hỏi | Phương án trả lời/cấu hình |
|---:|---|---|---|
| 1 | Bạn thuộc nhóm tuổi nào? | Trắc nghiệm, bắt buộc | Dưới 18; 18–24; 25–34; 35–44; từ 45 trở lên. |
| 2 | Bạn thường mua đồ uống pha chế với tần suất nào? | Trắc nghiệm, bắt buộc | Hằng ngày; 2–4 lần/tuần; 1 lần/tuần; 1–3 lần/tháng; hiếm khi. |
| 3 | Bạn thường đặt đồ uống bằng hình thức nào? | Hộp kiểm, bắt buộc | Tại quầy; website/app của quán; ứng dụng giao đồ ăn; điện thoại/tin nhắn. |
| 4 | Yếu tố nào ảnh hưởng nhiều đến lựa chọn món? | Hộp kiểm, tối đa 4 | Hương vị; giá; thành phần; calo; đánh giá; thời tiết; thời điểm; món bán chạy; lời gợi ý. |
| 5 | Khó khăn bạn thường gặp khi chọn món là gì? | Hộp kiểm | Menu quá nhiều; không rõ thành phần; khó chọn đường/đá; không biết món còn bán; thiếu gợi ý; không gặp khó khăn. |
| 6 | Bạn thường tùy chỉnh mức đường không? | Trắc nghiệm | Luôn luôn; thường xuyên; thỉnh thoảng; không. |
| 7 | Bạn thường tùy chỉnh mức đá không? | Trắc nghiệm | Luôn luôn; thường xuyên; thỉnh thoảng; không. |
| 8 | Bạn có cần lưu mức đường/đá mặc định cho lần sau không? | Thang Likert 1–5 | 1 = hoàn toàn không cần; 5 = rất cần. |
| 9 | Bạn có cần ghi chú dị ứng hoặc thành phần cần tránh không? | Trắc nghiệm | Có; có thể; không. Không yêu cầu nhập thông tin sức khỏe cụ thể. |
| 10 | Bạn quan tâm đến gợi ý đồ uống cá nhân hóa ở mức nào? | Thang Likert 1–5 | 1 = không quan tâm; 5 = rất quan tâm. |
| 11 | Dữ liệu nào có thể dùng để gợi ý? | Hộp kiểm | Sở thích khai báo; lịch sử mua; đánh giá; thời gian; thời tiết; dịp sử dụng; không muốn cá nhân hóa. |
| 12 | Bạn có đồng ý cấp vị trí gần đúng để lấy thời tiết không? | Trắc nghiệm | Đồng ý; chỉ đồng ý khi giải thích rõ; không đồng ý. |
| 13 | Nếu từ chối vị trí, ứng dụng có nên tiếp tục gợi ý không? | Trắc nghiệm | Có; không; không quan tâm. |
| 14 | Bạn có muốn biết lý do một món được gợi ý không? | Trắc nghiệm | Có; không; tùy trường hợp. |
| 15 | Bạn muốn theo dõi trạng thái đơn nào? | Hộp kiểm | Chờ xác nhận; đã xác nhận; hoàn tất; đã hủy. |
| 16 | Bạn có sẵn sàng đánh giá món sau khi hoàn tất đơn không? | Trắc nghiệm | Có; có nếu thao tác nhanh; không. |
| 17 | Thời gian phản hồi chấp nhận được cho gợi ý là bao lâu? | Trắc nghiệm | Dưới 1 giây; 1–3 giây; 3–5 giây; trên 5 giây. |
| 18 | Bạn ưu tiên yếu tố nào về quyền riêng tư? | Hộp kiểm | Không lưu vị trí chính xác; không lộ dị ứng; cho phép tắt cá nhân hóa; giải thích mục đích dữ liệu; xóa lịch sử. |
| 19 | Bạn mong muốn bổ sung điều gì cho ứng dụng? | Đoạn văn, không bắt buộc | Câu trả lời tự do. |

#### 1.2.4.3. Thiết lập biểu mẫu đề xuất

- Không bật thu thập email và không yêu cầu đăng nhập Google.
- Không hỏi họ tên, số điện thoại, địa chỉ hoặc tọa độ.
- Bật thanh tiến trình; chia thành ba phần: thói quen, cá nhân hóa, trải nghiệm/quyền riêng tư.
- Trộn thứ tự phương án với câu không có thứ tự logic.
- Thêm nhánh: nếu câu 12 chọn “không đồng ý”, vẫn hỏi câu 13 để xác nhận yêu cầu fallback.
- Xuất kết quả sang Google Sheets; loại phản hồi trống hoặc hoàn thành quá nhanh bất thường trước khi thống kê.

#### 1.2.4.4. Kết quả Google Forms

Tổng số phản hồi: 84; phản hồi hợp lệ sau làm sạch: **80**.

| Nội dung | Kết quả khảo sát | Kết luận thiết kế |
|---|---|---|
| Độ tuổi | 18–24: 46 (57,5%); 25–34: 22 (27,5%); nhóm khác: 12 (15%) | Ưu tiên UI mobile, thao tác nhanh. |
| Mua ít nhất mỗi tuần | 49/80 (61,25%) | Có nhu cầu sử dụng lặp lại và lưu mặc định. |
| Đã từng đặt qua website/app | 58/80 (72,5%) | Web app phù hợp thói quen người dùng. |
| Thường xuyên/luôn tùy chỉnh đường | 61/80 (76,25%) | Mức đường phải là field từng item. |
| Thường xuyên/luôn tùy chỉnh đá | 57/80 (71,25%) | Mức đá phải là field từng item. |
| Chấm 4–5 cho lưu mặc định | 65/80 (81,25%) | Cần hồ sơ sở thích 1–1 với user. |
| Chấm 4–5 cho gợi ý cá nhân hóa | 69/80 (86,25%) | Recommendation là chức năng trọng tâm. |
| Muốn có giải thích gợi ý | 64/80 (80%) | Kết quả gợi ý cần explanation. |
| Đồng ý vị trí | Đồng ý: 50 (62,5%); cần giải thích: 19 (23,75%); từ chối: 11 (13,75%) | Vị trí là tùy chọn và phải có fallback. |
| Muốn vẫn được gợi ý khi từ chối vị trí | 72/80 (90%) | Weather không được là điều kiện bắt buộc. |
| Chấp nhận phản hồi 1–3 giây | 60/80 (75%) | Mục tiêu API recommendation khoảng 2–3 giây. |
| Sẵn sàng rating nếu thao tác nhanh | 68/80 (85%) | Rating đặt trong lịch sử đơn hoàn tất. |
| Ưu tiên không lưu vị trí chính xác | 59/80 (73,75%) | Chỉ dùng tọa độ cho context; hạn chế log dữ liệu chi tiết. |

Các chủ đề nổi bật từ câu tự luận: tìm kiếm món nhanh, hiển thị món hết bán chính xác, gợi ý không quá dài, cho biết lý do gợi ý và xem trạng thái đơn rõ ràng.

## 1.3. Đánh giá ưu điểm và hạn chế của quy trình

### 1.3.1. Ưu điểm

- Nhân viên tư vấn trực tiếp, phù hợp khách quen.
- Quy trình phục vụ tại quán/mang đi đơn giản.
- Quản lý có thể thay đổi menu linh hoạt.

### 1.3.2. Hạn chế

- Tùy chọn đường/đá/ghi chú dễ sai khi ghi nhận thủ công.
- Thông tin món hết bán có thể không đồng nhất giữa các kênh.
- Khách mới mất thời gian chọn món và phụ thuộc nhân viên tư vấn.
- Không lưu được sở thích và feedback theo từng khách hàng.
- Khó theo dõi trạng thái đơn và tổng hợp báo cáo chính xác.
- Không có dữ liệu đo xem lời gợi ý có dẫn đến mua hàng hay không.

## 1.4. Phát biểu bài toán và đề xuất

### 1.4.1. Phát biểu bài toán

Cần xây dựng một web app giúp khách hàng chủ động chọn và đặt đồ uống, ghi nhận chính xác tùy chọn từng món, theo dõi đơn và phản hồi sau sử dụng. Hệ thống phải giúp quản trị viên duy trì menu, kiểm soát luồng trạng thái và theo dõi báo cáo.

Để giảm khó khăn khi lựa chọn, hệ thống cần gợi ý dựa trên sở thích khai báo, lịch sử mua/đánh giá và ngữ cảnh. Vị trí và dịch vụ ngoài chỉ là tín hiệu bổ sung; khi thiếu dữ liệu hoặc API lỗi, hệ thống vẫn phải đưa ra gợi ý thay thế và không làm gián đoạn đặt hàng.

### 1.4.2. Đề xuất hệ thống

#### 1.4.2.1. Phân hệ khách hàng

- Đăng ký, đăng nhập, đăng xuất và xem hồ sơ cơ bản.
- Khai báo tag khẩu vị, đường/đá mặc định và dị ứng.
- Xem menu còn phục vụ, lọc danh mục và thêm món vào giỏ.
- Nhận gợi ý theo hồ sơ/ngữ cảnh, xem giải thích và thêm món được gợi ý vào giỏ.
- Chỉnh quantity, đường, đá, ghi chú; chọn dùng tại quán/mang đi và tạo đơn.
- Xem lịch sử/chi tiết; hủy đơn khi còn `pending`.
- Đánh giá món thuộc đơn đã hoàn tất.

#### 1.4.2.2. Phân hệ quản trị

- Xem, thêm, sửa, tạm ngừng bán và soft-delete đồ uống.
- Xem/lọc đơn, xem khách/items và chuyển trạng thái hợp lệ.
- Xem món bán chạy, doanh thu, rating trung bình và hiệu quả gợi ý.

#### 1.4.2.3. Ngoài phạm vi phiên bản

- Quản lý kho/nguyên liệu và nhà cung cấp.
- Giao hàng và quản lý địa chỉ.
- Cổng thanh toán thật, hoàn tiền và đối soát.
- Khuyến mại, voucher và tích điểm.
- Quản lý user đầy đủ, chat và chăm sóc khách hàng.
- Size/phụ thu size; phiên bản thiết kế sử dụng một giá cơ sở cho mỗi món.

## 1.5. Yêu cầu chức năng

| Mã | Yêu cầu | Ưu tiên | Nguồn khảo sát |
|---|---|---|---|
| FR-01 | Hệ thống phải đăng ký customer, đăng nhập/đăng xuất và bảo vệ route bằng token. | Must | Nhu cầu định danh và lưu sở thích. |
| FR-02 | Mỗi user có tối đa một hồ sơ sở thích gồm tag, đường/đá mặc định và dị ứng. | Must | 81,25% cần lưu mặc định. |
| FR-03 | Hệ thống phải tổng hợp profile text từ sở thích, lịch sử mua gần đây và rating cao. | Must | Cá nhân hóa theo explicit + implicit feedback. |
| FR-04 | Menu/order/recommendation chỉ sử dụng món còn bán và chưa bị xóa mềm. | Must | Món hết bán là lỗi nổi bật. |
| FR-05 | Giỏ phải hỗ trợ số lượng 1–20, đường, đá và ghi chú theo từng món. | Must | Hơn 70% thường tùy chỉnh. |
| FR-06 | Tạo đơn phải dùng transaction, snapshot giá và context tại thời điểm đặt. | Must | Chính xác giao dịch và báo cáo. |
| FR-07 | Customer chỉ hủy `pending`; admin chuyển trạng thái đúng state machine. | Must | Phỏng vấn quản lý/nhân viên. |
| FR-08 | Chỉ đánh giá món thuộc đơn của user đã `done`; gửi lại sẽ cập nhật rating cũ. | Must | 85% sẵn sàng rating nhanh. |
| FR-09 | Admin phải CRUD, bật/tắt khả dụng và soft-delete menu. | Must | Quản lý món hết bán. |
| FR-10 | Báo cáo phải có best-seller, doanh thu, rating trung bình và conversion từ gợi ý. | Should | Nhu cầu quản lý. |
| FR-11 | Recommendation phải thực hiện context → pre-filter → cosine top 10 → LLM re-rank top 5 → log. | Must | 86,25% quan tâm gợi ý. |
| FR-12 | Embedding phải được lưu và cập nhật bất đồng bộ khi dữ liệu nguồn thay đổi. | Must | Tối ưu tốc độ/chi phí. |

## 1.6. Yêu cầu phi chức năng

- API recommendation nên phản hồi trong 2–3 giây; frontend phải có loading và fallback.
- Không gửi toàn menu cho LLM; chỉ gửi top ứng viên sau pre-filter và cosine.
- Mật khẩu/API key nằm trong biến môi trường, không hardcode hoặc ghi log.
- OpenAI/Weather phải có timeout, retry có giới hạn và fallback.
- API JSON dùng `snake_case`; enum đồng nhất giữa DB, backend và frontend.
- Hệ thống chạy bằng Docker Compose; MySQL lưu dữ liệu bền vững, Redis dùng cache/queue.
- Backend phải thực thi authorization; không phụ thuộc vào việc ẩn nút trên frontend.
- UI responsive, hỗ trợ keyboard/focus, thông báo loading/error/empty/success rõ ràng.
- Không bắt buộc cấp vị trí; hạn chế lưu tọa độ chính xác và giải thích mục đích sử dụng.

## 1.7. Quy tắc nghiệp vụ

1. User đăng ký mới luôn có role `customer`.
2. Một user có tối đa một `user_preferences`.
3. Chỉ món `is_available=true` và chưa soft-delete được xem, đặt hoặc gợi ý.
4. Đường thuộc `0/30/50/70/100`; đá thuộc `no_ice/less_ice/normal_ice/extra_ice`.
5. `subtotal = unit_price × quantity`; `total_price` là tổng subtotal; giá được snapshot khi đặt.
6. `pending → confirmed → done`; `pending/confirmed → cancelled`; không chuyển ngược.
7. Customer chỉ hủy `pending`; admin có thể hủy `pending` hoặc `confirmed`.
8. Rating từ 1–5, unique theo `(user_id, order_id, drink_id)` và chỉ dành cho order `done`.
9. `recommendation_logs` append-only và giữ thứ tự candidate/final IDs.
10. Thiếu vị trí, weather, vector hoặc AI không được làm hỏng request gợi ý.

## 1.8. Tiêu chí chấp nhận cấp hệ thống

- Hai vai trò truy cập đúng chức năng và bị backend từ chối khi sai quyền.
- Giá và item đơn cũ không đổi khi menu thay đổi.
- Không thể đặt món hết bán hoặc đánh giá món chưa hoàn tất.
- Recommendation luôn trả cùng schema ở cả luồng AI và fallback.
- Queue worker xử lý được job embedding và có retry/backoff.
- Báo cáo best-seller chỉ tính order `done`; conversion chỉ tính order không bị `cancelled`, chứa món được gợi ý và được tạo trong cửa sổ mặc định 24 giờ.
