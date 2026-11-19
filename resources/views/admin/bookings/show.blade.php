@extends('layouts.app')

@section('title', 'Chi tiết đơn ' . $booking->booking_code . ' - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h2 fw-bold mb-1">Đơn {{ $booking->booking_code }}</h1><p class="text-secondary mb-0">Tạo lúc {{ $booking->created_at->format('d/m/Y H:i') }}</p></div>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-light">Quay lại đơn hàng</a>
    </div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div class="row g-3">
        <div class="col-lg-7"><div class="card bg-dark border-secondary h-100"><div class="card-body p-4">
            <h2 class="h5 mb-3">Thông tin vé</h2>
            <div class="row g-3">
                <div class="col-sm-6"><span class="text-secondary">Khách hàng</span><div>{{ $booking->user->name }} ({{ $booking->user->email }})</div></div>
                <div class="col-sm-6"><span class="text-secondary">Phim</span><div>{{ $booking->showtime->movie->title }}</div></div>
                <div class="col-sm-6"><span class="text-secondary">Rạp / phòng</span><div>{{ $booking->showtime->room->cinema->name }} / {{ $booking->showtime->room->name }}</div></div>
                <div class="col-sm-6"><span class="text-secondary">Suất chiếu</span><div>{{ $booking->showtime->starts_at->format('d/m/Y H:i') }}</div></div>
            </div>
            <hr class="border-secondary">
            <h3 class="h6">Ghế và giá</h3>
            <div class="table-responsive"><table class="table table-dark table-sm align-middle"><thead><tr><th>Ghế</th><th>Loại</th><th>Giá</th></tr></thead><tbody>
                @foreach ($booking->bookingSeats as $bookingSeat)<tr><td>{{ $bookingSeat->seat->seat_code }}</td><td>{{ $bookingSeat->seat->seat_type }}</td><td>{{ number_format((float) $bookingSeat->price, 0, ',', '.') }} đ</td></tr>@endforeach
            </tbody><tfoot><tr><th colspan="2">Tổng đơn</th><th class="text-warning">{{ number_format((float) $booking->total_amount, 0, ',', '.') }} đ</th></tr></tfoot></table></div>
        </div></div></div>
        <div class="col-lg-5"><div class="card bg-dark border-secondary h-100"><div class="card-body p-4">
            <h2 class="h5 mb-3">Thanh toán</h2>
            <dl class="row">
                <dt class="col-5 text-secondary">Trạng thái đơn</dt><dd class="col-7">{{ $booking->status }}</dd>
                <dt class="col-5 text-secondary">Trạng thái tiền</dt><dd class="col-7">{{ $booking->payment_status }}</dd>
                <dt class="col-5 text-secondary">Phương thức</dt><dd class="col-7">{{ $booking->payment_method ?: '—' }}</dd>
                <dt class="col-5 text-secondary">Mã giao dịch</dt><dd class="col-7 text-break">{{ $booking->payment_reference ?: '—' }}</dd>
                <dt class="col-5 text-secondary">Thời điểm trả</dt><dd class="col-7">{{ $booking->paid_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </dl>
            @if ($booking->status === 'pending' && $booking->payment_status !== 'paid')
                <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}" onsubmit="return confirm('Hủy đơn đặt vé đang chờ này?')">@csrf @method('PATCH')<button class="btn btn-danger">Hủy đơn chờ thanh toán</button></form>
            @endif
        </div></div></div>
    </div>
</div>
@endsection
