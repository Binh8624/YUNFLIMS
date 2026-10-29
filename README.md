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
   Lệnh này chỉ áp dụng migration còn thiếu, không xóa dữ liệu. Không dùng `migrate:fresh` hoặc `db:wipe` trên dữ liệu cần giữ. Chỉ chạy `php artisan db:seed` ở môi trường local khi thực sự muốn dữ liệu mẫu; seeder tạo tài khoản mẫu và không nên chạy lặp trên production.
5. Chạy local server:
   ```bash
   php artisan serve
   ```

## Tài khoản demo

- Admin: tạo tài khoản với role `admin` trong database hoặc cập nhật trực tiếp `role` bằng SQL / tinker.
- Customer: đăng ký qua giao diện.

## Chức năng chính

- Trang chủ với banner, tìm kiếm và danh sách phim
- Chi tiết phim kèm lịch chiếu
- Lịch chiếu lọc theo ngày/rạp, định dạng phim, thời lượng, phân loại độ tuổi, trailer và đánh giá người dùng
- Chọn suất chiếu trong tương lai, xem giá theo ghế và chọn ghế theo sơ đồ phòng
- Tạo đơn đặt vé và thanh toán thử nghiệm
- Giữ ghế tối đa 10 phút trong lúc thanh toán; ghế hết hạn được giải phóng khi có yêu cầu đặt mới
- Thanh toán mô phỏng cho phép chọn kết quả thành công/thất bại; không thu thập thông tin thẻ và chưa kết nối cổng thật
- QR SVG tiêu chuẩn chứa liên kết xác minh vé; trang quét chỉ xác nhận đơn đã thanh toán, không hiển thị thông tin người mua
- Hồ sơ cá nhân và đổi mật khẩu
- Lịch sử đặt vé cho người dùng
- Dashboard admin, CRUD và tìm kiếm/phân trang cho phim, rạp, phòng, ghế, suất chiếu, định dạng, quy tắc giá, đánh giá, đơn vé, thanh toán và người dùng
- Tạo lịch tự động có xem trước, kiểm tra tương thích định dạng và khoảng vệ sinh 15 phút
- Quy tắc giá không cộng dồn: ưu tiên `priority` cao nhất, sau đó quy tắc cụ thể hơn, rồi ID thấp hơn; giá theo từng ghế được lưu tại thời điểm đặt
- Phân quyền admin ở middleware phía server, khóa/mở khóa tài khoản và giữ lịch sử đơn vé

## Dữ liệu phim mẫu

32 phim mẫu bổ sung dùng tên, thể loại, thời lượng và tóm tắt từ [TMDB 5000 Movie Dataset](https://www.kaggle.com/datasets/tmdb/tmdb-movie-metadata). Ảnh bìa trỏ tới ảnh phim trên Wikipedia/Wikimedia, không phải ảnh stock. Trạng thái `now_showing`/`coming_soon` được giữ theo dữ liệu demo hiện có, không phải lịch phát hành hoặc lịch chiếu đã xác minh; cần cập nhật trạng thái và tạo suất chiếu thực tế trước khi vận hành. Nội dung phim và poster thuộc quyền của chủ sở hữu tương ứng; cần xác minh quyền sử dụng hình ảnh và dữ liệu trước khi triển khai website production.

## Quản trị

Tài khoản có role `admin` truy cập `/admin`. Dashboard liên kết đến quản lý phim, đánh giá, rạp, phòng/ghế, định dạng, lịch chiếu/tạo lịch tự động, bảng giá, đơn vé, thanh toán và người dùng. Tài khoản bị khóa sẽ bị từ chối đăng nhập và phiên đang hoạt động sẽ bị đăng xuất ở request kế tiếp.

Không xóa được phim, rạp hoặc phòng còn liên kết dữ liệu lịch sử; hãy chuyển sang trạng thái tạm ngưng/ẩn để bảo toàn đơn vé và lịch chiếu.

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
