@extends('layouts.app')

@section('title', 'Thêm suất chiếu - YUNFLIMS')

@section('content')
<div class="container py-5" style="max-width: 800px">
    <h2 class="fw-bold mb-4">THÊM SUẤT CHIẾU</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($movies->isEmpty())
        <div class="alert alert-warning">
            Chưa có phim. Hãy thêm phim trong mục Quản lý phim trước.
        </div>
    @endif

    @if ($rooms->isEmpty())
        <div class="alert alert-warning">
            Chưa có phòng chiếu đang hoạt động. Hãy kiểm tra dữ liệu rạp và phòng.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.showtimes.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Phim *</label>

            <select name="movie_id" class="form-select" required>
                <option value="">-- Chọn phim --</option>

                @foreach ($movies as $movie)
                    <option
                        value="{{ $movie->id }}"
                        @selected(old('movie_id') == $movie->id)
                    >
                        {{ $movie->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Rạp và phòng chiếu *</label>

            <select name="room_id" class="form-select" required>
                <option value="">-- Chọn phòng --</option>

                @foreach ($rooms as $room)
                    <option
                        value="{{ $room->id }}"
                        @selected(old('room_id') == $room->id)
                    >
                        {{ $room->cinema->name }} — {{ $room->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Định dạng *</label>
            <select name="format_id" class="form-select" required>
                <option value="">-- Chọn định dạng --</option>
                @foreach ($formats as $format)
                    <option value="{{ $format->id }}" @selected(old('format_id') == $format->id)>{{ $format->name }}</option>
                @endforeach
            </select>
            <small class="text-secondary">Máy chủ kiểm tra định dạng phim và phòng trước khi lưu.</small>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Thời gian bắt đầu *</label>

                <input
                    type="datetime-local"
                    name="starts_at"
                    class="form-control"
                    value="{{ old('starts_at') }}"
                    required
                >
            </div>

            <div class="col-md-6">
                <label class="form-label">Thời gian kết thúc (tự tính theo thời lượng phim)</label>

                <input
                    type="datetime-local"
                    name="ends_at"
                    class="form-control"
                    value="{{ old('ends_at') }}"
                    readonly
                >
            </div>
        </div>

        <div class="mt-3 mb-3">
            <label class="form-label">Giá vé (VNĐ) *</label>

            <input
                type="number"
                name="price"
                class="form-control"
                min="0"
                max="99999999.99"
                step="1000"
                value="{{ old('price', 80000) }}"
                required
            >
        </div>

        <div class="mb-4">
            <label class="form-label">Trạng thái *</label>

            <select name="status" class="form-select" required>
                <option
                    value="scheduled"
                    @selected(old('status', 'scheduled') === 'scheduled')
                >
                    Đã lên lịch
                </option>

                <option
                    value="cancelled"
                    @selected(old('status') === 'cancelled')
                >
                    Đã hủy
                </option>
            </select>
        </div>

        <button type="submit" class="btn btn-red">
            Lưu suất chiếu
        </button>

        <a
            href="{{ route('admin.showtimes.index') }}"
            class="btn btn-outline-light ms-2"
        >
            Quay lại
        </a>
    </form>
</div>
<script>
    const movieDurations = @json($movies->pluck('duration', 'id'));
    const movieSelect = document.querySelector('[name="movie_id"]');
    const startsInput = document.querySelector('[name="starts_at"]');
    const endsInput = document.querySelector('[name="ends_at"]');

    function updateEndTime() {
        const duration = Number(movieDurations[movieSelect.value] || 0);
        const start = new Date(startsInput.value);
        if (!duration || Number.isNaN(start.getTime())) {
            endsInput.value = '';
            return;
        }
        start.setMinutes(start.getMinutes() + duration);
        const local = new Date(start.getTime() - start.getTimezoneOffset() * 60000);
        endsInput.value = local.toISOString().slice(0, 16);
    }

    movieSelect.addEventListener('change', updateEndTime);
    startsInput.addEventListener('change', updateEndTime);
</script>
@endsection