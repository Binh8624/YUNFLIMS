
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

    @if ($formats->isEmpty())
        <div class="alert alert-warning">
            Chưa có định dạng phim đang hoạt động.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.showtimes.store') }}">
        @csrf

        {{-- Chọn phim --}}
        <div class="mb-3">
            <label class="form-label">Phim *</label>

            <select
                name="movie_id"
                id="movie_id"
                class="form-select"
                required
            >
                <option value="">-- Chọn phim --</option>

                @foreach ($movies as $movie)
                    <option
                        value="{{ $movie->id }}"
                        data-format-ids="{{ $movie->formats->pluck('id')->implode(',') }}"
                        data-duration="{{ $movie->duration }}"
                        @selected(old('movie_id') == $movie->id)
                    >
                        {{ $movie->title }}
                        — {{ $movie->duration }} phút
                        ({{ $movie->formats->pluck('name')->join(', ') ?: 'Chưa có định dạng' }})
                    </option>
                @endforeach
            </select>

            <small class="text-secondary">
                Danh sách định dạng được lấy từ các định dạng đã cấu hình cho phim.
            </small>
        </div>

        {{-- Chọn định dạng --}}
        <div class="mb-3">
            <label class="form-label">Định dạng suất chiếu *</label>

            <select
                name="format_id"
                id="format_id"
                class="form-select"
                required
            >
                <option value="">-- Chọn định dạng --</option>

                @foreach ($formats as $format)
                    <option
                        value="{{ $format->id }}"
                        @selected(old('format_id') == $format->id)
                    >
                        {{ $format->name }}
                    </option>
                @endforeach
            </select>

            <small class="text-secondary">
                Chỉ có thể chọn định dạng mà phim đang hỗ trợ.
            </small>
        </div>

        {{-- Chọn rạp và phòng --}}
        <div class="mb-3">
            <label class="form-label">Rạp và phòng chiếu *</label>

            <select
                name="room_id"
                id="room_id"
                class="form-select"
                required
            >
                <option value="">-- Chọn phòng --</option>

                @foreach ($rooms as $room)
                    <option
                        value="{{ $room->id }}"
                        data-format-ids="{{ $room->formats->pluck('id')->implode(',') }}"
                        @selected(old('room_id') == $room->id)
                    >
                        {{ $room->cinema->name }}
                        — {{ $room->room_code }}
                        — {{ $room->name }}
                        ({{ $room->formats->pluck('name')->join(', ') ?: 'Chưa có định dạng' }})
                    </option>
                @endforeach
            </select>

            <small class="text-secondary">
                Chỉ những phòng hỗ trợ định dạng suất chiếu đã chọn mới được hiển thị.
            </small>
        </div>

        {{-- Ngày và giờ --}}
        <div class="row g-3">
            <div class="col-md-6">
                <label for="starts_on" class="form-label">Ngày chiếu *</label>

                <input
                    type="date"
                    id="starts_on"
                    name="starts_on"
                    class="form-control"
                    min="{{ now()->toDateString() }}"
                    value="{{ old('starts_on') }}"
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
                    value="{{ old('starts_time') }}"
                    required
                >
            </div>
        </div>

        {{-- Thời gian kết thúc --}}
        <div class="mt-2 mb-3">
            <label for="ends_at" class="form-label">
                Thời gian kết thúc (tự tính theo thời lượng phim)
            </label>

            <input
                type="datetime-local"
                id="ends_at"
                class="form-control"
                readonly
            >
        </div>

        {{-- Giá vé --}}
        <div class="row g-3 mt-1 mb-3">
            <div class="col-md-4">
                <label for="price" class="form-label">
                    Giá ghế thường (VNĐ) *
                </label>

                <input
                    type="number"
                    id="price"
                    name="price"
                    class="form-control"
                    min="0"
                    max="99999999.99"
                    step="1000"
                    value="{{ old('price', 80000) }}"
                    required
                >
            </div>

            <div class="col-md-4">
                <label for="vip_price" class="form-label">
                    Giá ghế VIP (VNĐ) *
                </label>

                <input
                    type="number"
                    id="vip_price"
                    name="vip_price"
                    class="form-control"
                    min="0"
                    max="99999999.99"
                    step="1000"
                    value="{{ old('vip_price', 100000) }}"
                    required
                >
            </div>

            <div class="col-md-4">
                <label for="couple_price" class="form-label">
                    Giá ghế đôi (VNĐ) *
                </label>

                <input
                    type="number"
                    id="couple_price"
                    name="couple_price"
                    class="form-control"
                    min="0"
                    max="99999999.99"
                    step="1000"
                    value="{{ old('couple_price', 160000) }}"
                    required
                >
            </div>
        </div>

        {{-- Trạng thái --}}
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
document.addEventListener('DOMContentLoaded', function () {
    const movieSelect = document.querySelector('#movie_id');
    const roomSelect = document.querySelector('#room_id');
    const formatSelect = document.querySelector('#format_id');

    const startsOnInput = document.querySelector('#starts_on');
    const startsTimeInput = document.querySelector('#starts_time');
    const endsInput = document.querySelector('#ends_at');

    if (
        !movieSelect ||
        !roomSelect ||
        !formatSelect ||
        !startsOnInput ||
        !startsTimeInput ||
        !endsInput
    ) {
        return;
    }

    // Lấy danh sách ID định dạng từ data-format-ids.
    function getFormatIds(option) {
        return (option?.dataset.formatIds || '')
            .split(',')
            .map(id => id.trim())
            .filter(id => id !== '');
    }

    // Lọc định dạng theo phim đang chọn.
    function updateMovieFormats() {
        const selectedMovie = movieSelect.selectedOptions[0];
        const supportedFormatIds = new Set(getFormatIds(selectedMovie));

        const currentFormatId = formatSelect.value;
        let firstSupportedFormat = '';

        Array.from(formatSelect.options).forEach(option => {
            if (!option.value) {
                option.disabled = false;
                option.hidden = false;
                return;
            }

            const supported = supportedFormatIds.has(option.value);

            option.disabled = !supported;
            option.hidden = !supported;

            if (supported && firstSupportedFormat === '') {
                firstSupportedFormat = option.value;
            }
        });

        // Giữ định dạng hiện tại nếu phim vẫn hỗ trợ.
        // Nếu không còn phù hợp thì chọn định dạng hợp lệ đầu tiên.
        if (!supportedFormatIds.has(currentFormatId)) {
            formatSelect.value = firstSupportedFormat;
        }

        updateCompatibleRooms();
    }

    // Lọc phòng theo định dạng suất chiếu đã chọn.
    function updateCompatibleRooms() {
        const selectedFormatId = formatSelect.value;

        Array.from(roomSelect.options).forEach(option => {
            if (!option.value) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const roomFormats = getFormatIds(option);
            const compatible = selectedFormatId !== ''
                && roomFormats.includes(selectedFormatId);

            option.hidden = !compatible;
            option.disabled = !compatible;
        });

        // Bỏ chọn phòng cũ nếu phòng không còn tương thích.
        if (roomSelect.selectedOptions[0]?.disabled) {
            roomSelect.value = '';
        }
    }

    // Tự tính thời gian kết thúc.
    function updateEndTime() {
        const selectedMovie = movieSelect.selectedOptions[0];
        const duration = Number(selectedMovie?.dataset.duration || 0);

        if (!duration || !startsOnInput.value || !startsTimeInput.value) {
            endsInput.value = '';
            return;
        }

        const start = new Date(
            `${startsOnInput.value}T${startsTimeInput.value}`
        );

        if (Number.isNaN(start.getTime())) {
            endsInput.value = '';
            return;
        }

        start.setMinutes(start.getMinutes() + duration);

        const local = new Date(
            start.getTime() - start.getTimezoneOffset() * 60000
        );

        endsInput.value = local.toISOString().slice(0, 16);
    }

    // Sự kiện thay đổi phim.
    movieSelect.addEventListener('change', function () {
        updateMovieFormats();
        updateEndTime();
    });

    // Sự kiện thay đổi định dạng.
    formatSelect.addEventListener('change', updateCompatibleRooms);

    // Sự kiện thay đổi phòng.
    // Không cần thay đổi định dạng phim khi chọn phòng.

    startsOnInput.addEventListener('change', updateEndTime);
    startsTimeInput.addEventListener('change', updateEndTime);

    // Khởi tạo khi tải trang, hỗ trợ old() sau validation.
    updateMovieFormats();
    updateEndTime();
});
</script>
@endsection
