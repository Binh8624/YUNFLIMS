@extends('layouts.app')

@section('title', 'Quản lý đơn đặt vé - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="mb-4"><h1 class="h2 fw-bold mb-1">Đơn đặt vé</h1><p class="text-secondary mb-0">Tra cứu đơn hàng, khách hàng, trạng thái thanh toán.</p></div>
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Mã đơn, tên hoặc email khách hàng"></div>
        <div class="col-md-2"><select name="status" class="form-select"><option value="">Mọi trạng thái đơn</option>@foreach (['pending' => 'Chờ thanh toán', 'confirmed' => 'Đã xác nhận', 'cancelled' => 'Đã hủy'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="payment_status" class="form-select"><option value="">Mọi trạng thái tiền</option>@foreach (['pending', 'paid', 'failed', 'cancelled'] as $status)<option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-outline-light">Lọc đơn</button></div>
    </form>
    <div class="card bg-dark border-secondary"><div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0"><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Phim / suất</th><th>Ghế</th><th>Tổng</th><th>Đơn / thanh toán</th><th></th></tr></thead>
            <tbody>@forelse ($bookings as $booking)
                <tr><td class="text-warning">{{ $booking->booking_code }}<div class="small text-secondary">{{ $booking->created_at->format('d/m/Y H:i') }}</div></td><td>{{ $booking->user->name }}<div class="small text-secondary">{{ $booking->user->email }}</div></td>
                    <td>{{ $booking->showtime->movie->title }}<div class="small text-secondary">{{ $booking->showtime->starts_at->format('d/m/Y H:i') }}</div></td>
                    <td>{{ $booking->bookingSeats->count() }}</td><td>{{ number_format((float) $booking->total_amount, 0, ',', '.') }} đ</td>
                    <td><span class="badge bg-secondary">{{ $booking->status }}</span> <span class="badge {{ $booking->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $booking->payment_status }}</span></td>
                    <td><a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-outline-light">Chi tiết</a></td></tr>
            @empty<tr><td colspan="7" class="text-center text-secondary py-4">Không có đơn đặt vé.</td></tr>@endforelse</tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $bookings->links() }}</div>
</div>
@endsection
