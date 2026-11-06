@extends('layouts.app')

@section('title', 'Đánh giá phim - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="mb-4"><h1 class="h2 fw-bold mb-1">Đánh giá phim</h1><p class="text-secondary mb-0">Kiểm duyệt nội dung đánh giá của khách hàng.</p></div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <form method="GET" class="row g-2 mb-3"><div class="col-md-5"><input name="search" class="form-control" value="{{ request('search') }}" placeholder="Phim hoặc người dùng"></div><div class="col-md-2"><select name="rating" class="form-select"><option value="">Mọi số sao</option>@foreach (range(1, 5) as $rating)<option value="{{ $rating }}" @selected(request('rating') == $rating)>{{ $rating }} sao</option>@endforeach</select></div><div class="col-auto"><button class="btn btn-outline-light">Lọc</button></div></form>
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div class="card bg-dark border-secondary"><div class="table-responsive"><table class="table table-dark table-hover align-middle mb-0">
        <thead><tr><th>Phim</th><th>Người đánh giá</th><th>Điểm</th><th>Nhận xét</th><th>Ngày</th><th></th></tr></thead>
        <tbody>@forelse ($reviews as $review)<tr><td>{{ $review->movie->title }}</td><td>{{ $review->user->name }}</td><td class="text-warning">{{ $review->rating }}/5</td><td>{{ $review->comment ?: '—' }}</td><td>{{ $review->created_at->format('d/m/Y') }}</td><td><form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Xóa đánh giá này?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Xóa</button></form></td></tr>@empty<tr><td colspan="6" class="text-center text-secondary py-4">Chưa có đánh giá.</td></tr>@endforelse</tbody>
    </table></div></div>
    <div class="mt-3">{{ $reviews->links() }}</div>
</div>
@endsection
