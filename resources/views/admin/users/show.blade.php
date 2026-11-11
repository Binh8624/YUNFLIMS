@extends('layouts.app')

@section('title', 'Chi tiết người dùng - YUNFLIMS Admin')

@section('content')
<div class="container-fluid admin-dashboard">
    <div class="admin-page-header">
        <div>
            <a href="{{ route('admin.users.index') }}" class="small link-light text-decoration-none">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Danh sách người dùng
            </a>
            <h1 class="mt-2">{{ $user->name }}</h1>
            <p>{{ $user->email }} · Tham gia {{ $user->created_at->format('d/m/Y') }}</p>
        </div>
        <span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-danger' }} align-self-start">
            {{ $user->is_active ? 'Tài khoản hoạt động' : 'Tài khoản đã khóa' }}
        </span>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <article class="admin-stat-card">
                <div class="admin-stat-top"><span>Vai trò hiện tại</span><span class="admin-stat-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span></div>
                <strong class="admin-stat-value">{{ $user->role === 'admin' ? 'Admin' : 'Khách hàng' }}</strong>
                <div class="admin-stat-note">Phân quyền theo cấu hình hiện có</div>
            </article>
        </div>
        <div class="col-md-4">
            <article class="admin-stat-card">
                <div class="admin-stat-top"><span>Tổng đơn đặt vé</span><span class="admin-stat-icon"><i class="bi bi-ticket-perforated" aria-hidden="true"></i></span></div>
                <strong class="admin-stat-value">{{ number_format($user->bookings_count) }}</strong>
                <div class="admin-stat-note">Bao gồm mọi trạng thái booking</div>
            </article>
        </div>
        <div class="col-md-4">
            <article class="admin-stat-card">
                <div class="admin-stat-top"><span>Đã thanh toán</span><span class="admin-stat-icon"><i class="bi bi-cash-stack" aria-hidden="true"></i></span></div>
                <strong class="admin-stat-value">{{ number_format((float) ($user->paid_total ?? 0), 0, ',', '.') }} đ</strong>
                <div class="admin-stat-note">Booking có trạng thái payment paid</div>
            </article>
        </div>
    </div>

    <section class="admin-panel">
        <div class="admin-panel-heading">
            <h2>Lịch sử đặt vé</h2>
            <span class="small text-secondary">{{ number_format($bookings->total()) }} đơn</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Mã booking</th><th>Phim / suất chiếu</th><th>Cụm rạp</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td class="text-warning">{{ $booking->booking_code }}</td>
                            <td>{{ $booking->showtime?->movie?->title ?? 'Phim đã gỡ' }}<div class="small text-secondary">{{ $booking->showtime?->starts_at?->format('d/m/Y H:i') ?? '—' }}</div></td>
                            <td>{{ $booking->showtime?->room?->cinema?->name ?? '—' }}</td>
                            <td>{{ $booking->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ number_format((float) $booking->total_amount, 0, ',', '.') }} đ</td>
                            <td><span class="badge text-bg-secondary">{{ $booking->status }}</span> <span class="badge {{ $booking->payment_status === 'paid' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $booking->payment_status }}</span></td>
                            <td><a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-outline-light">Chi tiết</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="admin-empty-state"><i class="bi bi-ticket" aria-hidden="true"></i>Người dùng chưa có đơn đặt vé.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($bookings->hasPages())
            <div class="p-3">{{ $bookings->links() }}</div>
        @endif
    </section>
</div>
@endsection
