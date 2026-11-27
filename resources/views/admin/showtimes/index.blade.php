@extends('layouts.app')

@section('title', 'Quản lý suất chiếu - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold">QUẢN LÝ SUẤT CHIẾU</h2>
            <p class="text-secondary mb-0">
                Lịch chiếu phim tại các rạp YUNFLIMS
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.showtimes.auto') }}" class="btn btn-outline-light">Tạo lịch tự động</a>
        <a href="{{ route('admin.showtimes.create') }}" class="btn btn-red">
            <i class="bi bi-plus-circle"></i> Thêm suất chiếu
        </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <form method="GET" class="row g-2 mb-3">
        <div class="col-12 col-md-4 col-xl-3">
            <label for="showtime-search" class="visually-hidden">Tìm phim hoặc phòng</label>
            <input id="showtime-search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Phim hoặc phòng">
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <label for="showtime-movie" class="visually-hidden">Phim</label>
            <select id="showtime-movie" name="movie_id" class="form-select">
                <option value="">Mọi phim</option>
                @foreach ($movies as $movie)
                    <option value="{{ $movie->id }}" @selected((string) ($filters['movie_id'] ?? '') === (string) $movie->id)>{{ $movie->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <label for="showtime-date" class="visually-hidden">Ngày chiếu</label>
            <input id="showtime-date" type="date" name="date" class="form-control" value="{{ $filters['date'] ?? '' }}">
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <label for="showtime-cinema" class="visually-hidden">Rạp</label>
            <select id="showtime-cinema" name="cinema_id" class="form-select">
                <option value="">Mọi rạp</option>
                @foreach ($cinemas as $cinema)
                    <option value="{{ $cinema->id }}" @selected((string) ($filters['cinema_id'] ?? '') === (string) $cinema->id)>{{ $cinema->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <label for="showtime-room" class="visually-hidden">Phòng</label>
            <select id="showtime-room" name="room_id" class="form-select">
                <option value="">Mọi phòng</option>
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}" @selected((string) ($filters['room_id'] ?? '') === (string) $room->id)>{{ $room->cinema->name }} · {{ $room->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4 col-xl-1">
            <label for="showtime-status" class="visually-hidden">Trạng thái</label>
            <select id="showtime-status" name="status" class="form-select">
                <option value="">Mọi trạng thái</option>
                <option value="scheduled" @selected(($filters['status'] ?? '') === 'scheduled')>Đã lên lịch</option>
                <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Đã hủy</option>
            </select>
        </div>
        <div class="col-12 col-xl-auto"><button class="btn btn-outline-light w-100">Lọc suất</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>Phim</th>
                    <th>Rạp / Phòng</th>
                    <th>Định dạng phim / phòng</th>
                    <th>Bắt đầu</th>
                    <th>Kết thúc</th>
                    <th>Giá vé</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($showtimes as $showtime)
                    <tr>
                        <td>{{ $showtime->movie->title }}</td>

                        <td>
                            {{ $showtime->room->cinema->name }}<br>
                            <small class="text-secondary">
                                {{ $showtime->room->name }}
                            </small>
                        </td>
                        <td>{{ $showtime->movie->format?->name ?? 'Chưa thiết lập' }} / {{ $showtime->room->format?->name ?? 'Chưa thiết lập' }}</td>

                        <td>
                            {{ $showtime->starts_at->format('d/m/Y H:i') }}
                        </td>

                        <td>
                            {{ $showtime->ends_at->format('d/m/Y H:i') }}
                        </td>

                        <td class="text-nowrap">
                            Thường: {{ number_format((float) $showtime->price, 0, ',', '.') }} đ<br>
                            VIP: {{ number_format((float) ($showtime->vip_price ?? $showtime->price), 0, ',', '.') }} đ<br>
                            Đôi: {{ number_format((float) ($showtime->couple_price ?? $showtime->price), 0, ',', '.') }} đ
                        </td>

                        <td>
                            @if ($showtime->status === 'scheduled')
                                <span class="badge bg-success">Đã lên lịch</span>
                            @else
                                <span class="badge bg-secondary">Đã hủy</span>
                            @endif
                        </td>

                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a
                                    href="{{ route('admin.showtimes.edit', $showtime) }}"
                                    class="btn btn-sm btn-primary"
                                >
                                    Sửa
                                </a>

                                @if ($showtime->status === 'scheduled')
                                    <form
                                        method="POST"
                                        action="{{ route('admin.showtimes.destroy', $showtime) }}"
                                        onsubmit="return confirm('Bạn có chắc muốn hủy suất chiếu này?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            class="btn btn-sm btn-danger"
                                            type="submit"
                                        >
                                            Hủy
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            Chưa có suất chiếu. Hãy thêm suất chiếu đầu tiên.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $showtimes->links() }}
</div>
@endsection