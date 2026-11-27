
@extends('layouts.app')

@section('title', 'Chính sách hủy vé và hoàn tiền - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="mx-auto" style="max-width:1000px;">
        <span class="badge bg-primary mb-3">YUNFLIMS</span>
        <h1 class="text-white fw-bold mb-3">Chính sách hủy vé và hoàn tiền</h1>
        <p class="text-secondary mb-4">Quy định về việc hủy đặt vé, xử lý đơn hàng và hoàn tiền.</p>

        <div class="card border-0 shadow-lg" style="background:#171525;color:#e5e3ee;">
            <div class="card-body p-4 p-md-5">
                <h5 class="fw-bold">1. Nguyên tắc chung</h5>
                <p>Mỗi đơn đặt vé được quản lý theo mã đơn và trạng thái giao dịch. Người dùng cần kiểm tra thông tin trước khi thanh toán. Việc hủy vé và hoàn tiền được xử lý theo trạng thái đơn hàng và khả năng hỗ trợ của hệ thống.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">2. Đơn đặt vé chưa thanh toán</h5>
                <p>Đơn đặt vé chưa thanh toán có thể được hủy theo chức năng được cung cấp. Nếu thời gian giữ ghế kết thúc mà chưa hoàn tất thanh toán, hệ thống có thể tự động hủy đơn và giải phóng ghế.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">3. Đơn đặt vé đã thanh toán</h5>
                <p>Đối với đơn đã thanh toán, người dùng thực hiện yêu cầu hủy thông qua chức năng được hệ thống cung cấp nếu đơn còn đủ điều kiện. Yêu cầu sẽ được kiểm tra dựa trên trạng thái vé, thời gian chiếu và trạng thái xử lý sản phẩm đi kèm.</p>
                <p>Việc ghi nhận hủy đơn không đồng nghĩa với việc tiền đã được hoàn ngay lập tức. Kết quả và trạng thái hoàn tiền cần được xác nhận riêng trong hệ thống.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">4. Sản phẩm bắp nước</h5>
                <p>Sản phẩm bắp nước được đặt cùng vé sẽ được xử lý theo trạng thái đơn hàng. Nếu đơn hàng đã chuyển sang trạng thái đang chuẩn bị hoặc hoàn tất, yêu cầu hủy có thể không được chấp nhận đối với phần sản phẩm đó.</p>
                <p>Việc hoàn kho sản phẩm khi hủy đơn được hệ thống xử lý theo trạng thái giao dịch tương ứng.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">5. Trường hợp không thể hủy</h5>
                <ul>
                    <li>Đơn hàng đã hoàn tất hoặc đã sử dụng vé.</li>
                    <li>Đơn hàng đã chuyển sang trạng thái không cho phép hủy.</li>
                    <li>Sản phẩm đi kèm đã được xử lý đến giai đoạn không thể thu hồi.</li>
                    <li>Các trường hợp khác được hệ thống thông báo là không đủ điều kiện.</li>
                </ul>

                <hr class="border-secondary">
                <h5 class="fw-bold">6. Quy trình xử lý hoàn tiền</h5>
                <p>Khi yêu cầu hoàn tiền được chấp nhận, trạng thái xử lý sẽ được cập nhật theo quy trình giao dịch. Người dùng nên lưu lại mã đặt vé và thông tin thanh toán để phục vụ việc kiểm tra khi cần thiết.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">7. Sai sót giao dịch</h5>
                <p>Nếu phát sinh trường hợp thanh toán thành công nhưng đơn chưa được cập nhật, thanh toán trùng hoặc số tiền không chính xác, người dùng nên lưu lại bằng chứng giao dịch và liên hệ bộ phận hỗ trợ để được kiểm tra.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">8. Lưu ý</h5>
                <p class="mb-0">Chính sách này mô tả nguyên tắc xử lý trong hệ thống YUNFLIMS. Quy trình hoàn tiền thực tế phụ thuộc vào phương thức thanh toán và trạng thái giao dịch được hệ thống hỗ trợ.</p>
            </div>
        </div>
    </div>
</div>
@endsection
