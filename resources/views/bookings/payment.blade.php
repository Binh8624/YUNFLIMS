
@extends('layouts.app')

@section('title', 'Thanh toán - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card bg-dark text-white border-secondary cinema-panel">
                <div class="card-body p-4">
                    <h2 class="mb-4">Thanh toán vé</h2>

                    <div class="alert alert-warning d-flex gap-3" role="status">
                        <i class="bi bi-clock-history fs-4" aria-hidden="true"></i>
                        <div>
                            <strong>Ghế đang được giữ tạm thời</strong>
                            <div>
                                Hoàn tất đặt vé trước
                                <time datetime="{{ $booking->hold_expires_at->toIso8601String() }}">
                                    {{ $booking->hold_expires_at->format('d/m/Y H:i:s') }}
                                </time>.
                            </div>
                            <div class="hold-countdown mt-1">
                                Thời gian còn lại:
                                <strong id="hold-countdown"
                                    data-expires-at="{{ $booking->hold_expires_at->timestamp }}">
                                    --:--
                                </strong>
                            </div>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    {{-- THÔNG TIN PHIM --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <p class="text-secondary mb-1">Phim</p>
                            <h4 class="mb-0">{{ $booking->showtime->movie->title }}</h4>
                        </div>

                        <div class="col-md-6 text-md-end">
                            <p class="text-secondary mb-1">Mã đặt vé</p>
                            <strong class="text-warning">{{ $booking->booking_code }}</strong>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <p class="text-secondary mb-1">Rạp</p>
                            <p class="mb-0">
                                {{ $booking->showtime->room->cinema->name ?? 'YUNFLIMS' }}
                            </p>
                        </div>

                        <div class="col-md-4">
                            <p class="text-secondary mb-1">Phòng</p>
                            <p class="mb-0">
                                {{ $booking->showtime->room->name }}
                                · {{ $booking->showtime->format?->name ?? '2D' }}
                            </p>
                        </div>

                        <div class="col-md-4">
                            <p class="text-secondary mb-1">Suất chiếu</p>
                            <p class="mb-0">
                                {{ $booking->showtime->starts_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>

                    {{-- GHẾ ĐÃ CHỌN --}}
                    <div class="mb-4">
                        <p class="text-secondary mb-2">Ghế đã chọn</p>

                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($booking->bookingSeats as $bookingSeat)
                                <span class="badge bg-success px-3 py-2">
                                    {{ $bookingSeat->seat->seat_code ?? ($bookingSeat->seat->row_number . $bookingSeat->seat->seat_number) }}
                                    · {{ number_format((float) $bookingSeat->price, 0, ',', '.') }} đ
                                </span>
                            @endforeach
                        </div>
                    </div>

                    {{-- BẮP NƯỚC ĐÃ CHỌN --}}
                    <div class="border-top border-secondary pt-4 mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-cup-straw me-2 text-warning"></i>
                                Bắp nước đã chọn
                            </h5>

                            <a href="{{ route('bookings.concessions', $booking) }}"
                               class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-pencil-square me-1"></i>
                                Chỉnh sửa
                            </a>
                        </div>

                        @if ($booking->concessionOrder && $booking->concessionOrder->items->isNotEmpty())
                            @foreach ($booking->concessionOrder->items as $item)
                                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                    <div>
                                        <div class="fw-semibold">
                                            {{ $item->product_name }}
                                        </div>
                                        <small class="text-secondary">
                                            {{ number_format((float) $item->unit_price, 0, ',', '.') }} đ
                                            × {{ $item->quantity }}
                                        </small>
                                    </div>

                                    <div class="fw-semibold text-warning text-nowrap">
                                        {{ number_format((float) $item->subtotal, 0, ',', '.') }} đ
                                    </div>
                                </div>
                            @endforeach

                            <div class="d-flex justify-content-between border-top border-secondary pt-3">
                                <span class="text-secondary">Tổng bắp nước</span>
                                <strong class="text-warning">
                                    {{ number_format((float) $booking->concessionOrder->total_amount, 0, ',', '.') }} đ
                                </strong>
                            </div>
                        @else
                            <div class="text-secondary mb-2">
                                Bạn chưa chọn bắp nước.
                            </div>

                            <a href="{{ route('bookings.concessions', $booking) }}"
                               class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-plus-circle me-1"></i>
                                Chọn bắp nước
                            </a>
                        @endif
                    </div>

                    {{-- THANH TOÁN --}}
                    <div class="border-top border-secondary pt-4 mt-4">
                        @if ($mockPaymentEnabled)
                            <div class="alert alert-secondary" role="status">
                                <i class="bi bi-shield-lock me-2" aria-hidden="true"></i>
                                Thanh toán mô phỏng chỉ khả dụng trong môi trường local/testing.
                            </div>

                            <form method="POST"
                                  action="{{ route('bookings.payment.process', $booking) }}"
                                  class="mb-4">
                                @csrf

                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle me-2" aria-hidden="true"></i>
                                    Thanh toán vé và bắp nước
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning" role="status">
                                Thanh toán mô phỏng không khả dụng trong môi trường này.
                            </div>
                        @endif
                    </div>

                    {{-- TỔNG TIỀN --}}
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mt-3 pt-3 border-top border-secondary">
                        <div>
                            <p class="mb-1 text-secondary">Tổng thanh toán</p>
                            <h3 class="text-warning mb-0">
                                {{ number_format((float) $booking->total_amount, 0, ',', '.') }} đ
                            </h3>
                            <small class="text-secondary">
                                Đã bao gồm tiền vé và bắp nước (nếu có)
                            </small>
                        </div>

                        <form method="POST"
                              action="{{ route('bookings.cancel', $booking) }}"
                              onsubmit="return confirm('Hủy đơn đặt vé và giải phóng ghế đang giữ?')">
                            @csrf

                            <button type="submit" class="btn btn-outline-light">
                                <i class="bi bi-x-circle me-2" aria-hidden="true"></i>
                                Hủy giữ ghế
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const countdown = document.getElementById('hold-countdown');

    if (countdown) {
        const expiresAt = Number(countdown.dataset.expiresAt) * 1000;

        function updateHoldCountdown() {
            const secondsRemaining = Math.max(
                0,
                Math.ceil((expiresAt - Date.now()) / 1000)
            );

            const minutes = Math.floor(secondsRemaining / 60);
            const seconds = secondsRemaining % 60;

            countdown.textContent =
                String(minutes).padStart(2, '0') + ':' +
                String(seconds).padStart(2, '0');

            if (secondsRemaining === 0) {
                window.location.reload();
                return;
            }

            window.setTimeout(updateHoldCountdown, 1000);
        }

        updateHoldCountdown();
    }
</script>
@endpush
