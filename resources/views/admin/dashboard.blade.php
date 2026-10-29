@extends('layouts.app')

@section('title', 'Dashboard quản trị - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="mb-4">
        <h1 class="h2 fw-bold mb-1">Dashboard</h1>
        <p class="text-secondary mb-0">Tổng quan hoạt động hệ thống YUNFLIMS.</p>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['Phim', $stats['movies'], 'bi-film'],
            ['Rạp chiếu', $stats['cinemas'], 'bi-building'],
            ['Phòng chiếu', $stats['rooms'], 'bi-door-open'],
            ['Suất chiếu', $stats['showtimes'], 'bi-calendar2-week'],
            ['Người dùng', $stats['users'], 'bi-people'],
            ['Đơn đặt vé', $stats['bookings'], 'bi-ticket-perforated'],
        ] as [$label, $value, $icon])
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card bg-dark border-secondary h-100">
                    <div class="card-body">
                        <div class="text-secondary small">{{ $label }}</div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <strong class="fs-3">{{ number_format($value) }}</strong>
                            <i class="bi {{ $icon }} fs-3 text-danger" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="col-12 col-md-8 col-xl-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <div class="text-secondary small">Doanh thu đã thanh toán</div>
                    <strong class="fs-3 text-warning">{{ number_format((float) $stats['revenue'], 0, ',', '.') }} đ</strong>
                    <div class="small text-secondary mt-1">Chỉ tính đơn có trạng thái thanh toán paid.</div>
                </div>
            </div>
        </div>
    </div>

    <section class="card bg-dark border-secondary">
        <div class="card-header bg-transparent border-secondary d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Đơn đặt vé gần đây</h2>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-light">Tất cả đơn</a>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead><tr><th>Mã</th><th>Khách hàng</th><th>Phim</th><th>Ngày tạo</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                    @forelse ($recentBookings as $booking)
                        <tr>
                            <td class="text-warning">{{ $booking->booking_code }}</td>
                            <td>{{ $booking->user->name }}</td>
                            <td>{{ $booking->showtime->movie->title }}</td>
                            <td>{{ $booking->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ ucfirst($booking->status) }} / {{ ucfirst($booking->payment_status) }}</td>
                            <td><a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-outline-light">Chi tiết</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">Chưa có đơn đặt vé.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
