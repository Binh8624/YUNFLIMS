
@extends('layouts.app')

@section('title', 'Giới thiệu - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="mx-auto" style="max-width:1100px;">
        <div class="text-center py-5">
            <span class="badge bg-primary mb-3">WELCOME TO YUNFLIMS</span>
            <h1 class="display-4 fw-bold text-white">Thế giới điện ảnh<br><span style="color:#a78bfa;">trong tầm tay bạn</span></h1>
            <p class="text-secondary mx-auto mt-3" style="max-width:700px;">
                YUNFLIMS mang đến trải nghiệm tra cứu phim, lựa chọn lịch chiếu
                và đặt vé trực tuyến thuận tiện trên cùng một nền tảng.
            </p>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card h-100 border-0 text-center" style="background:#171525;color:#fff;">
                    <div class="card-body p-4">
                        <div class="fs-1 mb-3">🎬</div>
                        <h5 class="fw-bold">Khám phá phim</h5>
                        <p class="text-secondary mb-0">Cập nhật thông tin phim, thể loại, nội dung và phân loại độ tuổi để lựa chọn tác phẩm phù hợp.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 text-center" style="background:#171525;color:#fff;">
                    <div class="card-body p-4">
                        <div class="fs-1 mb-3">🎟️</div>
                        <h5 class="fw-bold">Đặt vé tiện lợi</h5>
                        <p class="text-secondary mb-0">Chọn rạp, suất chiếu, phòng chiếu và vị trí ghế trực tiếp trên website.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 text-center" style="background:#171525;color:#fff;">
                    <div class="card-body p-4">
                        <div class="fs-1 mb-3">🍿</div>
                        <h5 class="fw-bold">Dịch vụ đi kèm</h5>
                        <p class="text-secondary mb-0">Lựa chọn bắp nước cùng vé xem phim để chuẩn bị cho buổi thưởng thức điện ảnh.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 mb-4" style="background:#171525;color:#e5e3ee;">
            <div class="card-body p-4 p-md-5">
                <h3 class="fw-bold mb-3">Về YUNFLIMS</h3>
                <p>YUNFLIMS được xây dựng với mục tiêu tạo ra một nền tảng đặt vé xem phim trực tuyến thân thiện, dễ sử dụng và có đầy đủ các chức năng cần thiết cho người yêu điện ảnh.</p>
                <p>Thông qua website, người dùng có thể tìm hiểu các bộ phim, theo dõi lịch chiếu, lựa chọn ghế ngồi, đặt vé, thanh toán và quản lý lịch sử giao dịch.</p>
                <p class="mb-0">Bên cạnh trải nghiệm dành cho khách hàng, hệ thống còn hỗ trợ quản trị viên quản lý phim, rạp chiếu, phòng chiếu, suất chiếu, đơn đặt vé và các dịch vụ liên quan.</p>
            </div>
        </div>

        <div class="text-center py-4">
            <h3 class="text-white fw-bold">Sẵn sàng cho bộ phim tiếp theo?</h3>
            <p class="text-secondary">Khám phá những bộ phim đang được trình chiếu tại YUNFLIMS.</p>
            <a href="{{ route('movies.index') }}" class="btn btn-primary px-4 py-2 mt-2">
                Khám phá phim ngay
            </a>
        </div>
    </div>
</div>
@endsection
