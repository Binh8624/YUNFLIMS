@extends('layouts.app')

@section('title', 'Quản lý rạp - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h2 fw-bold mb-1">Quản lý rạp</h1><p class="text-secondary mb-0">Quản lý thông tin và trạng thái rạp chiếu.</p></div>
        <a href="{{ route('admin.cinemas.create') }}" class="btn btn-red"><i class="bi bi-plus-lg"></i> Thêm rạp</a>
    </div>
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-5"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Tên rạp, địa chỉ, thành phố"></div>
        <div class="col-auto"><button class="btn btn-outline-light">Tìm kiếm</button></div>
    </form>
    <div class="card bg-dark border-secondary"><div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead><tr><th>Rạp</th><th>Thành phố</th><th>Địa chỉ</th><th>Điện thoại</th><th>Phòng</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody>
                @forelse ($cinemas as $cinema)
                    <tr>
                        <td>{{ $cinema->name }}</td><td>{{ $cinema->city }}</td><td>{{ $cinema->address }}</td><td>{{ $cinema->phone ?: '—' }}</td>
                        <td>{{ $cinema->rooms_count }}</td><td><span class="badge {{ $cinema->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $cinema->is_active ? 'Hoạt động' : 'Tạm ngưng' }}</span></td>
                        <td><div class="d-flex gap-2"><a class="btn btn-sm btn-primary" href="{{ route('admin.cinemas.edit', $cinema) }}">Sửa</a>
                            <form method="POST" action="{{ route('admin.cinemas.destroy', $cinema) }}" onsubmit="return confirm('Xóa rạp này?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" {{ $cinema->rooms_count ? 'disabled' : '' }}>Xóa</button></form></div></td>
                    </tr>
                @empty<tr><td colspan="7" class="text-center text-secondary py-4">Chưa có rạp phù hợp.</td></tr>@endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $cinemas->links() }}</div>
</div>
@endsection
