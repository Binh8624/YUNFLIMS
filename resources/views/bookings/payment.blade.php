@extends('layouts.app')

@section('title', 'Thanh toán - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card bg-dark text-white border-secondary">
                <div class="card-body p-4">
                    <h2 class="mb-4">Thanh toán vé</h2>
                    <div class="alert alert-warning">
                        Đây là mô phỏng thanh toán, chưa kết nối ngân hàng/cổng thanh toán. Ghế được giữ đến
                        <strong>{{ $booking->hold_expires_at->format('d/m/Y H:i:s') }}</strong>.
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

                    <div class="border border-secondary rounded p-3 mb-4">
                        <h5 class="mb-3">Chọn phương thức thanh toán</h5>

                        <form method="POST" action="{{ route('bookings.payment.process', $booking) }}">
                            @csrf

                            <div class="list-group">
                                <label class="list-group-item bg-transparent border-secondary text-white d-flex align-items-center gap-3">
                                    <input class="form-check-input me-2" type="radio" name="payment_method" value="card" checked>
                                    <span>
                                        <strong>Thẻ tín dụng / Ghi nợ</strong><br>
                                        <small class="text-secondary">Thanh toán thử nghiệm</small>
                                    </span>
                                </label>

                                <label class="list-group-item bg-transparent border-secondary text-white d-flex align-items-center gap-3">
                                    <input class="form-check-input me-2" type="radio" name="payment_method" value="momo">
                                    <span>
                                        <strong>Ví MoMo</strong><br>
                                        <small class="text-secondary">Mô phỏng thanh toán</small>
                                    </span>
                                </label>

                                <label class="list-group-item bg-transparent border-secondary text-white d-flex align-items-center gap-3">
                                    <input class="form-check-input me-2" type="radio" name="payment_method" value="bank_transfer">
                                    <span>
                                        <strong>Chuyển khoản ngân hàng</strong><br>
                                        <small class="text-secondary">Thanh toán giả lập</small>
                                    </span>
                                </label>

                                <label class="list-group-item bg-transparent border-secondary text-white d-flex align-items-center gap-3">
                                    <input class="form-check-input me-2" type="radio" name="payment_method" value="cash">
                                    <span>
                                        <strong>Tiền mặt tại quầy</strong><br>
                                        <small class="text-secondary">Chỉ dùng kiểm thử</small>
                                    </span>
                                </label>
                            </div>

                            <fieldset class="mt-4">
                                <legend class="h6">Kết quả mô phỏng</legend>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio" name="test_result" value="success" checked>
                                        <span class="form-check-label text-success">Mô phỏng thành công</span>
                                    </label>
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio" name="test_result" value="failed">
                                        <span class="form-check-label text-danger">Mô phỏng thất bại</span>
                                    </label>
                                </div>
                            </fieldset>

                            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-secondary">
                                <div>
                                    <p class="mb-1 text-secondary">Tổng tiền</p>
                                    <h3 class="text-warning mb-0">{{ number_format($booking->total_amount) }} đ</h3>
                                </div>

                                <button type="submit" class="btn btn-red px-4">
                                    Xác nhận thanh toán
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
