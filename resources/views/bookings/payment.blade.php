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
                            <div>Hoàn tất đặt vé trước <time datetime="{{ $booking->hold_expires_at->toIso8601String() }}">{{ $booking->hold_expires_at->format('d/m/Y H:i:s') }}</time>.</div>
                            <div class="hold-countdown mt-1">Thời gian còn lại: <strong id="hold-countdown" data-expires-at="{{ $booking->hold_expires_at->timestamp }}">--:--</strong></div>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

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
                            <p class="mb-0">{{ $booking->showtime->room->cinema->name ?? 'YUNFLIMS' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-secondary mb-1">Phòng</p>
                            <p class="mb-0">{{ $booking->showtime->room->name }} · {{ $booking->showtime->format?->name ?? '2D' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-secondary mb-1">Suất chiếu</p>
                            <p class="mb-0">{{ $booking->showtime->starts_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

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

                    <div class="alert alert-secondary" role="status">
                        <i class="bi bi-shield-lock me-2" aria-hidden="true"></i>
                        Chế độ hiện tại là thanh toán mô phỏng. Dữ liệu gửi lên máy chủ sẽ được kiểm tra và lưu trạng thái giao dịch để không xác nhận vé khi chưa thanh toán thành công.
                    </div>

                    <form method="POST" action="{{ route('bookings.payment.process', $booking) }}" class="mb-4">
                        @csrf
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" name="payment_result" value="success" class="btn btn-success">
                                <i class="bi bi-check-circle me-2" aria-hidden="true"></i>Mock thanh toán thành công
                            </button>
                            <button type="submit" name="payment_result" value="failed" class="btn btn-outline-warning">
                                <i class="bi bi-x-circle me-2" aria-hidden="true"></i>Mock thanh toán thất bại
                            </button>
                            <button type="submit" name="payment_result" value="timeout" class="btn btn-outline-light">
                                <i class="bi bi-clock-history me-2" aria-hidden="true"></i>Mock hết thời gian
                            </button>
                        </div>
                        <input type="hidden" name="payment_method" value="mock_test" />
                    </form>

                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mt-4 pt-3 border-top border-secondary">
                        <div>
                            <p class="mb-1 text-secondary">Tổng tiền</p>
                            <h3 class="text-warning mb-0">{{ number_format($booking->total_amount) }} đ</h3>
                        </div>

                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('Hủy đơn đặt vé và giải phóng ghế đang giữ?')">
                            @csrf
                            <button type="submit" class="btn btn-outline-light">
                                <i class="bi bi-x-circle me-2" aria-hidden="true"></i>Hủy giữ ghế
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
            const secondsRemaining = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
            const minutes = Math.floor(secondsRemaining / 60);
            const seconds = secondsRemaining % 60;

            countdown.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

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
