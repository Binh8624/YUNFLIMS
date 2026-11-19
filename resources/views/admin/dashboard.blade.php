@extends('layouts.app')

@section('title', 'Tổng quan vận hành - YUNFLIMS Admin')

@section('content')
<div class="container-fluid admin-dashboard">
    <div class="admin-page-header">
        <div>
            <span class="admin-page-eyebrow">YUNFLIMS · TRUNG TÂM ĐIỀU HÀNH</span>
            <h1>Tổng quan vận hành</h1>
            <p>{{ now()->locale('vi')->translatedFormat('l, d/m/Y') }} · Số liệu thanh toán chỉ tính giao dịch đã xác nhận.</p>
        </div>
        <a href="{{ route('admin.showtimes.create') }}" class="btn btn-red">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Tạo suất chiếu
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('admin.dashboard') }}" class="admin-dashboard-filters">
        <div>
            <label for="dashboard-period" class="form-label">Khoảng thời gian</label>
            <select id="dashboard-period" name="period" class="form-select" data-dashboard-period>
                <option value="today" @selected($period === 'today')>Hôm nay</option>
                <option value="7d" @selected($period === '7d')>7 ngày gần nhất</option>
                <option value="30d" @selected($period === '30d')>30 ngày gần nhất</option>
                <option value="month" @selected($period === 'month')>Tháng này</option>
                <option value="custom" @selected($period === 'custom')>Tùy chọn khoảng ngày</option>
            </select>
        </div>
        <div class="{{ $period === 'custom' ? '' : 'd-none' }}" data-custom-range>
            <label for="dashboard-from" class="form-label">Từ ngày</label>
            <input id="dashboard-from" type="date" name="from" class="form-control" value="{{ request('from') }}" @if ($period === 'custom') required @endif>
        </div>
        <div class="{{ $period === 'custom' ? '' : 'd-none' }}" data-custom-range>
            <label for="dashboard-to" class="form-label">Đến ngày</label>
            <input id="dashboard-to" type="date" name="to" class="form-control" value="{{ request('to') }}" @if ($period === 'custom') required @endif>
        </div>
        <div>
            <label for="dashboard-cinema" class="form-label">Cụm rạp</label>
            <select id="dashboard-cinema" name="cinema_id" class="form-select">
                <option value="">Tất cả cụm rạp</option>
                @foreach ($cinemas as $cinema)
                    <option value="{{ $cinema->id }}" @selected((string) $cinemaId === (string) $cinema->id)>{{ $cinema->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-outline-light">
            <i class="bi bi-funnel me-1" aria-hidden="true"></i> Áp dụng
        </button>
    </form>

    <div class="row g-3 mb-3">
        @foreach ([
            ['Doanh thu hôm nay', number_format($stats['revenue_today'], 0, ',', '.') . ' đ', 'bi-cash-stack', 'Đã thanh toán'],
            ['Doanh thu tháng này', number_format($stats['revenue_month'], 0, ',', '.') . ' đ', 'bi-graph-up-arrow', 'Theo ngày thanh toán'],
            ['Vé đã thanh toán hôm nay', number_format($stats['tickets_today']), 'bi-ticket-perforated', 'Vé thuộc giao dịch thành công'],
            ['Đơn đặt hôm nay', number_format($stats['bookings_today']), 'bi-receipt', 'Tất cả trạng thái đơn'],
            ['Phim đang chiếu', number_format($stats['movies_now_showing']), 'bi-film', 'Theo trạng thái hiện tại'],
            ['Suất chiếu hôm nay', number_format($stats['showtimes_today']), 'bi-calendar2-week', 'Lịch đã lên trong ngày'],
            ['Khách hàng', number_format($stats['customers']), 'bi-people', 'Tài khoản khách hàng'],
            ['Lấp đầy ghế hôm nay', number_format($stats['occupancy'], 1) . '%', 'bi-grid-3x3', 'Ghế đặt/giữ trên sức chứa hoạt động'],
        ] as [$label, $value, $icon, $note])
            <div class="col-6 col-xl-3">
                <article class="admin-stat-card">
                    <div class="admin-stat-top">
                        <span>{{ $label }}</span>
                        <span class="admin-stat-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                    </div>
                    <strong class="admin-stat-value">{{ $value }}</strong>
                    <div class="admin-stat-note">{{ $note }}</div>
                </article>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <section class="admin-panel">
                <div class="admin-panel-heading">
                    <div>
                        <h2>Doanh thu theo ngày</h2>
                        <div class="small text-secondary mt-1">
                            {{ $rangeStart->format('d/m/Y') }} – {{ $rangeEnd->format('d/m/Y') }}
                            · Tổng {{ number_format($stats['period_revenue'], 0, ',', '.') }} đ
                        </div>
                    </div>
                    <span class="badge text-bg-dark border border-secondary">Đã thanh toán</span>
                </div>
                <div class="admin-panel-body">
                    @if ($revenueTrend->sum('amount') > 0)
                        <div class="admin-revenue-chart" role="img" aria-label="Biểu đồ doanh thu theo ngày">
                            @foreach ($revenueTrend as $day)
                                @php($height = max(3, (int) round($day['amount'] / $maxDailyRevenue * 100)))
                                <div class="admin-revenue-day" title="{{ $day['date']->format('d/m/Y') }}: {{ number_format($day['amount'], 0, ',', '.') }} đ">
                                    <div class="admin-revenue-bar-area">
                                        <span class="admin-revenue-bar" style="height: {{ $height }}%"></span>
                                    </div>
                                    <span class="admin-revenue-label">{{ $day['date']->format('d/m') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="admin-empty-state">
                            <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                            Chưa có giao dịch thanh toán thành công trong khoảng thời gian này.
                        </div>
                    @endif
                </div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="admin-panel">
                <div class="admin-panel-heading">
                    <h2>Cảnh báo vận hành</h2>
                    <i class="bi bi-shield-check text-success" aria-hidden="true"></i>
                </div>
                <div class="admin-panel-body">
                    @if ($roomsWithoutSeats > 0)
                        <div class="admin-alert-row">
                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                            <span>{{ $roomsWithoutSeats }} phòng đang hoạt động chưa có sơ đồ ghế.</span>
                            <a class="ms-auto" href="{{ route('admin.rooms.index') }}">Xem</a>
                        </div>
                    @else
                        <div class="admin-empty-state py-3">
                            <i class="bi bi-check2-circle text-success" aria-hidden="true"></i>
                            Không có cảnh báo sơ đồ ghế.
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-6">
            <section class="admin-panel">
                <div class="admin-panel-heading">
                    <h2>Suất chiếu sắp bắt đầu hôm nay</h2>
                    <a href="{{ route('admin.showtimes.index') }}" class="small link-light">Tất cả suất chiếu</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Giờ</th><th>Phim</th><th>Rạp / phòng</th><th>Ghế</th></tr></thead>
                        <tbody>
                            @forelse ($upcomingShowtimes as $showtime)
                                <tr>
                                    <td class="text-nowrap">{{ $showtime->starts_at->format('H:i') }}</td>
                                    <td>{{ $showtime->movie?->title ?? 'Phim đã gỡ' }}</td>
                                    <td>{{ $showtime->room?->cinema?->name }}<div class="small text-secondary">{{ $showtime->room?->name }}</div></td>
                                    <td>{{ $showtime->occupied_seats_count }}/{{ $showtime->room?->active_seats_count ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><div class="admin-empty-state">Không còn suất chiếu sắp bắt đầu hôm nay.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="col-xl-6">
            <section class="admin-panel">
                <div class="admin-panel-heading">
                    <h2>Đơn đặt vé mới nhất</h2>
                    <a href="{{ route('admin.bookings.index') }}" class="small link-light">Tất cả đơn</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Phim</th><th>Thanh toán</th></tr></thead>
                        <tbody>
                            @forelse ($recentBookings as $booking)
                                <tr>
                                    <td><a class="text-warning text-decoration-none" href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->booking_code }}</a><div class="small text-secondary">{{ $booking->created_at->format('d/m H:i') }}</div></td>
                                    <td>{{ $booking->user?->name ?? 'Tài khoản đã xóa' }}</td>
                                    <td>{{ $booking->showtime?->movie?->title ?? 'Phim đã gỡ' }}</td>
                                    <td><span class="badge {{ $booking->payment_status === 'paid' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $booking->payment_status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><div class="admin-empty-state">Chưa có đơn đặt vé.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelector('[data-dashboard-period]')?.addEventListener('change', (event) => {
        const isCustom = event.target.value === 'custom';
        document.querySelectorAll('[data-custom-range]').forEach((field) => {
            field.classList.toggle('d-none', !isCustom);
            field.querySelector('input').required = isCustom;
        });
    });
</script>
@endpush
