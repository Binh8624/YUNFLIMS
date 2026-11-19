
@extends('layouts.app')

@section('title', 'Điều khoản sử dụng - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="mx-auto" style="max-width: 1000px;">
        <span class="badge bg-primary mb-3">YUNFLIMS</span>
        <h1 class="text-white fw-bold mb-3">Điều khoản sử dụng</h1>
        <p class="text-secondary mb-4">Vui lòng đọc kỹ các điều khoản trước khi sử dụng dịch vụ.</p>

        <div class="card border-0 shadow-lg" style="background:#171525;color:#e5e3ee;">
            <div class="card-body p-4 p-md-5">
                <h5 class="fw-bold">1. Giới thiệu</h5>
                <p>YUNFLIMS là nền tảng hỗ trợ người dùng tra cứu thông tin phim, lịch chiếu, lựa chọn rạp, đặt vé xem phim và sử dụng các dịch vụ đi kèm. Khi truy cập website, bạn xác nhận đã đọc và đồng ý với các điều khoản này.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">2. Tài khoản người dùng</h5>
                <p>Người dùng cần cung cấp thông tin chính xác khi đăng ký tài khoản. Bạn có trách nhiệm bảo mật mật khẩu, không chia sẻ tài khoản cho người khác và thông báo kịp thời khi phát hiện dấu hiệu truy cập trái phép.</p>
                <p>YUNFLIMS có quyền tạm ngừng quyền truy cập đối với tài khoản có dấu hiệu gian lận, giả mạo hoặc vi phạm quy định sử dụng.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">3. Đặt vé và giữ chỗ</h5>
                <p>Người dùng cần kiểm tra chính xác phim, rạp, phòng chiếu, ngày giờ chiếu và vị trí ghế trước khi xác nhận đặt vé. Ghế được giữ tạm thời trong khoảng thời gian do hệ thống quy định.</p>
                <p>Đơn đặt vé chưa hoàn tất thanh toán có thể tự động hết hạn. Khi đó, ghế đã giữ sẽ được giải phóng để phục vụ người dùng khác.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">4. Thanh toán</h5>
                <p>Người dùng cần thực hiện thanh toán theo hướng dẫn được hiển thị tại bước thanh toán. Đơn đặt vé chỉ được xác nhận sau khi hệ thống ghi nhận thanh toán thành công.</p>
                <p>Người dùng cần lưu lại thông tin đặt vé để thuận tiện cho việc tra cứu và sử dụng vé tại rạp.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">5. Sản phẩm bắp nước và dịch vụ bổ sung</h5>
                <p>Sản phẩm bắp nước được lựa chọn trong quá trình đặt vé sẽ được ghi nhận cùng đơn đặt vé. Số lượng sản phẩm phụ thuộc vào tình trạng hàng hóa thực tế được hiển thị trên hệ thống.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">6. Quy định sử dụng</h5>
                <ul>
                    <li>Không sử dụng website cho mục đích vi phạm pháp luật.</li>
                    <li>Không cố ý can thiệp, phá hoại hoặc gây gián đoạn hệ thống.</li>
                    <li>Không khai thác lỗi hệ thống để chiếm đoạt quyền lợi.</li>
                    <li>Không sử dụng thông tin hoặc tài khoản của người khác khi chưa được cho phép.</li>
                    <li>Không đăng tải nội dung xúc phạm, sai sự thật hoặc vi phạm quyền của bên thứ ba.</li>
                </ul>

                <hr class="border-secondary">
                <h5 class="fw-bold">7. Quyền và trách nhiệm của YUNFLIMS</h5>
                <p>YUNFLIMS nỗ lực duy trì hoạt động ổn định, bảo vệ thông tin người dùng và cung cấp thông tin dịch vụ rõ ràng. Hệ thống có thể được bảo trì hoặc cập nhật để cải thiện chất lượng dịch vụ.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">8. Thay đổi điều khoản</h5>
                <p>Điều khoản có thể được điều chỉnh khi cần thiết. Phiên bản cập nhật sẽ được công bố trên website. Người dùng nên thường xuyên kiểm tra để nắm được các quy định hiện hành.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">9. Hiệu lực</h5>
                <p class="mb-0">Các điều khoản này áp dụng đối với hoạt động truy cập và sử dụng website YUNFLIMS. Nếu có thắc mắc, người dùng có thể tham khảo trang Hỗ trợ hoặc Liên hệ.</p>
            </div>
        </div>
        <p class="text-secondary small mt-3">Nội dung điều khoản dành cho hệ thống YUNFLIMS trong phạm vi website.</p>
    </div>
</div>
@endsection
