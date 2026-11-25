
@extends('layouts.app')

@section('title', 'Cơ hội nghề nghiệp - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="mx-auto" style="max-width:1000px;">
        <div class="text-center py-5">
            <span class="badge bg-primary mb-3">CAREERS</span>
            <h1 class="display-5 fw-bold text-white">Cùng tạo nên trải nghiệm điện ảnh</h1>
            <p class="text-secondary mx-auto mt-3" style="max-width:700px;">
                YUNFLIMS hướng đến việc xây dựng một nền tảng xem phim trực tuyến
                thuận tiện, hiện đại và thân thiện với người dùng.
            </p>
        </div>

        <div class="card border-0 mb-4" style="background:#171525;color:#e5e3ee;">
            <div class="card-body p-4 p-md-5">
                <h3 class="fw-bold mb-3">Về môi trường làm việc</h3>
                <p>YUNFLIMS là dự án nền tảng đặt vé xem phim trực tuyến, được xây dựng với định hướng ứng dụng công nghệ web vào hoạt động tra cứu phim, quản lý lịch chiếu và đặt vé.</p>
                <p class="mb-0">Trang nghề nghiệp được xây dựng nhằm giới thiệu định hướng phát triển và các lĩnh vực công việc có thể tham gia trong quá trình mở rộng hệ thống.</p>
            </div>
        </div>

        <h3 class="text-white fw-bold mb-4">Các lĩnh vực phát triển</h3>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card h-100 border-0" style="background:#171525;color:#e5e3ee;">
                    <div class="card-body p-4">
                        <div class="fs-2 mb-3">💻</div>
                        <h5 class="fw-bold">Phát triển Backend</h5>
                        <p class="text-secondary mb-0">Xây dựng và cải thiện các chức năng xử lý dữ liệu, tài khoản, đặt vé và quản lý hệ thống.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-0" style="background:#171525;color:#e5e3ee;">
                    <div class="card-body p-4">
                        <div class="fs-2 mb-3">🎨</div>
                        <h5 class="fw-bold">Phát triển Frontend</h5>
                        <p class="text-secondary mb-0">Thiết kế giao diện trực quan, thân thiện và tối ưu trải nghiệm sử dụng trên nhiều thiết bị.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-0" style="background:#171525;color:#e5e3ee;">
                    <div class="card-body p-4">
                        <div class="fs-2 mb-3">🧪</div>
                        <h5 class="fw-bold">Kiểm thử phần mềm</h5>
                        <p class="text-secondary mb-0">Kiểm tra chức năng, phát hiện lỗi và đảm bảo các luồng sử dụng hoạt động đúng yêu cầu.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-0" style="background:#171525;color:#e5e3ee;">
                    <div class="card-body p-4">
                        <div class="fs-2 mb-3">📋</div>
                        <h5 class="fw-bold">Quản lý dự án</h5>
                        <p class="text-secondary mb-0">Phân tích yêu cầu, lập kế hoạch công việc và phối hợp các thành viên trong quá trình phát triển.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 mt-4 text-center" style="background:#211d35;color:#fff;">
            <div class="card-body p-4">
                <h4 class="fw-bold">Cảm ơn bạn đã quan tâm đến YUNFLIMS</h4>
                <p class="text-secondary mb-0">Thông tin tuyển dụng sẽ được cập nhật khi có nhu cầu nhân sự phù hợp.</p>
            </div>
        </div>
    </div>
</div>
@endsection
