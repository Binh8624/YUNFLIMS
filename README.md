# YUNFLIMS Cinema Booking

Website đặt vé xem phim Laravel 12 sử dụng Blade và Bootstrap. Hệ thống hỗ trợ duyệt phim/lịch chiếu, chọn ghế, giữ ghế tạm thời, thanh toán qua VNPay/MoMo khi đã cấu hình merchant, vé điện tử và khu vực quản trị.

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
- Tạo đơn đặt vé và thanh toán online qua VNPay/MoMo khi đã cấu hình thông tin merchant
- Giữ ghế tối đa 10 phút trong lúc thanh toán; ghế hết hạn được giải phóng khi có yêu cầu đặt mới
- Thanh toán mô phỏng chỉ bật thủ công cho phát triển; mặc định tắt và không dùng cho production
- Soát vé bằng camera QR hoặc nhập mã thủ công; backend kiểm tra thanh toán, trạng thái vé, thời gian suất chiếu và quyền theo rạp trước khi ghi lịch sử check-in
- QR được tạo động cho booking hợp lệ, chỉ chứa mã booking và không phụ thuộc domain
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

## Cấu hình thanh toán VNPay và MoMo

Thanh toán mô phỏng được tắt mặc định. Để dùng sandbox, tạo tài khoản test với nhà cung cấp và điền thông tin merchant vào `.env`; không đưa access key hoặc secret vào Git hay chat.

- VNPay: bật `VNPAY_ENABLED=true`, đặt `VNPAY_TMN_CODE` và `VNPAY_HASH_SECRET`.
- MoMo: bật `MOMO_ENABLED=true`, đặt `MOMO_PARTNER_CODE`, `MOMO_ACCESS_KEY` và `MOMO_SECRET_KEY`.
- MoMo mặc định dùng endpoint sandbox. `APP_URL` và URL IPN phải truy cập được từ MoMo; môi trường thật cần domain HTTPS công khai và endpoint production do nhà cung cấp cấp.
- Nếu chưa cấu hình đủ thông tin merchant, cổng đó không xuất hiện và hệ thống không tự xác nhận thanh toán.
- Hoàn tiền MoMo tự động chưa được tích hợp; yêu cầu hoàn tiền MoMo cần xử lý trên cổng merchant. VNPay tiếp tục dùng luồng hoàn tiền hiện có.

Chỉ bật `PAYMENT_MOCK_ENABLED=true` trong môi trường phát triển khi cần kiểm thử luồng mô phỏng. Thanh toán production cần hợp đồng/tài khoản merchant và cấu hình endpoint production chính thức.

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

## Soát vé, email và tác vụ nền

- Nhân viên có quyền `check_tickets` mở trang quét tại `/tickets/scan` hoặc từ dashboard. Camera cần HTTPS trên môi trường thật (localhost được trình duyệt xem là an toàn); có thể chuyển sang nhập mã thủ công nếu không cấp được quyền camera.
- QR chỉ hỗ trợ tra cứu; nhân viên phải xác nhận check-in và backend kiểm tra lại vé. Mã QR không chứa thông tin người mua.
- Booking thanh toán thành công, hủy và hoàn tiền tạo email qua bảng `email_deliveries`; email được khử trùng theo khóa idempotency và có thể theo dõi lỗi qua `status`, `attempts`, `last_error`. Đăng ký/đặt lại mật khẩu/xác nhận email sử dụng notification queued.
- Password reset dùng token database, hết hạn sau 60 phút và được giới hạn tần suất gửi. Email verification có link ký số hết hạn sau 60 phút; hệ thống chưa bắt buộc xác minh để đăng nhập nhằm giữ nguyên luồng tài khoản hiện có.

## Kiểm thử đã chạy

Kiểm tra cục bộ:

```bash
php artisan route:list
php artisan migrate:status
php artisan test --compact
```

`endroid/qr-code` tạo QR tiêu chuẩn ở định dạng SVG. Composer cần cài dependencies theo `composer.lock`.

## Triển khai scheduler, queue và email

Chạy migration bổ sung trước khi khởi động worker:

```bash
php artisan migrate --force
```

Thiết lập SMTP từ nhà cung cấp bạn chọn trong `.env` (`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`). Không đưa thông tin xác thực vào mã nguồn. Ứng dụng từ chối khởi động ở production nếu mailer là `log` hoặc `array`. Sau khi đổi cấu hình:

```bash
php artisan config:cache
php artisan queue:restart
```

Queue mặc định dùng database; migration hiện có tạo bảng `jobs` và `failed_jobs`. Chạy worker liên tục, với timeout của job 60 giây và `retry_after` 90 giây:

```bash
php artisan queue:work database --queue=default --sleep=3 --tries=5 --timeout=60 --max-time=3600
```

Trên Linux có thể quản lý worker bằng Supervisor, ví dụ:

```ini
[program:yunflims-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/yunflims/artisan queue:work database --queue=default --sleep=3 --tries=5 --timeout=60 --max-time=3600
directory=/var/www/yunflims
user=www-data
numprocs=1
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stopwaitsecs=70
redirect_stderr=true
stdout_logfile=/var/www/yunflims/storage/logs/queue-worker.log
```

Nạp cấu hình và xác nhận worker hoạt động:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status yunflims-queue:*
```

Theo dõi `php artisan queue:failed`; dùng `php artisan queue:retry all` sau khi xử lý nguyên nhân lỗi. Worker tự khởi động lại nhờ `autorestart=true`; triển khai code mới cần chạy `php artisan queue:restart`.

Đặt cron trên server để scheduler chạy kể cả khi không có người truy cập website:

```cron
* * * * * cd /var/www/yunflims && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler đối soát payment và giải phóng booking giữ ghế hết hạn mỗi phút. `withoutOverlapping` ngăn chạy chồng lặp trên một máy; `onOneServer` yêu cầu cache store dùng chung giữa các máy nếu triển khai nhiều instance.

## Lưu ý triển khai production

- Chỉ bật cổng VNPay/MoMo sau khi có tài khoản merchant, URL HTTPS/IPN công khai và cấu hình endpoint production; không dùng sandbox hoặc mock để nhận tiền production.
- Cấu hình SMTP thực tế và worker queue theo hướng dẫn ở trên; không dùng mailer `log` khi triển khai.
- Xem xét chính sách QR xác minh công khai, HTTPS, URL ứng dụng, cookie/session bảo mật, rate limit và logging trước khi triển khai.
- Cấu hình `APP_ENV=production`, `APP_DEBUG=false`, mail, cache, queue và URL ứng dụng; chạy `php artisan optimize` sau khi cấu hình.
- Backup database trước mọi thay đổi schema production; áp dụng migration đã rà soát bằng `php artisan migrate --force`.
