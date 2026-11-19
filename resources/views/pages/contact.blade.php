
@extends('layouts.app')

@section('title', 'Liên hệ - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="mx-auto" style="max-width:1000px;">
        <div class="text-center mb-5">
            <span class="badge bg-primary mb-3">CONTACT US</span>
            <h1 class="text-white fw-bold">Liên hệ với YUNFLIMS</h1>
            <p class="text-secondary">Chúng tôi luôn mong muốn cải thiện trải nghiệm của bạn.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-5">
                <div class="card h-100 border-0" style="background:#171525;color:#e5e3ee;">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-4">Thông tin hỗ trợ</h4>

                        <div class="d-flex gap-3 mb-4">
                            <div class="fs-3">📩</div>
                            <div>
                                <h6 class="fw-bold mb-1">Yêu cầu hỗ trợ</h6>
                                <p class="text-secondary mb-0">Bạn có thể gửi yêu cầu đến bộ phận quản trị thông qua kênh hỗ trợ được công bố trên hệ thống.</p>
                            </div>
                        </div>

                        <div class="d-flex gap-3 mb-4">
                            <div class="fs-3">🎟️</div>
                            <div>
                                <h6 class="fw-bold mb-1">Vấn đề đặt vé</h6>
                                <p class="text-secondary mb-0">Chuẩn bị mã đặt vé và thông tin suất chiếu để việc kiểm tra được thuận tiện.</p>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <div class="fs-3">🔒</div>
                            <div>
                                <h6 class="fw-bold mb-1">Bảo mật tài khoản</h6>
                                <p class="text-secondary mb-0">Không chia sẻ mật khẩu hoặc thông tin đăng nhập cho bất kỳ ai.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card h-100 border-0" style="background:#171525;color:#e5e3ee;">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-3">Bạn cần hỗ trợ?</h4>
                        <p class="text-secondary">Hãy tham khảo các nội dung hỗ trợ bên dưới để tìm hướng giải quyết phù hợp.</p>

                        <div class="p-3 rounded mb-3" style="background:#211d35;">
                            <h6 class="fw-bold">Tra cứu thông tin phim</h6>
                            <p class="text-secondary mb-2">Xem thông tin phim, thể loại, thời lượng và phân loại độ tuổi.</p>
                            <a href="{{ route('movies.index') }}" class="text-info text-decoration-none">Xem danh sách phim →</a>
                        </div>

                        <div class="p-3 rounded mb-3" style="background:#211d35;">
                            <h6 class="fw-bold">Tra cứu lịch chiếu</h6>
                            <p class="text-secondary mb-2">Kiểm tra rạp, ngày chiếu và suất chiếu đang được cung cấp.</p>
                            <a href="{{ route('showtimes.index') }}" class="text-info text-decoration-none">Xem lịch chiếu →</a>
                        </div>

                        <div class="p-3 rounded" style="background:#211d35;">
                            <h6 class="fw-bold">Câu hỏi thường gặp</h6>
                            <p class="text-secondary mb-2">Tìm hiểu cách đặt vé, thanh toán và quản lý đơn hàng.</p>
                            <a href="{{ route('pages.support') }}" class="text-info text-decoration-none">Đến trang hỗ trợ →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
