
@extends('layouts.app')

@section('title', 'Chính sách bảo mật - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="mx-auto" style="max-width:1000px;">
        <span class="badge bg-primary mb-3">YUNFLIMS</span>
        <h1 class="text-white fw-bold mb-3">Chính sách bảo mật</h1>
        <p class="text-secondary mb-4">YUNFLIMS tôn trọng quyền riêng tư và chú trọng bảo vệ thông tin của người dùng.</p>

        <div class="card border-0 shadow-lg" style="background:#171525;color:#e5e3ee;">
            <div class="card-body p-4 p-md-5">
                <h5 class="fw-bold">1. Phạm vi áp dụng</h5>
                <p>Chính sách này giải thích cách YUNFLIMS thu thập, sử dụng, lưu trữ và bảo vệ thông tin phát sinh khi người dùng truy cập website, đăng ký tài khoản và sử dụng dịch vụ.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">2. Thông tin được thu thập</h5>
                <p>Tùy theo chức năng người dùng sử dụng, hệ thống có thể xử lý các thông tin sau:</p>
                <ul>
                    <li>Thông tin tài khoản như họ tên, tên đăng nhập, địa chỉ email và số điện thoại nếu được cung cấp.</li>
                    <li>Thông tin đặt vé gồm phim, suất chiếu, rạp, ghế, giá vé và trạng thái đơn hàng.</li>
                    <li>Thông tin sản phẩm bắp nước và các dịch vụ được đặt kèm.</li>
                    <li>Nội dung đánh giá, yêu thích và lịch sử giao dịch trên website.</li>
                    <li>Dữ liệu kỹ thuật cần thiết để vận hành và bảo vệ hệ thống.</li>
                </ul>

                <hr class="border-secondary">
                <h5 class="fw-bold">3. Mục đích sử dụng thông tin</h5>
                <p>Thông tin được sử dụng nhằm:</p>
                <ul>
                    <li>Tạo và quản lý tài khoản người dùng.</li>
                    <li>Xử lý đặt vé, giữ ghế và thanh toán.</li>
                    <li>Quản lý đơn hàng sản phẩm đi kèm.</li>
                    <li>Hiển thị lịch sử đặt vé và hỗ trợ chăm sóc người dùng.</li>
                    <li>Phát hiện hành vi gian lận và bảo vệ an toàn hệ thống.</li>
                    <li>Cải thiện giao diện, chức năng và chất lượng dịch vụ.</li>
                </ul>

                <hr class="border-secondary">
                <h5 class="fw-bold">4. Lưu trữ và bảo vệ dữ liệu</h5>
                <p>YUNFLIMS áp dụng các biện pháp kỹ thuật và quản lý phù hợp để hạn chế truy cập trái phép, mất mát hoặc thay đổi dữ liệu. Người dùng cũng cần tự bảo vệ thông tin đăng nhập và không cung cấp mật khẩu cho người khác.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">5. Chia sẻ thông tin</h5>
                <p>YUNFLIMS không chủ động mua bán thông tin cá nhân của người dùng. Thông tin chỉ được chia sẻ khi cần thiết để cung cấp dịch vụ, theo yêu cầu hợp pháp của cơ quan có thẩm quyền hoặc khi người dùng đồng ý.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">6. Thời gian lưu trữ</h5>
                <p>Thông tin được lưu trữ trong khoảng thời gian cần thiết để vận hành tài khoản, xử lý giao dịch, giải quyết yêu cầu và đáp ứng các nghĩa vụ liên quan. Dữ liệu có thể được xóa hoặc ẩn danh khi không còn cần thiết, trừ trường hợp phải tiếp tục lưu trữ theo quy định áp dụng.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">7. Quyền của người dùng</h5>
                <p>Người dùng có thể kiểm tra và cập nhật thông tin cá nhân thông qua chức năng tài khoản được cung cấp. Người dùng cũng có thể gửi yêu cầu liên quan đến việc truy cập, chỉnh sửa hoặc xử lý thông tin cá nhân theo quy định áp dụng.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">8. Cookie và dữ liệu kỹ thuật</h5>
                <p>Website có thể sử dụng cookie hoặc công nghệ tương tự để duy trì phiên đăng nhập, ghi nhớ lựa chọn và hỗ trợ hoạt động của các chức năng. Người dùng có thể quản lý cookie thông qua cài đặt trình duyệt, tuy nhiên việc tắt cookie có thể ảnh hưởng đến một số tính năng.</p>

                <hr class="border-secondary">
                <h5 class="fw-bold">9. Thay đổi chính sách</h5>
                <p class="mb-0">Chính sách có thể được cập nhật để phù hợp với hoạt động của hệ thống. Nội dung mới sẽ được công bố trên website YUNFLIMS.</p>
            </div>
        </div>
        <p class="text-secondary small mt-3">Chính sách này mô tả cách xử lý dữ liệu trong phạm vi hệ thống YUNFLIMS.</p>
    </div>
</div>
@endsection
