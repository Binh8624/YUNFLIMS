@extends('layouts.app')

@section('title', 'Định dạng phim - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="mb-4"><h1 class="h2 fw-bold mb-1">Định dạng phim</h1><p class="text-secondary mb-0">Định dạng phải được hỗ trợ đồng thời bởi phim và phòng trước khi tạo suất.</p></div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div class="alert alert-info">YUNFLIMS hỗ trợ cố định ba định dạng: 2D, 3D và IMAX. Định dạng đang được dùng không thể thêm, đổi tên hoặc tắt.</div>
    <div class="card bg-dark border-secondary"><div class="table-responsive"><table class="table table-dark table-hover align-middle mb-0">
        <thead><tr><th>Tên</th><th>Slug</th><th>Phim</th><th>Phòng</th><th>Suất</th><th>Trạng thái</th></tr></thead>
        <tbody>@forelse ($formats as $format)
            <tr><td>{{ $format->name }}</td><td>{{ $format->slug }}</td><td>{{ $format->movies_count }}</td><td>{{ $format->rooms_count }}</td><td>{{ $format->showtimes_count }}</td><td>{{ $format->is_active ? 'Hoạt động' : 'Tạm ngưng' }}</td></tr>
        @empty<tr><td colspan="6" class="text-center text-secondary py-4">Chưa có định dạng.</td></tr>@endforelse</tbody>
    </table></div></div>
    <div class="mt-3">{{ $formats->links() }}</div>
</div>
@endsection
