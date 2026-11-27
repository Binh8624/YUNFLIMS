@extends('layouts.app')

@section('title', 'Quản lý thanh toán - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="mb-4"><h1 class="h2 fw-bold mb-1">Thanh toán</h1><p class="text-secondary mb-0">Các giao dịch được lưu cùng đơn đặt vé. Hệ thống chưa cấu hình cổng thanh toán để xác nhận giao dịch thực.</p></div>
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Mã giao dịch hoặc mã đơn"></div>
        <div class="col-md-2"><select name="payment_status" class="form-select"><option value="">Mọi trạng thái</option>@foreach (['pending', 'paid', 'failed', 'cancelled'] as $status)<option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="payment_method" class="form-select"><option value="">Mọi phương thức</option>@foreach (['card', 'momo', 'bank_transfer', 'cash'] as $method)<option value="{{ $method }}" @selected(request('payment_method') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-outline-light">Lọc giao dịch</button></div>
    </form>
    <div class="card bg-dark border-secondary"><div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0"><thead><tr><th>Mã giao dịch</th><th>Mã đơn / khách</th><th>Phim</th><th>Phương thức</th><th>Số tiền</th><th>Trạng thái</th><th>Thời gian</th></tr></thead>
            <tbody>@forelse ($payments as $payment)
                <tr><td class="text-break">{{ $payment->payment_reference ?: '—' }}</td><td><a href="{{ route('admin.bookings.show', $payment) }}">{{ $payment->booking_code }}</a><div class="small text-secondary">{{ $payment->user->name }}</div></td><td>{{ $payment->showtime->movie->title }}</td><td>{{ $payment->payment_method ? ucfirst(str_replace('_', ' ', $payment->payment_method)) : '—' }}</td><td>{{ number_format((float) $payment->total_amount, 0, ',', '.') }} đ</td><td><span class="badge {{ $payment->payment_status === 'paid' ? 'bg-success' : 'bg-secondary' }}">{{ $payment->payment_status }}</span></td><td>{{ $payment->paid_at?->format('d/m/Y H:i') ?? $payment->updated_at->format('d/m/Y H:i') }}</td></tr>
            @empty<tr><td colspan="7" class="text-center text-secondary py-4">Chưa có giao dịch phù hợp.</td></tr>@endforelse</tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $payments->links() }}</div>
</div>
@endsection
