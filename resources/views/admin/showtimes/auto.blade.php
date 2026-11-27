@extends('layouts.app')

@section('title', 'Tạo lịch tự động - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="mb-4">
        <h1 class="h2 fw-bold mb-1">Tạo suất chiếu tự động</h1>
        <p class="text-secondary mb-0">
            Chọn phim, định dạng, khung giờ và phòng chiếu.
            Hệ thống tự tính thời lượng phim, thời gian vệ sinh 15 phút
            và bỏ qua các lịch bị xung đột.
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">
            <form method="POST"
                  action="{{ route('admin.showtimes.auto.preview') }}"
                  class="row g-3">
                @csrf

                {{-- Chọn phim --}}
                <div class="col-md-6">
                    <label class="form-label">Phim</label>

                    <select name="movie_id"
                            id="schedule-movie"
                            class="form-select"
                            required>
                        <option value="">Chọn phim</option>

                        @foreach ($movies as $movie)
                            <option
                                value="{{ $movie->id }}"
                                data-format-ids="{{ $movie->formats->pluck('id')->implode(',') }}"
                                @selected(old('movie_id') == $movie->id)
                            >
                                {{ $movie->title }}
                                · {{ $movie->duration }} phút
                                · {{ $movie->formats->pluck('name')->join(', ') ?: 'Chưa có định dạng' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Chọn định dạng suất chiếu --}}
                <div class="col-md-6">
                    <label class="form-label">Định dạng suất chiếu</label>

                    <select name="format_id"
                            id="schedule-format"
                            class="form-select"
                            required>
                        <option value="">Chọn định dạng</option>

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
                        Chỉ những định dạng được phim hỗ trợ mới có thể chọn.
                    </small>
                </div>

                {{-- Ngày bắt đầu --}}
                <div class="col-md-3">
                    <label class="form-label">Ngày bắt đầu</label>
                    <input
                        type="date"
                        name="starts_on"
                        min="{{ now()->toDateString() }}"
                        value="{{ old('starts_on', now()->toDateString()) }}"
                        class="form-control"
                        required
                    >
                </div>

                {{-- Ngày kết thúc --}}
                <div class="col-md-3">
                    <label class="form-label">Ngày kết thúc</label>
                    <input
                        type="date"
                        name="ends_on"
                        min="{{ now()->toDateString() }}"
                        value="{{ old('ends_on', now()->toDateString()) }}"
                        class="form-control"
                        required
                    >
                </div>

                {{-- Giá vé --}}
                <div class="col-md-4">
                    <label class="form-label">Giá ghế thường (VNĐ)</label>
                    <input
                        type="number"
                        name="price"
                        value="{{ old('price', 80000) }}"
                        min="0"
                        max="99999999.99"
                        step="1000"
                        class="form-control"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Giá ghế VIP (VNĐ)</label>
                    <input
                        type="number"
                        name="vip_price"
                        value="{{ old('vip_price', 100000) }}"
                        min="0"
                        max="99999999.99"
                        step="1000"
                        class="form-control"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Giá ghế đôi (VNĐ)</label>
                    <input
                        type="number"
                        name="couple_price"
                        value="{{ old('couple_price', 160000) }}"
                        min="0"
                        max="99999999.99"
                        step="1000"
                        class="form-control"
                        required
                    >
                </div>

                {{-- Tự động phân phòng --}}
                <div class="col-12">
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="auto_allocate"
                            id="auto-allocate"
                            value="1"
                            @checked(old('auto_allocate'))
                        >

                        <label class="form-check-label" for="auto-allocate">
                            Tự động chọn một phòng tương thích còn trống
                            cho mỗi ngày và khung giờ
                        </label>
                    </div>

                    <small class="text-secondary">
                        Hệ thống chỉ phân bổ phòng hỗ trợ định dạng đã chọn,
                        đồng thời kiểm tra lịch hiện có và thời gian nghỉ 15 phút.
                    </small>
                </div>

                {{-- Chọn phòng thủ công --}}
                <div class="col-12"
                     id="manual-room-selection"
                     @if (old('auto_allocate')) hidden @endif>

                    <fieldset>
                        <legend class="form-label">Chọn phòng chiếu tương thích</legend>

                        <div class="row g-2">
                            @foreach ($rooms as $room)
                                <div class="col-sm-6 col-xl-4">
                                    <label
                                        class="schedule-choice w-100 room-format-option"
                                        data-format-ids="{{ $room->formats->pluck('id')->implode(',') }}"
                                    >
                                        <input
                                            class="schedule-choice-input"
                                            type="checkbox"
                                            name="room_ids[]"
                                            value="{{ $room->id }}"
                                            @checked(in_array($room->id, old('room_ids', [])))
                                        >

                                        <span class="schedule-choice-card">
                                            <span class="d-block text-warning fw-semibold">
                                                {{ $room->cinema->name }}
                                            </span>

                                            <span class="d-block mt-1">
                                                {{ $room->room_code }} — {{ $room->name }}
                                            </span>

                                            <span class="d-block mt-2">
                                                @forelse ($room->formats as $roomFormat)
                                                    <span class="badge bg-secondary me-1">
                                                        {{ $roomFormat->name }}
                                                    </span>
                                                @empty
                                                    <span class="badge bg-danger">
                                                        Chưa cấu hình định dạng
                                                    </span>
                                                @endforelse
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>

                    <small class="text-secondary d-block mt-2">
                        Những phòng không hỗ trợ định dạng đã chọn
                        sẽ tự động bị ẩn và không thể lựa chọn.
                    </small>
                </div>

                {{-- Khung giờ --}}
                <div class="col-12">
                    <fieldset>
                        <legend class="form-label">Khung giờ bắt đầu</legend>

                        <div class="d-flex flex-wrap gap-2">
                            @foreach (['09:00', '12:00', '15:00', '18:00', '21:00'] as $time)
                                <label class="schedule-choice">
                                    <input
                                        class="schedule-choice-input"
                                        type="checkbox"
                                        name="times[]"
                                        value="{{ $time }}"
                                        @checked(in_array($time, old('times', [])))
                                    >

                                    <span class="schedule-time">
                                        {{ $time }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <small class="text-secondary d-block mt-2">
                            Tối đa 31 ngày, 10 phòng ở chế độ thủ công
                            và 8 khung giờ mỗi ngày.
                        </small>
                    </fieldset>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-outline-light">
                        Xem trước lịch
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Kết quả xem trước --}}
    @if ($preview)
        <div class="card bg-dark border-secondary">
            <div class="card-body">

                <h2 class="h5">
                    Xem trước: {{ $preview['count'] }} suất có thể tạo
                </h2>

                <p class="text-secondary">
                    @if (old('auto_allocate'))
                        Mỗi ngày và khung giờ được gán tối đa một phòng
                        có hỗ trợ định dạng đã chọn.
                    @else
                        Mỗi khung giờ được tạo tại từng phòng đã chọn.
                    @endif

                    Bỏ qua {{ count($preview['skipped']) }} suất.
                    Backend sẽ tính toán lại trong transaction khi lưu.
                </p>

                <div class="table-responsive mb-3">
                    <table class="table table-dark table-sm">
                        <thead>
                            <tr>
                                <th>Rạp / phòng</th>
                                <th>Bắt đầu</th>
                                <th>Kết thúc</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach (array_slice($preview['accepted'], 0, 100) as $slot)
                                <tr>
                                    <td>
                                        {{ $slot['cinema_name'] }}
                                        /
                                        {{ $slot['room_name'] }}
                                    </td>

                                    <td>{{ $slot['starts_at'] }}</td>
                                    <td>{{ $slot['ends_at'] }}</td>

                                    <td class="text-success">
                                        Có thể tạo
                                    </td>
                                </tr>
                            @endforeach

                            @foreach (array_slice($preview['skipped'], 0, 100) as $slot)
                                <tr>
                                    <td>
                                        {{ $slot['cinema_name'] }}
                                        /
                                        {{ $slot['room_name'] }}
                                    </td>

                                    <td>{{ $slot['starts_at'] }}</td>
                                    <td>{{ $slot['ends_at'] }}</td>

                                    <td class="text-warning">
                                        {{ $slot['skip_reason'] ?? 'Bỏ qua do trùng lịch hoặc chưa đủ thời gian nghỉ.' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Xác nhận tạo lịch --}}
                <form
                    method="POST"
                    action="{{ route('admin.showtimes.auto.store') }}"
                >
                    @csrf

                    @foreach ([
                        'movie_id',
                        'format_id',
                        'starts_on',
                        'ends_on',
                        'price',
                        'vip_price',
                        'couple_price'
                    ] as $field)
                        <input
                            type="hidden"
                            name="{{ $field }}"
                            value="{{ old($field) }}"
                        >
                    @endforeach

                    @if (old('auto_allocate'))
                        <input
                            type="hidden"
                            name="auto_allocate"
                            value="1"
                        >
                    @else
                        @foreach (old('room_ids', []) as $roomId)
                            <input
                                type="hidden"
                                name="room_ids[]"
                                value="{{ $roomId }}"
                            >
                        @endforeach
                    @endif

                    @foreach (old('times', []) as $time)
                        <input
                            type="hidden"
                            name="times[]"
                            value="{{ $time }}"
                        >
                    @endforeach

                    <button
                        type="submit"
                        class="btn btn-red"
                        {{ $preview['count'] === 0 ? 'disabled' : '' }}
                    >
                        Tạo {{ $preview['count'] }} suất chiếu
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>

{{-- JavaScript lọc định dạng phim và phòng --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const movieSelect = document.querySelector('#schedule-movie');
    const formatSelect = document.querySelector('#schedule-format');
    const roomChoices = document.querySelectorAll('.room-format-option');
    const autoAllocate = document.querySelector('#auto-allocate');
    const manualRoomSelection = document.querySelector('#manual-room-selection');

    if (!movieSelect || !formatSelect || !autoAllocate || !manualRoomSelection) {
        return;
    }

    function getFormatIds(element) {
        return (element.dataset.formatIds || '')
            .split(',')
            .map(id => id.trim())
            .filter(id => id !== '');
    }

    function updateRoomChoices() {
        const selectedFormatId = formatSelect.value;
        const useAutoAllocation = autoAllocate.checked;

        roomChoices.forEach(choice => {
            const supportedFormats = getFormatIds(choice);
            const compatible = selectedFormatId !== ''
                && supportedFormats.includes(selectedFormatId);

            const input = choice.querySelector('input');

            choice.hidden = !compatible;
            input.disabled = useAutoAllocation || !compatible;

            if (!compatible) {
                input.checked = false;
            }
        });

        manualRoomSelection.hidden = useAutoAllocation;
    }

    function updateScheduleFormats() {
        const selectedMovie = movieSelect.selectedOptions[0];
        const supportedFormats = new Set(
            selectedMovie ? getFormatIds(selectedMovie) : []
        );

        const currentFormatId = formatSelect.value;
        let firstSupportedFormat = '';

        Array.from(formatSelect.options).forEach(option => {
            if (!option.value) {
                option.disabled = false;
                option.hidden = false;
                return;
            }

            const supported = supportedFormats.has(option.value);

            option.disabled = !supported;
            option.hidden = !supported;

            if (supported && firstSupportedFormat === '') {
                firstSupportedFormat = option.value;
            }
        });

        // Giữ định dạng đã chọn nếu phim vẫn hỗ trợ.
        // Nếu không còn tương thích thì chọn định dạng hợp lệ đầu tiên.
        if (!supportedFormats.has(currentFormatId)) {
            formatSelect.value = firstSupportedFormat;
        }

        updateRoomChoices();
    }

    movieSelect.addEventListener('change', updateScheduleFormats);
    formatSelect.addEventListener('change', updateRoomChoices);
    autoAllocate.addEventListener('change', updateRoomChoices);

    // Khôi phục lựa chọn khi trang tải lại hoặc có lỗi validation.
    updateScheduleFormats();
});
</script>

<style>
    .schedule-choice {
        display: inline-flex;
        position: relative;
        cursor: pointer;
    }

    .schedule-choice[hidden] {
        display: none !important;
    }

    .schedule-choice-input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
    }

    .schedule-choice-card,
    .schedule-time {
        display: block;
        padding: .7rem .9rem;
        color: #ddd;
        background: #1b1b22;
        border: 1px solid #303039;
        border-radius: .6rem;
        transition:
            border-color .15s ease,
            background-color .15s ease,
            color .15s ease;
    }

    .schedule-choice-card {
        min-height: 100%;
    }

    .schedule-choice:hover .schedule-choice-card,
    .schedule-choice:hover .schedule-time {
        border-color: var(--ys-red);
    }

    .schedule-choice-input:checked + .schedule-choice-card,
    .schedule-choice-input:checked + .schedule-time {
        color: #fff;
        background: var(--ys-red);
        border-color: var(--ys-red);
        box-shadow: 0 0 0 2px rgb(229 9 20 / 20%);
    }

    .schedule-choice-input:checked + .schedule-choice-card .text-warning {
        color: #fff !important;
    }

    .schedule-choice-input:focus-visible + .schedule-choice-card,
    .schedule-choice-input:focus-visible + .schedule-time {
        outline: 3px solid #fff;
        outline-offset: 2px;
    }
</style>
@endsection
