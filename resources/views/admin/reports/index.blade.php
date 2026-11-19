@extends('layouts.app')

@section('title', 'Báo cáo doanh thu - YUNFLIMS Admin')

@section('content')
<div class="container-fluid admin-dashboard">
    <div class="admin-page-header">
        <div>
            <span class="admin-page-eyebrow">PHÂN TÍCH KINH DOANH</span>
            <h1>Báo cáo doanh thu</h1>
            <p>Chỉ tổng hợp booking đã thanh toán thành công theo thời điểm ghi nhận giao dịch.</p>
        </div>
        <a
            href="{{ route('admin.reports.export', array_filter(['from' => request('from'), 'to' => request('to'), 'cinema_id' => $cinemaId])) }}"
            class="btn btn-outline-light"
        >
            <i class="bi bi-download me-1" aria-hidden="true"></i> Xuất CSV
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('admin.reports.index') }}" class="admin-dashboard-filters">
        <div>
            <label for="report-from" class="form-label">Từ ngày</label>
            <input id="report-from" type="date" name="from" class="form-control" value="{{ $from->format('Y-m-d') }}" required>
        </div>
        <div>
            <label for="report-to" class="form-label">Đến ngày</label>
            <input id="report-to" type="date" name="to" class="form-control" value="{{ $to->format('Y-m-d') }}" required>
        </div>
        <div>
            <label for="report-cinema" class="form-label">Cụm rạp</label>
            <select id="report-cinema" name="cinema_id" class="form-select">
                <option value="">Tất cả cụm rạp</option>
                @foreach ($cinemas as $cinema)
                    <option value="{{ $cinema->id }}" @selected((string) $cinemaId === (string) $cinema->id)>{{ $cinema->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-outline-light">
            <i class="bi bi-funnel me-1" aria-hidden="true"></i> Lọc báo cáo
        </button>
    </form>

    <div class="row g-3 mb-3">
        @foreach ([
            ['Doanh thu thực thu', number_format($revenue, 0, ',', '.') . ' đ', 'bi-cash-stack'],
            ['Giao dịch thành công', number_format($bookingCount), 'bi-check2-circle'],
            ['Vé đã thanh toán', number_format($tickets), 'bi-ticket-perforated'],
            ['Giá trị trung bình/đơn', number_format($bookingCount ? $revenue / $bookingCount : 0, 0, ',', '.') . ' đ', 'bi-receipt'],
        ] as [$label, $value, $icon])
            <div class="col-6 col-xl-3">
                <article class="admin-stat-card">
                    <div class="admin-stat-top">
                        <span>{{ $label }}</span>
                        <span class="admin-stat-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                    </div>
                    <strong class="admin-stat-value">{{ $value }}</strong>
                    <div class="admin-stat-note">{{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</div>
                </article>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <section class="admin-panel">
                <div class="admin-panel-heading">
                    <h2>Doanh thu theo ngày</h2>
                    <span class="badge text-bg-dark border border-secondary">VND · Đã thanh toán</span>
                </div>
                <div class="admin-panel-body">
                    @if ($trend->sum('revenue') > 0)
                        <div class="admin-revenue-chart" role="img" aria-label="Biểu đồ doanh thu theo ngày">
                            @foreach ($trend as $day)
                                @php($height = max(3, (int) round($day['revenue'] / $maxDailyRevenue * 100)))
                                <div class="admin-revenue-day" title="{{ $day['date']->format('d/m/Y') }}: {{ number_format($day['revenue'], 0, ',', '.') }} đ">
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
                            Không có giao dịch thành công trong khoảng thời gian đã chọn.
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <section class="admin-panel">
                <div class="admin-panel-heading"><h2>Doanh thu theo cụm rạp</h2></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Cụm rạp</th><th>Đơn đã thanh toán</th><th class="text-end">Doanh thu</th></tr></thead>
                        <tbody>
                            @forelse ($cinemaRevenue as $row)
                                <tr>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ number_format($row->paid_bookings) }}</td>
                                    <td class="text-end text-warning">{{ number_format((float) $row->revenue, 0, ',', '.') }} đ</td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><div class="admin-empty-state">Chưa có doanh thu rạp trong kỳ.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="col-xl-6">
            <section class="admin-panel">
                <div class="admin-panel-heading"><h2>Phim có doanh thu cao</h2></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Phim</th><th>Đơn</th><th class="text-end">Doanh thu</th></tr></thead>
                        <tbody>
                            @forelse ($movieRevenue as $row)
                                <tr>
                                    <td>{{ $row->title }}</td>
                                    <td>{{ number_format($row->paid_bookings) }}</td>
                                    <td class="text-end text-warning">{{ number_format((float) $row->revenue, 0, ',', '.') }} đ</td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><div class="admin-empty-state">Chưa có doanh thu phim trong kỳ.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="col-12">
            <section class="admin-panel">
                <div class="admin-panel-heading"><h2>Phương thức thanh toán</h2></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Phương thức</th><th>Giao dịch</th><th class="text-end">Doanh thu</th></tr></thead>
                        <tbody>
                            @forelse ($paymentMethods as $row)
                                <tr>
                                    <td>{{ $row->method }}</td>
                                    <td>{{ number_format($row->transactions) }}</td>
                                    <td class="text-end text-warning">{{ number_format((float) $row->revenue, 0, ',', '.') }} đ</td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><div class="admin-empty-state">Chưa có giao dịch thanh toán.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <p class="small text-secondary mt-3 mb-0">
        Hệ thống hiện chưa lưu doanh thu dịch vụ, giảm giá hoặc hoàn tiền riêng; báo cáo chỉ phản ánh tổng tiền booking đã thanh toán.
    </p>
</div>
@endsection
