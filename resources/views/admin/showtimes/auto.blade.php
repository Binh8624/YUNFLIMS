@extends('layouts.app')

@section('title', 'Tạo lịch tự động - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="mb-4"><h1 class="h2 fw-bold mb-1">Tạo suất chiếu tự động</h1><p class="text-secondary mb-0">Chọn khung giờ và phòng. Hệ thống tính thời lượng phim, thời gian vệ sinh 15 phút và bỏ qua xung đột.</p></div>
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div class="card bg-dark border-secondary mb-4"><div class="card-body">
        <form method="POST" action="{{ route('admin.showtimes.auto.preview') }}" class="row g-3">@csrf
            <div class="col-md-6"><label class="form-label">Phim</label><select name="movie_id" class="form-select" required><option value="">Chọn phim</option>@foreach ($movies as $movie)<option value="{{ $movie->id }}" @selected(old('movie_id') == $movie->id)>{{ $movie->title }} · {{ $movie->duration }} phút</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Định dạng</label><select name="format_id" class="form-select" required><option value="">Chọn định dạng</option>@foreach ($formats as $format)<option value="{{ $format->id }}" @selected(old('format_id') == $format->id)>{{ $format->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Ngày bắt đầu</label><input type="date" name="starts_on" min="{{ now()->toDateString() }}" value="{{ old('starts_on', now()->toDateString()) }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Ngày kết thúc</label><input type="date" name="ends_on" min="{{ now()->toDateString() }}" value="{{ old('ends_on', now()->toDateString()) }}" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Giá cơ bản (VNĐ)</label><input type="number" name="price" value="{{ old('price', 80000) }}" min="0" step="1000" class="form-control" required></div>
            <div class="col-12">
                <fieldset>
                    <legend class="form-label">Phòng tương thích</legend>
                    <div class="row g-2">
                        @foreach ($rooms as $room)
                            <div class="col-sm-6 col-xl-4">
                                <label class="schedule-choice w-100">
                                    <input
                                        class="schedule-choice-input"
                                        type="checkbox"
                                        name="room_ids[]"
                                        value="{{ $room->id }}"
                                        @checked(in_array($room->id, old('room_ids', [])))
                                    >
                                    <span class="schedule-choice-card">
                                        <span class="d-block text-warning fw-semibold">{{ $room->cinema->name }}</span>
                                        <span class="d-block mt-1">{{ $room->name }}</span>
                                        <span class="d-flex flex-wrap gap-1 mt-2">
                                            @foreach ($room->formats as $roomFormat)
                                                <span class="badge bg-secondary">{{ $roomFormat->name }}</span>
                                            @endforeach
                                        </span>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </fieldset>
            </div>
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
                                <span class="schedule-time">{{ $time }}</span>
                            </label>
                        @endforeach
                    </div>
                    <small class="text-secondary d-block mt-2">Tối đa 31 ngày, 10 phòng và 8 khung giờ mỗi ngày.</small>
                </fieldset>
            </div>
            <div class="col-12"><button class="btn btn-outline-light">Xem trước lịch</button></div>
        </form>
    </div></div>

    @if ($preview)
        <div class="card bg-dark border-secondary"><div class="card-body">
            <h2 class="h5">Xem trước: {{ $preview['count'] }} suất có thể tạo</h2>
            <p class="text-secondary">Bỏ qua {{ count($preview['skipped']) }} khung giờ do xung đột hoặc giờ đã qua. Xung đột sẽ được kiểm tra lại trong transaction khi lưu.</p>
            <div class="table-responsive mb-3"><table class="table table-dark table-sm"><thead><tr><th>Rạp / phòng</th><th>Bắt đầu</th><th>Kết thúc</th><th>Trạng thái</th></tr></thead><tbody>
                @foreach (array_slice($preview['accepted'], 0, 100) as $slot)<tr><td>{{ $slot['cinema_name'] }} / {{ $slot['room_name'] }}</td><td>{{ $slot['starts_at'] }}</td><td>{{ $slot['ends_at'] }}</td><td class="text-success">Có thể tạo</td></tr>@endforeach
                @foreach (array_slice($preview['skipped'], 0, 100) as $slot)<tr><td>{{ $slot['cinema_name'] }} / {{ $slot['room_name'] }}</td><td>{{ $slot['starts_at'] }}</td><td>{{ $slot['ends_at'] }}</td><td class="text-warning">Bỏ qua - trùng lịch</td></tr>@endforeach
            </tbody></table></div>
            <form method="POST" action="{{ route('admin.showtimes.auto.store') }}">@csrf
                @foreach (['movie_id', 'format_id', 'starts_on', 'ends_on', 'price'] as $field)<input type="hidden" name="{{ $field }}" value="{{ old($field) }}">@endforeach
                @foreach (old('room_ids', []) as $roomId)<input type="hidden" name="room_ids[]" value="{{ $roomId }}">@endforeach
                @foreach (old('times', []) as $time)<input type="hidden" name="times[]" value="{{ $time }}">@endforeach
                <button class="btn btn-red" {{ $preview['count'] === 0 ? 'disabled' : '' }}>Tạo {{ $preview['count'] }} suất chiếu</button>
            </form>
        </div></div>
    @endif
</div>

<style>
    .schedule-choice {
        display: inline-flex;
        position: relative;
        cursor: pointer;
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
        transition: border-color .15s ease, background-color .15s ease, color .15s ease;
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
