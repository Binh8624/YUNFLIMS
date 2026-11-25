
@extends('layouts.app')

@section('title', 'Phân loại độ tuổi phim - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="mx-auto" style="max-width:1000px;">
        <span class="badge bg-primary mb-3">YUNFLIMS</span>
        <h1 class="text-white fw-bold mb-3">Phân loại độ tuổi phim</h1>
        <p class="text-secondary mb-4">Tìm hiểu các mức phân loại độ tuổi trước khi lựa chọn bộ phim phù hợp.</p>

        <div class="card border-0 shadow-lg mb-4" style="background:#171525;color:#e5e3ee;">
            <div class="card-body p-4">
                <p>Phim chiếu tại Việt Nam được phân loại theo mức độ phù hợp với người xem. Người dùng cần kiểm tra nhãn phân loại hiển thị tại thông tin phim trước khi đặt vé.</p>
                <p class="mb-0">Các mức phân loại dưới đây được trình bày để người dùng dễ nhận biết:</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card h-100 border-0" style="background:#171525;color:#e5e3ee;">
                    <div class="card-body p-4">
                        <span class="badge bg-success mb-3">P</span>
                        <h5 class="fw-bold">Phim được phép phổ biến đến mọi độ tuổi</h5>
                        <p class="text-secondary mb-0">Phim phù hợp với mọi đối tượng khán giả.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 border-0" style="background:#171525;color:#e5e3ee;">
                    <div class="card-body p-4">
                        <span class="badge bg-info mb-3">K</span>
                        <h5 class="fw-bold">Phim dành cho trẻ em</h5>
                        <p class="text-secondary mb-0">Trẻ em dưới 13 tuổi khi xem phải có người giám hộ đi cùng.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 border-0" style="background:#171525;color:#e5e3ee;">
                    <div class="card-body p-4">
                        <span class="badge bg-warning text-dark mb-3">T13</span>
                        <h5 class="fw-bold">Phim dành cho khán giả từ đủ 13 tuổi trở lên</h5>
                        <p class="text-secondary mb-0">Không phổ biến đến khán giả dưới 13 tuổi.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 border-0" style="background:#171525;color:#e5e3ee;">
                    <div class="card-body p-4">
                        <span class="badge bg-warning text-dark mb-3">T16</span>
                        <h5 class="fw-bold">Phim dành cho khán giả từ đủ 16 tuổi trở lên</h5>
                        <p class="text-secondary mb-0">Không phổ biến đến khán giả dưới 16 tuổi.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 border-0" style="background:#171525;color:#e5e3ee;">
                    <div class="card-body p-4">
                        <span class="badge bg-danger mb-3">T18</span>
                        <h5 class="fw-bold">Phim dành cho khán giả từ đủ 18 tuổi trở lên</h5>
                        <p class="text-secondary mb-0">Không phổ biến đến khán giả dưới 18 tuổi.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 border-0" style="background:#171525;color:#e5e3ee;">
                    <div class="card-body p-4">
                        <span class="badge bg-secondary mb-3">C</span>
                        <h5 class="fw-bold">Phim không được phép phổ biến</h5>
                        <p class="text-secondary mb-0">Phim không được phép phát hành, phổ biến theo quyết định phân loại.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 mt-4" style="background:#171525;color:#e5e3ee;">
            <div class="card-body p-4">
                <h5 class="fw-bold">Lưu ý dành cho khán giả</h5>
                <ul class="mb-0">
                    <li>Hãy kiểm tra nhãn phân loại trước khi mua vé.</li>
                    <li>Người xem cần mang giấy tờ phù hợp để xác minh độ tuổi khi được yêu cầu.</li>
                    <li>Rạp chiếu có quyền từ chối phục vụ nếu người xem không đáp ứng điều kiện độ tuổi.</li>
                    <li>Thông tin phân loại chính thức của từng phim được ưu tiên áp dụng.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
