
@extends('layouts.app')

@section('title', 'Quản lý phim - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">QUẢN LÝ PHIM</h2>
            <p class="text-secondary mb-0">
                Quản lý danh sách phim của YUNFLIMS
            </p>
        </div>

        <a href="{{ route('admin.movies.create') }}"
           class="btn btn-red">
            <i class="bi bi-plus-circle"></i> Thêm phim
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-5">
            <input name="search" value="{{ request('search') }}" class="form-control"
                   placeholder="Tìm tên phim hoặc thể loại">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Mọi trạng thái</option>
                <option value="now_showing" @selected(request('status') === 'now_showing')>Đang chiếu</option>
                <option value="coming_soon" @selected(request('status') === 'coming_soon')>Sắp chiếu</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Ẩn</option>
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-outline-light">Lọc phim</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>Poster</th>
                    <th>Tên phim</th>
                    <th>Thể loại</th>
                    <th>Thời lượng</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($movies as $movie)
                    <tr>
                        <td>
                            @if ($movie->poster)
                                <img src="{{ $movie->poster }}"
                                     alt="{{ $movie->title }}"
                                     width="55"
                                     style="aspect-ratio: 2/3; object-fit: cover;">
                            @else
                                Chưa có ảnh
                            @endif
                        </td>

                        <td>{{ $movie->title }}</td>
                        <td>{{ $movie->genre }}</td>
                        <td>{{ $movie->duration }} phút</td>

                        <td>
                            @switch($movie->status)
                                @case('now_showing')
                                    <span class="badge bg-success">Đang chiếu</span>
                                    @break
                                @case('coming_soon')
                                    <span class="badge bg-warning text-dark">Sắp chiếu</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">Ẩn</span>
                            @endswitch
                        </td>

                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.movies.edit', $movie) }}"
                                   class="btn btn-sm btn-primary">
                                    Sửa
                                </a>

                                <form action="{{ route('admin.movies.destroy', $movie) }}"
                                      method="POST"
                                      onsubmit="return confirm('Bạn có chắc muốn xóa phim này?')">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="btn btn-sm btn-danger">
                                        Xóa
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            Chưa có phim nào trong hệ thống.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $movies->links() }}
    </div>

</div>
@endsection
