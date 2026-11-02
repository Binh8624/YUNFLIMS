@extends('layouts.app')

@section('title', 'Thẻ quà tặng - YUNFLIMS Cinema')

@push('styles')
<style>
    .page-title {
        border-bottom: 2px solid #303039;
        padding-bottom: 14px;
    }

    .wallet-empty {
        background: var(--ys-card);
        border: 1px dashed #3d3d47;
        border-radius: 16px;
        padding: 3.5rem 1.5rem;
        text-align: center;
    }

    .gift-card-design {
        position: relative;
        border-radius: 14px;
        aspect-ratio: 16 / 9;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        overflow: hidden;
        box-shadow: 0 10px 26px rgba(0, 0, 0, .35);
        transition: transform .25s ease, box-shadow .25s ease;
    }

    .gift-card-design:hover {
        transform: translateY(-6px) scale(1.01);
        box-shadow: 0 16px 34px rgba(0, 0, 0, .5);
    }

    .gift-card-design .design-emoji {
        font-size: 42px;
        line-height: 1;
    }

    .gift-card-design .design-title {
        font-size: 24px;
        font-weight: 900;
        letter-spacing: .5px;
    }

    .gift-card-design .design-brand {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 3px;
        opacity: .75;
    }

    .section-rule {
        border-bottom: 1px solid #303039;
        padding-bottom: 12px;
    }

    .btn-outline-red {
        border: 1px solid var(--ys-red);
        color: var(--ys-red);
        font-weight: 700;
    }

    .btn-outline-red:hover {
        background: var(--ys-red);
        color: #fff;
    }

    .service-icon {
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: var(--ys-red);
    }
</style>
@endpush

@section('content')
<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('home') }}" class="text-decoration-none text-secondary">
                    <i class="bi bi-house-door"></i> Trang chủ
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('services.index') }}" class="text-decoration-none text-secondary">Dịch vụ</a>
            </li>
            <li class="breadcrumb-item active text-light" aria-current="page">Thẻ quà tặng</li>
        </ol>
    </nav>

    <h1 class="page-title fw-bold mb-4">Thẻ quà tặng</h1>

    <section class="wallet-empty mb-5">
        <i class="bi bi-wallet2 feature-icon d-block mb-3"></i>
        <h4 class="fw-bold mb-2">Bạn chưa có thẻ quà tặng nào</h4>
        <p class="text-secondary mb-4">
            Thêm thẻ quà tặng để lưu lại những món quà đã mua và sử dụng khi cần.
        </p>
        <a href="#bo-suu-tap" class="btn btn-red px-4 py-2">THÊM THẺ MỚI</a>
    </section>

    <section id="bo-suu-tap">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 section-rule mb-4">
            <div class="d-flex align-items-center gap-3">
                <span class="service-icon" style="width: 48px; height: 48px; font-size: 22px;">
                    <i class="bi bi-stars"></i>
                </span>
                <h4 class="mb-0 fw-bold">BST MỚI</h4>
            </div>
            <a href="#bo-suu-tap" class="btn btn-outline-red px-4">TẤT CẢ</a>
        </div>

        <div class="row g-4">
            @foreach ($designs as $design)
                <div class="col-md-6 col-lg-4">
                    <button type="button"
                            class="gift-card-design w-100 border-0 text-decoration-none"
                            style="background: {{ $design['theme'] }}; color: {{ $design['text_color'] }};"
                            data-bs-toggle="modal"
                            data-bs-target="#giftCardModal-{{ $loop->index }}">
                        <span class="design-emoji">{{ $design['emoji'] }}</span>
                        <span class="design-title">{{ $design['name'] }}</span>
                        <span class="design-brand">YUNFLIMS GIFT CARD</span>
                    </button>

                    <div class="mt-3">
                        <h6 class="fw-bold mb-1 text-white">{{ $design['name'] }}</h6>
                        <p class="text-secondary small mb-0">{{ $design['description'] }}</p>
                    </div>
                </div>

                <div class="modal fade" id="giftCardModal-{{ $loop->index }}" tabindex="-1"
                     aria-labelledby="giftCardModalLabel-{{ $loop->index }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-light border-secondary">
                            <div class="modal-header border-secondary">
                                <h5 class="modal-title" id="giftCardModalLabel-{{ $loop->index }}">
                                    Thẻ quà tặng {{ $design['name'] }}
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body">
                                <div class="gift-card-design mb-3"
                                     style="background: {{ $design['theme'] }}; color: {{ $design['text_color'] }};">
                                    <span class="design-emoji">{{ $design['emoji'] }}</span>
                                    <span class="design-title">{{ $design['name'] }}</span>
                                    <span class="design-brand">YUNFLIMS GIFT CARD</span>
                                </div>
                                <p class="mb-2">{{ $design['description'] }}</p>
                                <p class="text-secondary small mb-0">
                                    Để mua thẻ quà tặng, vui lòng liên hệ quầy vé YUNFLIMS hoặc hotline
                                    1900 0000. Thẻ có thể dùng để đổi vé xem phim tại tất cả rạp YUNFLIMS.
                                </p>
                            </div>
                            <div class="modal-footer border-secondary">
                                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Đóng</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="bi bi-gift feature-icon"></i>
                    <h5 class="mt-3">Quà tặng ý nghĩa</h5>
                    <p class="text-secondary mb-0 small">
                        Thẻ quà tặng là món quà phù hợp cho sinh nhật, lễ kỷ niệm và dịp đặc biệt.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="bi bi-shield-check feature-icon"></i>
                    <h5 class="mt-3">An toàn &amp; bảo mật</h5>
                    <p class="text-secondary mb-0 small">
                        Mỗi thẻ có mã riêng, được bảo vệ và kiểm tra khi sử dụng tại quầy.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="bi bi-arrow-repeat feature-icon"></i>
                    <h5 class="mt-3">Linh hoạt</h5>
                    <p class="text-secondary mb-0 small">
                        Người nhận có thể tự chọn phim, suất chiếu và vị trí ghế yêu thích.
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
