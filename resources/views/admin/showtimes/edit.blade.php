@extends('layouts.app')

@section('title', 'Sửa suất chiếu - YUNFLIMS')

@section('content')
<div class="container py-5" style="max-width: 800px">
    <h2 class="fw-bold mb-4">CHỈNH SỬA SUẤT CHIẾU</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('admin.showtimes.update', $showtime) }}"
    >
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Phim *</label>

            <select name="movie_id" class="form-select" required>
                @foreach ($movies as $movie)
                    <option
                        value="{{ $movie->id }}"
                        @selected(old('movie_id', $showtime->movie_id) == $movie->id)
                    >
                        {{ $movie->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Rạp và phòng chiếu *</label>

            <select name="room_id" class="form-select" required>
                @foreach ($rooms as $room)
                    <option
                        value="{{ $room->id }}"
                        @selected(old('room_id', $showtime->room_id) == $room->id)
                    >
                        {{ $room->cinema->name }} — {{ $room->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Định dạng *</label>
            <select name="format_id" class="form-select" required>
                @foreach ($formats as $format)
                    <option value="{{ $format->id }}" @selected(old('format_id', $showtime->format_id) == $format->id)>{{ $format->name }}</option>
                @endforeach
            </select>
            <small class="text-secondary">Máy chủ kiểm tra định dạng phim và phòng trước khi lưu.</small>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="starts_on" class="form-label">Ngày chiếu *</label>
                <input
                    type="date"
                    id="starts_on"
                    name="starts_on"
                    class="form-control"
                    min="{{ now()->toDateString() }}"
                    value="{{ old('starts_on', $showtime->starts_at->format('Y-m-d')) }}"
                    required
                >
            </div>

            <div class="col-md-6">
                <label for="starts_time" class="form-label">Giờ bắt đầu *</label>
                <input
                    type="time"
                    id="starts_time"
                    name="starts_time"
                    class="form-control"
                    value="{{ old('starts_time', $showtime->starts_at->format('H:i')) }}"
                    required
                >
            </div>
        </div>
        <div class="mt-2 mb-3">
            <label for="ends_at" class="form-label">Thời gian kết thúc (tự tính theo thời lượng phim)</label>
            <input
                type="datetime-local"
                id="ends_at"
                class="form-control"
                value="{{ old('ends_at', $showtime->ends_at->format('Y-m-d\TH:i')) }}"
                readonly
            >
        </div>

        <div class="row g-3 mt-1 mb-3">
            <div class="col-md-4">
                <label for="price" class="form-label">Giá ghế thường (VNĐ) *</label>
                <input type="number" id="price" name="price" class="form-control" min="0" max="99999999.99" step="1000" value="{{ old('price', $showtime->price) }}" required>
            </div>
            <div class="col-md-4">
                <label for="vip_price" class="form-label">Giá ghế VIP (VNĐ) *</label>
                <input type="number" id="vip_price" name="vip_price" class="form-control" min="0" max="99999999.99" step="1000" value="{{ old('vip_price', $showtime->vip_price ?? $showtime->price) }}" required>
            </div>
            <div class="col-md-4">
                <label for="couple_price" class="form-label">Giá ghế đôi (VNĐ) *</label>
                <input type="number" id="couple_price" name="couple_price" class="form-control" min="0" max="99999999.99" step="1000" value="{{ old('couple_price', $showtime->couple_price ?? $showtime->price) }}" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Trạng thái *</label>

            <select name="status" class="form-select" required>
                <option
                    value="scheduled"
                    @selected(old('status', $showtime->status) === 'scheduled')
                >
                    Đã lên lịch
                </option>

                <option
                    value="cancelled"
                    @selected(old('status', $showtime->status) === 'cancelled')
                >
                    Đã hủy
                </option>
            </select>
        </div>

        <button type="submit" class="btn btn-red">
            Lưu thay đổi
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
    const startsOnInput = document.querySelector('[name="starts_on"]');
    const startsTimeInput = document.querySelector('[name="starts_time"]');
    const endsInput = document.querySelector('#ends_at');

    function updateEndTime() {
        const duration = Number(movieDurations[movieSelect.value] || 0);
        const start = new Date(`${startsOnInput.value}T${startsTimeInput.value}`);
        if (!duration || Number.isNaN(start.getTime())) {
            endsInput.value = '';
            return;
        }
        start.setMinutes(start.getMinutes() + duration);
        const local = new Date(start.getTime() - start.getTimezoneOffset() * 60000);
        endsInput.value = local.toISOString().slice(0, 16);
    }

    movieSelect.addEventListener('change', updateEndTime);
    startsOnInput.addEventListener('change', updateEndTime);
    startsTimeInput.addEventListener('change', updateEndTime);
</script>
@endsection