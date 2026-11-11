@extends('layouts.app')

@section('title', 'Thông tin vé - YUNFLIMS')

@section('content')
<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="card bg-dark text-white border-secondary mx-auto cinema-panel"
         style="max-width:820px;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">
                        @if ($booking->status === 'confirmed')
                            <span class="text-success">Vé đã xác nhận</span>
                        @elseif ($booking->status === 'pending')
                            <span class="text-warning">Vé chờ thanh toán</span>
                        @else
                            <span class="text-danger">Vé đã hủy</span>
                        @endif
                    </h2>
                    <p class="mb-0 text-secondary">Mã đặt vé: <strong class="text-warning">{{ $booking->booking_code }}</strong></p>
                </div>

                @if ($booking->status === 'pending')
                    <a href="{{ route('bookings.payment', $booking) }}" class="btn btn-red">Thanh toán ngay</a>
                @endif
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <p class="mb-1 text-secondary">Phim</p>
                        <h4 class="mb-0">{{ $booking->showtime->movie->title }}</h4>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-secondary">Rạp</p>
                            <p class="mb-0">{{ $booking->showtime->room->cinema->name ?? 'YUNFLIMS' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-secondary">Phòng</p>
                            <p class="mb-0">{{ $booking->showtime->room->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-secondary">Suất chiếu</p>
                            <p class="mb-0">{{ $booking->showtime->starts_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-secondary">Phương thức thanh toán</p>
                            <p class="mb-0">
                                @if ($booking->payment_method)
                                    {{ ucfirst(str_replace('_', ' ', $booking->payment_method)) }}
                                @else
                                    Chưa thanh toán
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="text-secondary mb-2">Ghế đã chọn</p>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($booking->bookingSeats as $bookingSeat)
                                <span class="badge bg-success px-3 py-2">
                                    {{ $bookingSeat->seat->seat_code ?? ($bookingSeat->seat->row_number . $bookingSeat->seat->seat_number) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 text-center border-start border-secondary">
                    @if ($booking->status === 'confirmed' && $booking->qr_code)
                        <p class="text-secondary mb-2">Mã QR vé điện tử</p>
                        <div class="d-inline-block bg-white p-2 rounded">
                            {!! $booking->qr_code !!}
                        </div>
                    @endif

                    <div class="mt-4">
                        <p class="text-secondary mb-1">Trạng thái thanh toán</p>
                        <strong class="{{ $booking->payment_status === 'paid' ? 'text-success' : 'text-warning' }}">
                            {{ $booking->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                        </strong>
                    </div>

                    <div class="mt-4">
                        <h4 class="text-warning mb-0">{{ number_format($booking->total_amount) }} đ</h4>
                    </div>
                    @if ($booking->status === 'pending' && $booking->hold_expires_at)
                        <p class="small text-secondary mt-2">Giữ ghế đến {{ $booking->hold_expires_at->format('d/m/Y H:i:s') }}</p>
                    @endif
                </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2">
                <a href="{{ route('bookings.history') }}" class="btn btn-outline-light">Lịch sử đặt vé</a>
                <a href="{{ route('showtimes.index') }}" class="btn btn-danger">Quay lại suất chiếu</a>
                @if ($booking->status === 'pending')
                    <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('Hủy đơn đặt vé và giải phóng ghế đang giữ?')">
                        @csrf
                        <button type="submit" class="btn btn-outline-light">Hủy giữ ghế</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection