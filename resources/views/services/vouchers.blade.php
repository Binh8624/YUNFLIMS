@extends('layouts.app')

@section('title', 'Voucher phim - YUNFLIMS Cinema')

@push('styles')
<style>
    .page-title {
        border-bottom: 2px solid #303039;
        padding-bottom: 14px;
    }

    .voucher-item {
        height: 100%;
    }

    .voucher-visual {
        position: relative;
        border-radius: 12px;
        aspect-ratio: 16 / 9;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        color: #fff;
        overflow: hidden;
        box-shadow: 0 10px 26px rgba(0, 0, 0, .35);
    }

    .voucher-visual::after {
        content: "";
        position: absolute;
        inset: 12px;
        border: 1px dashed rgba(255, 255, 255, .45);
        border-radius: 8px;
        pointer-events: none;
    }

    .voucher-visual .voucher-icon {
        font-size: 34px;
        line-height: 1;
    }

    .voucher-visual .voucher-brand {
        font-size: 12px;
        letter-spacing: 3px;
        opacity: .85;
    }

    .voucher-visual .voucher-label {
        font-size: 20px;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .voucher-name {
        font-weight: 800;
        font-size: 19px;
    }

    .voucher-price {
        font-size: 18px;
        font-weight: 700;
        color: #fff;
    }

    .btn-buy {
        background: var(--ys-red);
        color: #fff;
        border: none;
        font-weight: 800;
        font-size: 12px;
        letter-spacing: .5px;
        padding: 6px 14px;
        border-radius: 4px;
    }

    .btn-buy:hover {
        background: #bd0610;
        color: #fff;
    }

    .voucher-desc {
        color: #b9b9c2;
        font-size: 14px;
    }

    .voucher-note {
        background: var(--ys-card);
        border: 1px solid #303039;
        border-radius: 12px;
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
            <li class="breadcrumb-item active text-light" aria-current="page">Voucher phim</li>
        </ol>
    </nav>

    <h1 class="page-title fw-bold mb-4">Voucher Phim</h1>

    <div class="row g-4 g-lg-5">
        @foreach ($vouchers as $voucher)
            <div class="col-md-6 col-lg-4">
                <div class="voucher-item">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <span class="voucher-name">{{ $voucher['name'] }}</span>
                        <button type="button"
                                class="btn-buy"
                                data-bs-toggle="modal"
                                data-bs-target="#voucherModal-{{ $voucher['slug'] }}">
                            MUA
                        </button>
                    </div>

                    <div class="voucher-price mb-2">
                        {{ number_format($voucher['price'], 0, ',', '.') }} đ
                    </div>

                    <p class="voucher-desc mb-3">{{ $voucher['description'] }}</p>

                    <div class="voucher-visual" style="background: {{ $voucher['theme'] }};">
                        <i class="bi {{ $voucher['icon'] }} voucher-icon"></i>
                        <span class="voucher-brand">YUNFLIMS</span>
                        <span class="voucher-label">{{ $voucher['name'] }}</span>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="voucherModal-{{ $voucher['slug'] }}" tabindex="-1"
                 aria-labelledby="voucherModalLabel-{{ $voucher['slug'] }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-dark text-light border-secondary">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title" id="voucherModalLabel-{{ $voucher['slug'] }}">
                                {{ $voucher['name'] }}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <div class="voucher-visual mb-3" style="background: {{ $voucher['theme'] }};">
                                <i class="bi {{ $voucher['icon'] }} voucher-icon"></i>
                                <span class="voucher-brand">YUNFLIMS</span>
                                <span class="voucher-label">{{ $voucher['name'] }}</span>
                            </div>
                            <p class="mb-2">
                                <strong>Giá bán:</strong>
                                {{ number_format($voucher['price'], 0, ',', '.') }} đ
                            </p>
                            <p class="mb-2">{{ $voucher['description'] }}</p>
                            <p class="text-secondary small mb-0">
                                Để mua voucher, vui lòng liên hệ quầy vé YUNFLIMS hoặc hotline
                                1900 0000. Tính năng đặt mua trực tuyến sẽ sớm ra mắt.
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

    <div class="voucher-note p-4 mt-5">
        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Lưu ý khi sử dụng voucher</h5>
        <ul class="text-secondary small mb-0">
            <li>Voucher áp dụng cho suất chiếu phim đang được công chiếu tại YUNFLIMS.</li>
            <li>Mỗi voucher có giá trị đổi một vé xem phim theo định dạng tương ứng.</li>
            <li>Voucher không quy đổi thành tiền mặt và không áp dụng đồng thời với ưu đãi khác.</li>
            <li>Vui lòng kiểm tra thời hạn sử dụng in trên voucher trước khi dùng.</li>
        </ul>
    </div>
</div>
@endsection
