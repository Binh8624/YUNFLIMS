@extends('layouts.app')

@section('title', 'Nhật ký hoạt động - YUNFLIMS Admin')

@section('content')
<div class="container-fluid admin-dashboard">
    <div class="admin-page-header">
        <div>
            <span class="admin-page-eyebrow">KIỂM SOÁT VẬN HÀNH</span>
            <h1>Nhật ký hoạt động</h1>
            <p>Lưu người thao tác, route, đối tượng và trạng thái phản hồi; không lưu payload, mật khẩu hoặc token.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="admin-dashboard-filters">
        <div>
            <label for="activity-search" class="form-label">Tìm quản trị viên / đối tượng</label>
            <input id="activity-search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Tên, email, loại hoặc mã đối tượng">
        </div>
        <div>
            <label for="activity-action" class="form-label">Thao tác</label>
            <select id="activity-action" name="action" class="form-select">
                <option value="">Tất cả thao tác</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-outline-light"><i class="bi bi-funnel me-1" aria-hidden="true"></i> Lọc nhật ký</button>
    </form>

    <section class="admin-panel">
        <div class="admin-panel-heading">
            <h2>Hoạt động quản trị</h2>
            <span class="small text-secondary">{{ number_format($logs->total()) }} bản ghi</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Thời gian</th><th>Người thực hiện</th><th>Thao tác</th><th>Đối tượng</th><th>HTTP</th><th>Kết quả</th></tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->actor?->name ?? 'Tài khoản đã xóa' }}</td>
                            <td class="text-break">{{ $log->action }}</td>
                            <td>{{ $log->subject_type ? $log->subject_type . ' #' . $log->subject_id : '—' }}</td>
                            <td><span class="badge text-bg-dark border border-secondary">{{ $log->http_method }}</span></td>
                            <td><span class="badge {{ $log->response_status < 400 ? 'text-bg-success' : 'text-bg-warning' }}">{{ $log->response_status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="admin-empty-state"><i class="bi bi-journal-text" aria-hidden="true"></i>Chưa có hoạt động quản trị được ghi nhận.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="p-3">{{ $logs->links() }}</div>
        @endif
    </section>
</div>
@endsection
