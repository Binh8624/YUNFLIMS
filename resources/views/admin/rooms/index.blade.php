@extends('layouts.app')

@section('title', 'Quản lý phòng chiếu - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h2 fw-bold mb-1">Quản lý phòng chiếu</h1><p class="text-secondary mb-0">Phòng, sức chứa và sơ đồ ghế theo rạp.</p></div>
        <a href="{{ route('admin.rooms.create') }}" class="btn btn-red"><i class="bi bi-plus-lg"></i> Thêm phòng</a>
    </div>
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Tên phòng hoặc rạp"></div>
        <div class="col-md-3"><select name="cinema_id" class="form-select"><option value="">Tất cả rạp</option>@foreach ($cinemas as $cinema)<option value="{{ $cinema->id }}" @selected(request('cinema_id') == $cinema->id)>{{ $cinema->name }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-outline-light">Lọc</button></div>
    </form>
    <div class="card bg-dark border-secondary"><div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead><tr><th>Rạp</th><th>Phòng</th><th>Sức chứa</th><th>Ghế đã tạo</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody>
                @forelse ($rooms as $room)
                    <tr><td>{{ $room->cinema->name }}</td><td>{{ $room->name }}</td><td>{{ $room->total_seats }}</td><td>{{ $room->seats_count }}</td>
                        <td><span class="badge {{ $room->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $room->is_active ? 'Hoạt động' : 'Tạm ngưng' }}</span></td>
                        <td><div class="d-flex flex-wrap gap-2"><a class="btn btn-sm btn-outline-info" href="{{ route('admin.rooms.seats.index', $room) }}">Sơ đồ ghế</a><a class="btn btn-sm btn-primary" href="{{ route('admin.rooms.edit', $room) }}">Sửa</a>
                            <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}" onsubmit="return confirm('Xóa phòng này?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Xóa</button></form></div></td></tr>
                @empty<tr><td colspan="6" class="text-center text-secondary py-4">Chưa có phòng phù hợp.</td></tr>@endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $rooms->links() }}</div>
</div>
@endsection
