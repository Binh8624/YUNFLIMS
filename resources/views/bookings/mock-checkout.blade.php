@extends('layouts.app')

@section('title', 'Cổng mô phỏng - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card bg-dark text-white border-secondary cinema-panel">
                <div class="card-body p-4">
                    <span class="hero-kicker">CỔNG MÔ PHỎNG</span>
                    <h1 class="h4 mt-1 mb-3">Giả lập kết quả giao dịch</h1>
                    <p class="text-secondary">Đây là môi trường thử nghiệm, không kết nối ngân hàng và không thu tiền thật. Chọn một nút bên dưới để giả lập kết quả.</p>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <p class="text-secondary mb-1">Mã giao dịch</p>
                            <strong class="text-warning">{{ $payment->order_ref }}</strong>
                        </div>
                        <div class="col-6 text-end">
                            <p class="text-secondary mb-1">Số tiền</p>
                            <strong class="text-warning">{{ number_format((float) $payment->amount) }} đ</strong>
                        </div>
                        <div class="col-12">
                            <p class="text-secondary mb-1">Đơn đặt vé</p>
                            <p class="mb-0">{{ $payment->booking->booking_code }} · {{ $payment->booking->showtime->movie->title }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('payment.mock.simulate', $payment) }}">
                        @csrf
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" name="result" value="failed" class="btn btn-outline-warning">
                                <i class="bi bi-x-circle me-2" aria-hidden="true"></i>Giả lập thất bại
                            </button>
                            <button type="submit" name="result" value="timeout" class="btn btn-outline-light">
                                <i class="bi bi-clock-history me-2" aria-hidden="true"></i>Giả lập hết thời gian
                            </button>
                            <button type="submit" name="result" value="success" class="btn btn-success">
                                <i class="bi bi-check-circle me-2" aria-hidden="true"></i>Giả lập thanh toán thành công
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection