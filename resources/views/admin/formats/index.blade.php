@extends('layouts.app')

@section('title', 'Định dạng phim - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="mb-4"><h1 class="h2 fw-bold mb-1">Định dạng phim</h1><p class="text-secondary mb-0">Định dạng phải được hỗ trợ đồng thời bởi phim và phòng trước khi tạo suất.</p></div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div class="card bg-dark border-secondary mb-4"><div class="card-body">
        <h2 class="h5">Thêm định dạng</h2>
        <form method="POST" action="{{ route('admin.formats.store') }}" class="row g-2 align-items-end">@csrf
            <div class="col-md-5"><label class="form-label">Tên định dạng</label><input name="name" class="form-control" maxlength="50" placeholder="Ví dụ: 4DX" required></div>
            <div class="col-md-3"><label class="form-label">Trạng thái</label><select name="is_active" class="form-select"><option value="1">Hoạt động</option><option value="0">Tạm ngưng</option></select></div>
            <div class="col-auto"><button class="btn btn-red">Thêm</button></div>
        </form>
    </div></div>
    <div class="card bg-dark border-secondary"><div class="table-responsive"><table class="table table-dark table-hover align-middle mb-0">
        <thead><tr><th>Tên</th><th>Slug</th><th>Phim</th><th>Phòng</th><th>Suất</th><th>Trạng thái</th><th>Cập nhật</th></tr></thead>
        <tbody>@forelse ($formats as $format)
            <tr><td>{{ $format->name }}</td><td>{{ $format->slug }}</td><td>{{ $format->movies_count }}</td><td>{{ $format->rooms_count }}</td><td>{{ $format->showtimes_count }}</td><td>{{ $format->is_active ? 'Hoạt động' : 'Tạm ngưng' }}</td>
                <td><div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.formats.update', $format) }}" class="d-flex gap-2">@csrf @method('PUT')<input name="name" value="{{ $format->name }}" maxlength="50" class="form-control form-control-sm" required><select name="is_active" class="form-select form-select-sm"><option value="1" @selected($format->is_active)>Hoạt động</option><option value="0" @selected(!$format->is_active)>Tạm ngưng</option></select><button class="btn btn-sm btn-primary">Lưu</button></form>
                    <form method="POST" action="{{ route('admin.formats.destroy', $format) }}" onsubmit="return confirm('Xóa định dạng này?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" {{ $format->movies_count || $format->rooms_count || $format->showtimes_count ? 'disabled' : '' }}>Xóa</button></form>
                </div></td></tr>
        @empty<tr><td colspan="7" class="text-center text-secondary py-4">Chưa có định dạng.</td></tr>@endforelse</tbody>
    </table></div></div>
    <div class="mt-3">{{ $formats->links() }}</div>
</div>
@endsection
