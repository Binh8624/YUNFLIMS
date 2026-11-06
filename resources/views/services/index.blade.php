@extends('layouts.app')

@section('title', 'Dịch vụ - YUNFLIMS Cinema')

@push('styles')
<style>
    .service-hero {
        background: linear-gradient(120deg, #501016, #211116);
        border-radius: 18px;
        padding: 3rem;
    }

    .service-card {
        background: var(--ys-card);
        border: 1px solid #303039;
        border-radius: 14px;
        height: 100%;
        transition: transform .25s ease, border-color .25s ease;
    }

    .service-card:hover {
        transform: translateY(-6px);
        border-color: var(--ys-red);
    }

    .service-icon {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        color: #fff;
        background: var(--ys-red);
    }

    .service-card .card-link {
        color: var(--ys-red);
        font-weight: 700;
        text-decoration: none;
    }

    .service-card .card-link:hover {
        color: #fff;
    }
</style>
@endpush

@section('content')
<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Trang chủ</a></li>
            <li class="breadcrumb-item active text-light" aria-current="page">Dịch vụ</li>
        </ol>
    </nav>

    <section class="service-hero mb-5">
        <span class="badge badge-red mb-3 px-3 py-2">DỊCH VỤ YUNFLIMS</span>
        <h1 class="fw-bold mb-3">Quà tặng &amp; ưu đãi điện ảnh</h1>
        <p class="text-light mb-0" style="max-width: 640px;">
            Chọn voucher xem phim hoặc thẻ quà tặng để dành tặng người thân, bạn bè.
            Mỗi tấm vé là một trải nghiệm đáng nhớ tại YUNFLIMS.
        </p>
    </section>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="service-card p-4 p-lg-5 d-flex flex-column">
                <div class="service-icon mb-4"><i class="bi bi-ticket-perforated"></i></div>
                <h3 class="fw-bold">Voucher phim</h3>
                <p class="text-secondary flex-grow-1">
                    Voucher 2D, 3D, IMAX, 4DX, Gold Class và L'AMOUR với mức giá linh hoạt.
                    Phù hợp làm quà tặng hoặc tích lũy cho những buổi xem phim tiếp theo.
                </p>
                <a href="{{ route('services.vouchers') }}" class="card-link mt-2">
                    Xem danh sách voucher <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="service-card p-4 p-lg-5 d-flex flex-column">
                <div class="service-icon mb-4"><i class="bi bi-gift"></i></div>
                <h3 class="fw-bold">Thẻ quà tặng</h3>
                <p class="text-secondary flex-grow-1">
                    Thẻ quà tặng YUNFLIMS với nhiều mẫu thiết kế rực rỡ.
                    Tặng thẻ để người nhận tự chọn bộ phim và suất chiếu yêu thích.
                </p>
                <a href="{{ route('services.gift-cards') }}" class="card-link mt-2">
                    Khám phá thẻ quà tặng <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <section class="mt-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="service-card p-4 text-center">
                    <i class="bi bi-lightning-charge feature-icon"></i>
                    <h5 class="mt-3">Sử dụng nhanh chóng</h5>
                    <p class="text-secondary mb-0 small">
                        Xuất trình mã voucher tại quầy vé để đổi vé xem phim.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card p-4 text-center">
                    <i class="bi bi-calendar-check feature-icon"></i>
                    <h5 class="mt-3">Hiệu lực dài</h5>
                    <p class="text-secondary mb-0 small">
                        Voucher có thời hạn sử dụng lên đến 12 tháng kể từ ngày mua.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card p-4 text-center">
                    <i class="bi bi-headset feature-icon"></i>
                    <h5 class="mt-3">Hỗ trợ tận tâm</h5>
                    <p class="text-secondary mb-0 small">
                        Cần tư vấn? Liên hệ hotline hoặc email support@yunflims.example.
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
