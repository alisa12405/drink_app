# CHƯƠNG 1. KHẢO SÁT VÀ YÊU CẦU HỆ THỐNG

Trong chương này, nhóm khảo sát quy trình chọn món, tiếp nhận đơn và quản lý hoạt động tại một cửa hàng cà phê nhỏ. Kết quả là cơ sở phát biểu bài toán, xác định chức năng và thiết kế hệ thống Smart Drink trước khi lập trình.

## 1.1. Giới thiệu đơn vị khảo sát

- **Đơn vị khảo sát:** Cửa hàng Mộc Nhiên Coffee.
- **Địa chỉ:** Số 18, ngõ 45 Trần Thái Tông, phường Dịch Vọng Hậu, quận Cầu Giấy, Hà Nội.
- **Năm thành lập:** 2022.
- **Loại hình:** Cửa hàng cà phê độc lập, phục vụ tại quán và mang đi.
- **Thời gian hoạt động:** 07:00–22:30 hằng ngày.
- **Quy mô:** Một cửa hàng, 45 chỗ ngồi, trung bình 120–160 lượt khách/ngày.

Mộc Nhiên Coffee phục vụ chủ yếu sinh viên, nhân viên văn phòng và người dân quanh khu vực. Menu có khoảng 35 món thuộc các nhóm cà phê, trà, trà sữa, đá xay và nước trái cây. Ngoài bán trực tiếp tại quầy, cửa hàng nhận đơn mang đi qua điện thoại và tin nhắn.

Cơ cấu tổ chức gồm:

- Nguyễn Thị Minh Anh, 32 tuổi, chủ cửa hàng kiêm quản lý: quản lý menu, giá, nhân sự, đơn và doanh thu.
- Hai nhân viên thu ngân kiêm phục vụ: tiếp nhận đơn, ghi tùy chọn, thu tiền và giao đồ uống.
- Ba nhân viên pha chế: tiếp nhận phiếu món, pha chế và thông báo hoàn tất.
- Một nhân viên bán thời gian hỗ trợ ca tối và cuối tuần.

Hiện cửa hàng sử dụng menu in, phiếu ghi món và bảng tính để tổng hợp doanh thu. Khẩu vị khách quen do nhân viên ghi nhớ; cửa hàng chưa có công cụ lưu sở thích, gợi ý món hoặc theo dõi đánh giá theo từng khách hàng.

> Thông tin cửa hàng, nhân sự, phỏng vấn và số liệu khảo sát là tình huống nghiên cứu do nhóm xây dựng riêng cho bài tập lớn.

## 1.2. Hình thức khảo sát

### 1.2.1. Hình thức thực hiện

- Phỏng vấn trực tiếp quản lý cửa hàng.
- Phỏng vấn nhân viên bán hàng và pha chế.
- Quan sát quy trình tại quầy ở ca thường và ca cao điểm.
- Khảo sát khách hàng bằng Google Forms.
- Nghiên cứu menu, phiếu ghi món và bảng tổng hợp doanh thu.

### 1.2.2. Đối tượng và kế hoạch khảo sát

| Phương pháp | Đối tượng | Quy mô | Thời gian | Mục đích |
|---|---|---:|---|---|
| Phỏng vấn | Chủ cửa hàng kiêm quản lý | 1 người | 45 phút | Quy trình menu, đơn và báo cáo. |
| Phỏng vấn | Nhân viên thu ngân/pha chế | 3 người | 20 phút/người | Thao tác và lỗi thường gặp. |
| Quan sát | Hoạt động tại quầy | 2 ca, 2 giờ/ca | 05–06/08/2026 | Luồng chọn món và xử lý đơn. |
| Google Forms | Khách hàng | 60 phản hồi hợp lệ | 05–10/08/2026 | Thói quen và nhu cầu ứng dụng. |
| Nghiên cứu tài liệu | Nhóm phân tích | 3 loại tài liệu | 04/08/2026 | Menu, phiếu món và báo cáo. |

### 1.2.3. Phỏng vấn nhân viên bán hàng

- **Người hỏi:** Lê Hoàng Nam, 21 tuổi, thành viên nhóm phân tích.
- **Người trả lời:** Trần Quốc Huy, 23 tuổi, nhân viên thu ngân kiêm pha chế.
- **Kinh nghiệm:** 14 tháng làm việc tại cửa hàng.
- **Thời gian:** 16:00 ngày 05/08/2026.
- **Địa điểm:** Quầy phục vụ Mộc Nhiên Coffee.

**Bảng 1.1. Câu hỏi phỏng vấn nhân viên bán hàng**

| STT | Câu hỏi | Nội dung trả lời |
|---:|---|---|
| 1 | Quy trình tiếp nhận đơn diễn ra thế nào? | Hỏi món, số lượng, đường, đá, ghi chú, hình thức nhận rồi ghi phiếu cho pha chế. |
| 2 | Thao tác nào mất nhiều thời gian nhất? | Giới thiệu món cho khách mới và hỏi lại từng tùy chọn. |
| 3 | Lỗi nào thường xảy ra? | Bỏ sót ghi chú, nhầm đường/đá hoặc nhận món vừa hết nguyên liệu. |
| 4 | Nhân viên biết món hết bằng cách nào? | Pha chế báo miệng hoặc ghi bảng nhỏ nên đôi khi cập nhật chậm. |
| 5 | Khách thường hỏi tư vấn gì? | Món ít ngọt, cà phê nhẹ, món nóng/lạnh và phù hợp thời tiết. |
| 6 | Đơn có các trạng thái nào? | Mới nhận, đã xác nhận, hoàn tất và hủy. |
| 7 | Khách được thay đổi/hủy đơn khi nào? | Thuận tiện nhất trước khi nhân viên xác nhận pha chế. |
| 8 | Giao diện xử lý đơn nên có gì? | Danh sách theo trạng thái, tùy chọn rõ và nút chuyển bước tiếp theo. |

Kết luận: hệ thống cần ghi tùy chọn theo từng món, cập nhật khả dụng và kiểm soát chuyển trạng thái đơn.

### 1.2.4. Phỏng vấn người quản lý

- **Người hỏi:** Lê Hoàng Nam, 21 tuổi, thành viên nhóm phân tích.
- **Người trả lời:** Nguyễn Thị Minh Anh, 32 tuổi, chủ cửa hàng kiêm quản lý.
- **Kinh nghiệm:** 4 năm quản lý cửa hàng đồ uống.
- **Thời gian:** 09:30 ngày 06/08/2026.
- **Địa điểm:** Khu vực văn phòng Mộc Nhiên Coffee.

**Bảng 1.2. Câu hỏi phỏng vấn người quản lý**

| STT | Câu hỏi | Nội dung trả lời |
|---:|---|---|
| 1 | Cửa hàng quản lý menu và giá thế nào? | Cập nhật bảng tính rồi in lại menu; món tạm hết được báo riêng cho nhân viên. |
| 2 | Khó khăn lớn nhất khi đông khách? | Tư vấn mất thời gian, phiếu dễ lẫn thứ tự và khó theo dõi đơn đang chờ. |
| 3 | Thông tin khách nào cần lưu? | Tài khoản, sở thích, đường/đá và lịch sử mua; không lưu dữ liệu không liên quan. |
| 4 | Ai được sửa menu và trạng thái đơn? | Quản lý sửa menu; quyền vận hành được gom vào vai trò admin trong bài tập. |
| 5 | Báo cáo nào cần thiết? | Món bán chạy, doanh thu, đánh giá trung bình và hiệu quả gợi ý. |
| 6 | Mong muốn đối với gợi ý? | Ngắn gọn, ưu tiên món còn bán, theo khẩu vị/thời tiết và không bắt buộc vị trí. |
| 7 | Khách được hủy đơn khi nào? | Chỉ tự hủy lúc đơn mới nhận; sau xác nhận cần liên hệ cửa hàng. |
| 8 | Yêu cầu quan trọng nhất? | Dễ dùng trên điện thoại, không làm chậm bán hàng và báo cáo rõ ràng. |

Kết luận: hệ thống cần phân quyền, quản lý menu tập trung, snapshot giá, báo cáo và gợi ý có fallback.

### 1.2.5. Phỏng vấn khách hàng

- **Người hỏi:** Lê Hoàng Nam, 21 tuổi, thành viên nhóm phân tích.
- **Người trả lời:** Phạm Thu Hà, 26 tuổi, nhân viên văn phòng, đến quán 2–3 lần/tuần.
- **Thời gian:** 11:45 ngày 06/08/2026.

| STT | Câu hỏi | Nội dung trả lời |
|---:|---|---|
| 1 | Khó khăn khi chọn đồ uống? | Khó biết món mới có hợp khẩu vị và không nhớ mức đường lần trước. |
| 2 | Có muốn lưu sở thích không? | Có; thường chọn 30% đường, ít đá và trà trái cây. |
| 3 | Có quan tâm gợi ý theo thời tiết? | Có nhưng không muốn bắt buộc cấp vị trí chính xác. |
| 4 | Muốn theo dõi gì sau khi đặt? | Mã đơn, món, tổng tiền và trạng thái. |
| 5 | Có sẵn sàng đánh giá món? | Có nếu chọn sao và nhận xét ngay trong lịch sử đơn. |

### 1.2.6. Kết quả quan sát hiện trường

| Chỉ số | Ca thường | Ca cao điểm | Nhận xét |
|---|---:|---:|---|
| Số đơn quan sát | 24 | 38 | Ca cao điểm 11:30–13:30. |
| Thời gian trung bình chọn món | 2 phút 35 giây | 4 phút 05 giây | Khách mới cần nhiều thời gian hơn. |
| Khách hỏi tư vấn | 7/24 | 16/38 | Nhu cầu tư vấn tăng khi đông. |
| Đơn tùy chỉnh đường/đá | 18/24 | 30/38 | Tùy chọn phải lưu theo từng món. |
| Đơn phải xác nhận lại | 3/24 | 8/38 | Cần form và validation rõ. |
| Món tạm hết | 2 món | 3 món | Cần cập nhật `is_available`. |

### 1.2.7. Khảo sát khách hàng bằng Google Forms

**Tiêu đề:** Khảo sát nhu cầu sử dụng ứng dụng đặt và gợi ý đồ uống Smart Drink.

**Mô tả:** Khảo sát phục vụ bài tập lớn phân tích và thiết kế ứng dụng cho Mộc Nhiên Coffee. Thời gian trả lời 3–5 phút; không thu thập họ tên, email, số điện thoại hoặc vị trí chính xác.

**Bảng 1.3. Danh sách câu hỏi Google Forms**

| STT | Câu hỏi | Loại | Phương án/cấu hình |
|---:|---|---|---|
| 1 | Bạn thuộc nhóm tuổi nào? | Trắc nghiệm | Dưới 18; 18–24; 25–34; 35–44; từ 45. |
| 2 | Nghề nghiệp hiện tại? | Trắc nghiệm | Học sinh/sinh viên; văn phòng; kinh doanh; khác. |
| 3 | Tần suất mua đồ uống pha chế? | Trắc nghiệm | Hằng ngày; 2–4 lần/tuần; tuần; tháng; hiếm khi. |
| 4 | Hình thức đặt thường dùng? | Hộp kiểm | Tại quầy; web/app quán; app giao đồ ăn; điện thoại/tin nhắn. |
| 5 | Yếu tố ảnh hưởng lựa chọn? | Hộp kiểm | Vị; giá; thành phần; đánh giá; thời tiết; thời điểm; bán chạy; gợi ý. |
| 6 | Khó khăn khi chọn món? | Hộp kiểm | Menu nhiều; không rõ thành phần; đường/đá; món hết; thiếu gợi ý; không khó. |
| 7 | Bạn thường tùy chỉnh đường? | Trắc nghiệm | Luôn; thường xuyên; thỉnh thoảng; không. |
| 8 | Bạn thường tùy chỉnh đá? | Trắc nghiệm | Luôn; thường xuyên; thỉnh thoảng; không. |
| 9 | Có cần lưu đường/đá mặc định? | Likert 1–5 | 1 = không cần; 5 = rất cần. |
| 10 | Có cần ghi chú dị ứng/thành phần tránh? | Trắc nghiệm | Có; có thể; không. |
| 11 | Mức quan tâm gợi ý cá nhân hóa? | Likert 1–5 | 1 = không quan tâm; 5 = rất quan tâm. |
| 12 | Dữ liệu có thể dùng để gợi ý? | Hộp kiểm | Sở thích; lịch sử; đánh giá; giờ; thời tiết; dịp; không cá nhân hóa. |
| 13 | Có cấp vị trí gần đúng để lấy thời tiết? | Trắc nghiệm | Đồng ý; cần giải thích; không đồng ý. |
| 14 | Từ chối vị trí vẫn nên gợi ý? | Trắc nghiệm | Có; không; không quan tâm. |
| 15 | Có muốn biết lý do món được gợi ý? | Trắc nghiệm | Có; không; tùy trường hợp. |
| 16 | Muốn theo dõi trạng thái đơn nào? | Hộp kiểm | Chờ; xác nhận; hoàn tất; hủy. |
| 17 | Có sẵn sàng đánh giá sau khi hoàn tất? | Trắc nghiệm | Có; có nếu nhanh; không. |
| 18 | Thời gian phản hồi gợi ý chấp nhận được? | Trắc nghiệm | Dưới 1; 1–3; 3–5; trên 5 giây. |
| 19 | Mong muốn bổ sung điều gì? | Đoạn văn | Không bắt buộc. |

Biểu mẫu chia ba phần: thông tin chung, thói quen chọn món và nhu cầu cá nhân hóa; không bật thu thập email. Có 64 phản hồi, sau làm sạch còn **60 phản hồi hợp lệ**.

**Bảng 1.4. Tổng hợp kết quả Google Forms**

| Nội dung | Kết quả | Kết luận thiết kế |
|---|---|---|
| Độ tuổi | 18–24: 31 (51,7%); 25–34: 18 (30%); khác: 11 (18,3%) | Ưu tiên UI mobile. |
| Nghề nghiệp | Sinh viên: 25 (41,7%); văn phòng: 23 (38,3%); khác: 12 (20%) | Phù hợp nhóm khách chính. |
| Mua ít nhất mỗi tuần | 39/60 (65%) | Có nhu cầu dùng lặp lại. |
| Đã đặt qua web/app | 42/60 (70%) | Web app phù hợp. |
| Thường xuyên/luôn tùy chỉnh đường | 45/60 (75%) | Lưu đường theo item. |
| Thường xuyên/luôn tùy chỉnh đá | 43/60 (71,7%) | Lưu đá theo item. |
| Chấm 4–5 cho lưu mặc định | 47/60 (78,3%) | Cần hồ sơ sở thích. |
| Chấm 4–5 cho cá nhân hóa | 50/60 (83,3%) | Recommendation là trọng tâm. |
| Muốn giải thích gợi ý | 46/60 (76,7%) | Cần explanation ngắn. |
| Đồng ý vị trí | Đồng ý 35; cần giải thích 16; từ chối 9 | Vị trí là tùy chọn. |
| Vẫn muốn gợi ý khi từ chối vị trí | 53/60 (88,3%) | Phải có fallback. |
| Chấp nhận phản hồi 1–3 giây | 44/60 (73,3%) | Mục tiêu 2–3 giây. |
| Sẵn sàng đánh giá nếu nhanh | 49/60 (81,7%) | Rating trong lịch sử. |

Ý kiến tự luận tập trung vào tìm món nhanh, hiển thị món hết chính xác, lưu tùy chọn, giải thích gợi ý ngắn và trạng thái đơn rõ ràng.

## 1.3. Đánh giá ưu điểm và hạn chế của hệ thống hiện tại

### 1.3.1. Ưu điểm

- Quy trình trực tiếp đơn giản, phù hợp cửa hàng nhỏ.
- Nhân viên tư vấn linh hoạt và ghi nhớ khẩu vị khách quen.
- Menu và giá có thể thay đổi nhanh theo nguyên liệu.
- Quản lý trực tiếp nên xử lý tình huống phát sinh nhanh.

### 1.3.2. Hạn chế

- Phiếu thủ công dễ thiếu số lượng, đường, đá hoặc ghi chú.
- Trạng thái món hết chưa đồng bộ giữa thu ngân và pha chế.
- Khách mới mất thời gian chọn món và phụ thuộc tư vấn.
- Không lưu sở thích, lịch sử và đánh giá theo khách hàng.
- Khó theo dõi trạng thái đơn trong giờ cao điểm.
- Báo cáo bảng tính tốn thời gian, chưa đo hiệu quả gợi ý.

## 1.4. Phát biểu bài toán và đề xuất

### 1.4.1. Phát biểu bài toán

Mộc Nhiên Coffee cần hệ thống giúp khách xem menu, chọn và đặt đồ uống mà không phụ thuộc hoàn toàn vào tư vấn trực tiếp. Hệ thống phải ghi chính xác tùy chọn từng món, cho phép theo dõi/hủy đơn đúng điều kiện và thu thập đánh giá.

Cửa hàng cần quản lý tập trung menu và trạng thái phục vụ, xử lý đơn theo thứ tự, bảo toàn giá tại thời điểm đặt và xem báo cáo. Gợi ý dựa trên sở thích, lịch sử, đánh giá và ngữ cảnh; vị trí, thời tiết và AI là tín hiệu bổ sung, không được làm gián đoạn đặt hàng.

### 1.4.2. Đề xuất hệ thống

**Trang khách hàng:** quản lý tài khoản; khai báo sở thích; xem menu; nhận gợi ý; quản lý giỏ/đặt hàng; xem/hủy đơn; đánh giá món.

**Trang quản trị:** quản lý menu; xem/lọc/xử lý đơn; xem báo cáo món bán chạy, doanh thu, rating và hiệu quả gợi ý.

**Ngoài phạm vi:** kho/nguyên liệu, nhân sự/ca làm, giao hàng, thanh toán thật, khuyến mại, tích điểm, size và phụ thu size.

## 1.5. Yêu cầu chức năng

| Mã | Yêu cầu | Nguồn khảo sát |
|---|---|---|
| FR-01 | Đăng ký customer, đăng nhập/đăng xuất và bảo vệ route bằng token. | Định danh người dùng. |
| FR-02 | Mỗi user có tối đa một hồ sơ sở thích. | 78,3% cần lưu mặc định. |
| FR-03 | Tổng hợp profile từ sở thích, lịch sử và rating cao. | Nhu cầu cá nhân hóa. |
| FR-04 | Chỉ xem, đặt hoặc gợi ý món còn bán, chưa xóa mềm. | Lỗi thông tin món hết. |
| FR-05 | Giỏ hỗ trợ số lượng 1–20, đường, đá, ghi chú từng món. | Hơn 70% tùy chỉnh. |
| FR-06 | Tạo đơn trong transaction, snapshot giá và context. | Chính xác giao dịch. |
| FR-07 | Customer chỉ hủy `pending`; admin theo state machine. | Phỏng vấn quản lý/nhân viên. |
| FR-08 | Chỉ rating món thuộc đơn của user đã `done`. | 81,7% sẵn sàng đánh giá nhanh. |
| FR-09 | Admin CRUD, bật/tắt và soft-delete menu. | Quản lý món hết bán. |
| FR-10 | Báo cáo best-seller, doanh thu, rating và conversion. | Phỏng vấn quản lý. |
| FR-11 | Recommendation: context → lọc → cosine top 10 → LLM top 5 → log. | 83,3% quan tâm. |
| FR-12 | Cập nhật embedding bất đồng bộ khi nguồn thay đổi. | Yêu cầu hiệu năng. |

## 1.6. Yêu cầu phi chức năng

- Recommendation phản hồi mục tiêu 2–3 giây, có loading và fallback.
- Không gửi toàn menu cho LLM; tích hợp ngoài có timeout và retry giới hạn.
- Mật khẩu/API key không hardcode hoặc ghi log; backend thực thi phân quyền.
- API dùng `snake_case`; enum đồng nhất giữa DB, backend và frontend.
- Hệ thống dùng Docker Compose, MySQL và Redis cache/queue.
- UI responsive và thể hiện loading/error/empty/success rõ ràng.
- Không bắt buộc cấp vị trí; hạn chế lưu tọa độ chính xác.

## 1.7. Quy tắc nghiệp vụ

1. User đăng ký mới luôn có role `customer`; mỗi user có tối đa một preference.
2. Chỉ món `is_available=true`, chưa soft-delete được xem, đặt hoặc gợi ý.
3. Đường: `0/30/50/70/100`; đá: `no_ice/less_ice/normal_ice/extra_ice`.
4. Tổng đơn bằng tổng `unit_price × quantity`; giá được snapshot khi đặt.
5. `pending → confirmed → done`; `pending/confirmed → cancelled`; không chuyển ngược.
6. Customer chỉ hủy `pending`; rating 1–5 chỉ dành cho order `done`.
7. Thiếu vị trí, weather, vector hoặc AI không được làm hỏng request gợi ý.

## 1.8. Tiêu chí chấp nhận cấp hệ thống

- Hai vai trò truy cập đúng chức năng; backend từ chối sai quyền.
- Giá và item đơn cũ không đổi khi menu thay đổi.
- Không đặt món hết bán hoặc đánh giá món chưa hoàn tất.
- Recommendation giữ cùng schema ở luồng AI và fallback.
- Queue worker xử lý embedding với retry/backoff.
- Báo cáo best-seller chỉ tính order `done` và conversion theo cửa sổ 24 giờ.
