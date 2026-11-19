# YUNFLIMS — Giai đoạn 7: Kiểm thử và Bảo mật

## 1. Mục tiêu

Giai đoạn 7 tập trung vào xác minh các tiêu chí chất lượng và bảo mật của hệ thống theo yêu cầu nghiệp vụ:

- Đăng ký và đăng nhập
- Phân quyền người dùng
- Tạo và sửa phim / phòng / ghế
- Quản lý lịch chiếu
- Giữ ghế và chống bỏ ghế lẻ
- Thanh toán mock và vé điện tử
- Quét vé / check-in một lần
- Bảo mật request, người dùng, quyền truy cập và logging
- Kiểm thử độ tin cậy và tối thiểu hóa rủi ro xung đột đồng thời

## 2. Kiểm tra đã thực hiện

### 2.1 Kiểm thử cú pháp và route

- `php -l app/Models/User.php`
- `php -l app/Models/Movie.php`
- `php -l app/Http/Controllers/AuthController.php`
- `php -l app/Http/Controllers/BookingController.php`
- `php -l app/Http/Controllers/TicketVerificationController.php`
- `php artisan route:list | Select-String -Pattern 'profile|favorites|movies.favorite|bookings.history|tickets.checkin'`

Kết quả: không phát hiện lỗi cú pháp trong các file đã sửa. Route mới và route check-in đều xuất hiện trong route table.

### 2.2 Kiểm tra quyền truy cập check-in

- Route `/tickets/{bookingCode}/check-in` đã được bổ sung middleware `can:check-tickets`.
- Điều này đảm bảo chỉ nhân viên có quyền `check_tickets` mới có thể quét vé, thay vì chỉ lọc theo `admin` tổng quát.

### 2.3 Kiểm tra xung đột đồng thời

- `TicketVerificationController::checkIn()` đã dùng `DB::transaction()` + `lockForUpdate()` để khóa booking trước khi cập nhật trạng thái vé.
- `BookingController::processPayment()` đã được cập nhật để khóa booking trước khi xử lý thanh toán mock, giúp tránh trường hợp nhiều request cùng lúc xác nhận cùng một booking.

## 3. Vấn đề bảo mật và kiểm sốt đã khắc phục

### 3.1 Quản lý quyền truy cập check-in

Trước đây, route check-in chỉ cần `auth + active + admin`, nghĩa là bất kỳ nhân viên admin nào cũng có thể quét vé, dù không phải nhân viên soát vé. Điều này vi phạm nguyên tắc phân quyền theo vai trò.

Sau khi sửa:

- `can:check-tickets` đã được yêu cầu tại route
- `User::hasPermission('check_tickets')` là nguồn xác thực chính
- Điều này phù hợp với mô hình role dựa trên gate đã có từ phần Giai đoạn 5

### 3.2 Xung đột đồng thời khi quét vé

Hai nhân viên hoặc hai request cùng lúc có thể nhìn thấy cùng một booking và cùng cập nhật `ticket_status = used`.

Sau khi sửa:

- `lockForUpdate()` khóa hàng booking trong transaction
- Nếu đã sử dụng, request không cập nhật thêm
- Nếu chưa, chỉ một request được phép cập nhật và ghi nhận check-in.

### 3.3 Xung đột đồng thời khi thanh toán

Một booking có thể nhận nhiều request thanh toán cùng lúc, dẫn đến xác nhận trùng hoặc ghi điểm tích lũy nhiều lần.

Sau khi sửa:

- Booking được khóa ở đầu xử lý payment
- Xử lý status và cập nhật điểm chỉ thực hiện đúng một lần cho một booking đang ở trạng thái pending

## 4. Ràng buộc hệ thống vẫn cần xác minh trên môi trường có DB

Các kiểm thử DB-backed có thể chạy đầy đủ chỉ khi cơ sở dữ liệu MySQL thật đang sẵn sàng. Hiện tại môi trường này đang thiếu DB service, nên các test tích hợp bị chặn bởi:

- `SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it`

Do đó, các kiểm thử dưới đây cần được chạy trên môi trường dev/staging có MySQL hoặc Laravel Sail/docker:

- `php artisan test --filter=BookingSeatValidationTest`
- `php artisan test --filter=TicketVerificationTest` (nếu bổ sung trong tương lai)
- `php artisan test --filter=AdminPermissionTest`
- `php artisan test --filter=BookingConcurrencyTest`

## 5. Kết luận

Giai đoạn 7 đã thực hiện kiểm tra thiết yếu về "QA + bảo mật" cho phần cực kỳ nhạy cảm của hệ thống: quyền truy cập, check-in vé và thanh toán mock. Các khắc phục chính đã được triển khai ở tầng route và controller, đồng thời lưu lại báo cáo trong dự án để theo dõi.

Mức độ an toàn hiện tại đã tăng đáng kể nhưng vẫn cần kiểm thử DB-backed thực tế trên môi trường có MySQL để xác nhận hoàn toàn.
