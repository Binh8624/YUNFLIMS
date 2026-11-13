# YUNFLIMS Cinema Booking

Website đặt vé xem phim Laravel 12 sử dụng Blade và Bootstrap. Hệ thống hỗ trợ duyệt phim/lịch chiếu, chọn ghế, giữ ghế tạm thời, thanh toán mô phỏng, vé điện tử và khu vực quản trị.

## Môi trường

- PHP 8.2+
- Laravel 12
- MySQL / MariaDB (giữ cấu hình hiện có trong `.env`)
- Bootstrap 5 + Blade

## Cài đặt nhanh

1. Sao chép `.env.example` sang `.env` nếu chưa có.
2. Cấu hình thông tin DB trong `.env`:
   - DB_CONNECTION=mysql
   - DB_HOST=127.0.0.1
   - DB_PORT=3306
   - DB_DATABASE=your_database
   - DB_USERNAME=your_user
   - DB_PASSWORD=your_password
3. Tạo key:
   ```bash
   php artisan key:generate
   ```
4. Chạy các migration bổ sung an toàn:
   ```bash
   php artisan migrate
   ```
   Lệnh này chỉ áp dụng migration còn thiếu, không xóa dữ liệu. Không dùng `migrate:fresh` hoặc `db:wipe` trên dữ liệu cần giữ. Chỉ chạy `php artisan db:seed` ở môi trường local khi thực sự muốn dữ liệu mẫu; các seeder mẫu từ chối chạy ở production.
   Migration định dạng thêm trường định dạng duy nhất cho phim và phòng, ưu tiên định dạng đang dùng nhiều nhất trong lịch chiếu hiện có rồi đến cấu hình cũ; dữ liệu ghế, lịch chiếu và vé không bị xóa. Migration bổ sung metadata phim đặt phân loại tuổi cũ ở trạng thái chưa xác minh và thêm cờ phim nổi bật, không tạo hoặc thay đổi phim/lịch chiếu.
5. Để bổ sung năm phòng mẫu cùng sơ đồ 60 ghế mà không tạo trùng, chạy:
   ```bash
   php artisan db:seed --class="Database\Seeders\CinemaSeeder"
   ```
   Seeder giữ nguyên phòng và sơ đồ ghế đã có; nếu một phòng mẫu đã tồn tại nhưng chưa có ghế, seeder bổ sung sơ đồ 60 ghế. Năm phòng mới được chia thành 2D, 3D và IMAX. Lệnh từ chối chạy ở production.
6. Chạy local server:
   ```bash
   php artisan serve
   ```

## Tài khoản demo

- Admin: tạo tài khoản với role `admin` trong database hoặc cập nhật trực tiếp `role` bằng SQL / tinker.
- Customer: đăng ký qua giao diện.

## Chức năng chính

- Trang chủ với banner, tìm kiếm và danh sách phim
- Trang chi tiết và danh sách phim dùng chung cách hiển thị thể loại, định dạng và trạng thái xác minh phân loại tuổi
- Trang lịch chiếu tổng hợp lọc theo ngày có suất thực tế, thành phố, rạp, phim, thể loại và định dạng; giờ chiếu được nhóm theo rạp/phim và liên kết đúng suất chiếu
- Chọn suất chiếu trong tương lai, xem giá theo ghế và chọn ghế theo sơ đồ phòng
- Sơ đồ ghế kiểm tra trạng thái đặt/giữ tức thời, thời hạn giữ 5 phút và quy tắc không để ghế lẻ theo từng hàng; máy chủ kiểm tra lại trong transaction trước khi tạo booking
- Tạo đơn đặt vé và thanh toán thử nghiệm
- Giữ ghế tối đa 10 phút trong lúc thanh toán; ghế hết hạn được giải phóng khi có yêu cầu đặt mới
- Thanh toán mô phỏng cho phép chọn kết quả thành công/thất bại; không thu thập thông tin thẻ và chưa kết nối cổng thật
- QR SVG tiêu chuẩn chứa liên kết xác minh vé; trang quét chỉ xác nhận đơn đã thanh toán, không hiển thị thông tin người mua
- Hồ sơ cá nhân và đổi mật khẩu
- Lịch sử đặt vé cho người dùng
- Dashboard admin, CRUD và tìm kiếm/phân trang cho phim, rạp, phòng, ghế, suất chiếu, định dạng, quy tắc giá, đánh giá, đơn vé, thanh toán và người dùng; lịch chiếu có thể lọc theo phim, ngày, rạp và phòng
- Tạo lịch hàng loạt có xem trước; hỗ trợ giữ cách chọn phòng thủ công hoặc tự động gán tối đa một phòng tương thích còn trống cho mỗi ngày/khung giờ. Hệ thống kiểm tra xung đột và khoảng nghỉ 15 phút, rồi tính lại trong transaction trước khi lưu
- Lệnh `showtimes:generate-test` tạo lịch thử nghiệm best-effort trong 7 ngày, chỉ dùng rạp/phòng đang hoạt động có định dạng tương thích; lệnh giữ nguyên lịch cũ, tránh trùng lặp, ghi log các lần bỏ qua và từ chối chạy trên production
- Mỗi phim và phòng có một định dạng cố định: 2D, 3D hoặc IMAX; lịch chiếu và đặt vé chỉ chấp nhận định dạng trùng khớp
- Phim nổi bật lấy từ đặt vé đã xác nhận/đánh giá thực tế; khi chưa có thống kê, quản trị viên có thể chọn phim nổi bật trong form phim. Không tự tạo chỉ số phổ biến.
- Phân loại tuổi P/K/T13/T16/T18/C chỉ hiển thị mã sau khi quản trị viên xác nhận; dữ liệu cũ được gắn cờ cần xác minh, không tự suy đoán từ tên hoặc thể loại. C bị loại khỏi danh mục và luồng đặt vé công khai.
- Thể loại nhiều nhãn được tách thành các thể loại ngắn từ dữ liệu hiện có để hiển thị/lọc; chuỗi thể loại gốc trong database được giữ nguyên.
- Giá suất chiếu được cấu hình riêng cho ghế thường, VIP và ghế đôi; quy tắc giá không cộng dồn: ưu tiên `priority` cao nhất, sau đó quy tắc cụ thể hơn, rồi ID thấp hơn; giá theo từng ghế được lưu tại thời điểm đặt
- Phân quyền admin ở middleware phía server, khóa/mở khóa tài khoản và giữ lịch sử đơn vé

## Dữ liệu phim mẫu

32 phim mẫu bổ sung dùng tên, thể loại, thời lượng và tóm tắt từ [TMDB 5000 Movie Dataset](https://www.kaggle.com/datasets/tmdb/tmdb-movie-metadata). Ảnh bìa trỏ tới ảnh phim trên Wikipedia/Wikimedia, không phải ảnh stock. Trạng thái `now_showing`/`coming_soon` được giữ theo dữ liệu demo hiện có, không phải lịch phát hành hoặc lịch chiếu đã xác minh; cần cập nhật trạng thái và tạo suất chiếu thực tế trước khi vận hành. Nội dung phim và poster thuộc quyền của chủ sở hữu tương ứng; cần xác minh quyền sử dụng hình ảnh và dữ liệu trước khi triển khai website production.

## Quản trị

Tài khoản có role `admin` truy cập `/admin`. Dashboard liên kết đến quản lý phim, đánh giá, rạp, phòng/ghế, định dạng, lịch chiếu/tạo lịch tự động, bảng giá, đơn vé, thanh toán và người dùng. Tài khoản bị khóa sẽ bị từ chối đăng nhập và phiên đang hoạt động sẽ bị đăng xuất ở request kế tiếp.

Không xóa được phim, rạp hoặc phòng còn liên kết dữ liệu lịch sử; hãy chuyển sang trạng thái tạm ngưng/ẩn để bảo toàn đơn vé và lịch chiếu.

## Tạo lịch chiếu thử nghiệm

Trong môi trường local hoặc testing, xem lại kế hoạch trước khi xác nhận:

```bash
php artisan showtimes:generate-test
```

Lệnh yêu cầu xác nhận, tạo lịch trong 7 ngày từ phim `now_showing`, và báo cáo số phim/rạp/phòng, suất đã tạo/bỏ qua, phim chưa có suất hợp lệ cùng thống kê theo ngày/rạp. Mục tiêu là 3–5 suất mỗi phim mỗi ngày, nhưng lịch được tạo best-effort: giới hạn giờ 08:00–23:00, định dạng phòng/phim, thời lượng, lịch có sẵn và thời gian vệ sinh 15 phút luôn được ưu tiên. Nếu không đủ công suất, lệnh ghi rõ nguyên nhân bỏ qua thay vì tạo lịch không hợp lệ. Không xóa/sửa lịch cũ. Có thể dùng `php artisan showtimes:generate-test --force` để bỏ qua xác nhận tương tác; lệnh vẫn từ chối môi trường production.

## Giao diện & bảo mật

- Responsive trên desktop/tablet/mobile
- Error pages tùy chỉnh cho 403, 404, 500
- CSRF được giữ trong form Laravel
- Validation và kiểm tra quyền người dùng
- Không lưu dữ liệu thẻ nhạy cảm trong hệ thống

## Kiểm thử đã chạy

Kiểm tra cục bộ:

```bash
php artisan route:list
php artisan migrate:status
php artisan test --compact
```

`endroid/qr-code` tạo QR tiêu chuẩn ở định dạng SVG. Composer cần cài dependencies theo `composer.lock`.

## Lưu ý triển khai production

- Thanh toán hiện chỉ là mô phỏng. Trước khi nhận thanh toán thật, tích hợp cổng thanh toán, xác minh chữ ký callback/webhook, idempotency, hoàn tiền và đối soát; không dùng lựa chọn mô phỏng trong production.
- Cấu hình mail và SMTP nếu muốn gửi email xác nhận.
- Xem xét chính sách QR xác minh công khai, HTTPS, URL ứng dụng, cookie/session bảo mật, rate limit và logging trước khi triển khai.
- Cấu hình `APP_ENV=production`, `APP_DEBUG=false`, mail, cache, queue và URL ứng dụng; chạy `php artisan optimize` sau khi cấu hình.
- Backup database trước mọi thay đổi schema production; áp dụng migration đã rà soát bằng `php artisan migrate --force`.
