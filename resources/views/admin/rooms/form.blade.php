@extends('layouts.app')

@section('title', ($room->exists ? 'Sửa phòng' : 'Thêm phòng') . ' - YUNFLIMS')

@section('content')
@php
    $selectedFormatIds = old(
        'format_ids',
        $room->exists
            ? $room->formats->pluck('id')->map(fn ($id) => (string) $id)->all()
            : []
    );

    $selectedFormatIds = array_map('strval', (array) $selectedFormatIds);
@endphp

<div class="container-fluid py-4 px-lg-4">
    <div class="card bg-dark border-secondary mx-auto" style="max-width: 760px;">
        <div class="card-body p-4">

            <h1 class="h3 fw-bold mb-4">
                {{ $room->exists ? 'Sửa phòng chiếu' : 'Thêm phòng chiếu' }}
            </h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST"
                  action="{{ $room->exists ? route('admin.rooms.update', $room) : route('admin.rooms.store') }}">

                @csrf

                @if ($room->exists)
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">Rạp *</label>

                    <select name="cinema_id" class="form-select" required>
                        <option value="">Chọn rạp</option>

                        @foreach ($cinemas as $cinema)
                            <option value="{{ $cinema->id }}"
                                @selected(old('cinema_id', $room->cinema_id) == $cinema->id)>
                                {{ $cinema->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mã phòng *</label>

                    <input name="room_code"
                           class="form-control"
                           value="{{ old('room_code', $room->room_code) }}"
                           required
                           maxlength="30"
                           placeholder="Ví dụ: P01">
                </div>

                <div class="mb-3">
                    <label class="form-label">Tên phòng *</label>

                    <input name="name"
                           class="form-control"
                           value="{{ old('name', $room->name) }}"
                           required
                           maxlength="100">
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Định dạng phòng *</label>

                    <div class="border border-secondary rounded p-3">
                        @forelse ($formats as $format)
                            <div class="form-check mb-2">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="format_ids[]"
                                       id="format_{{ $format->id }}"
                                       value="{{ $format->id }}"
                                       @checked(in_array((string) $format->id, $selectedFormatIds, true))>

                                <label class="form-check-label"
                                       for="format_{{ $format->id }}">
                                    {{ $format->name }}
                                </label>
                            </div>
                        @empty
                            <span class="text-warning">
                                Hiện không có định dạng phòng chiếu đang hoạt động.
                            </span>
                        @endforelse
                    </div>

                    <small class="text-secondary">
                        Có thể chọn nhiều định dạng mà phòng chiếu hỗ trợ.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Sức chứa *</label>

                    <input type="number"
                           name="total_seats"
                           class="form-control"
                           value="{{ old('total_seats', $room->total_seats) }}"
                           required
                           min="1"
                           max="65535">
                </div>

                <div class="mb-4">
                    <label class="form-label">Trạng thái</label>

                    <select name="is_active" class="form-select">
                        <option value="1"
                            @selected((string) old('is_active', (int) ($room->is_active ?? true)) === '1')>
                            Hoạt động
                        </option>

                        <option value="0"
                            @selected((string) old('is_active', (int) ($room->is_active ?? true)) === '0')>
                            Tạm ngưng
                        </option>
                    </select>
                </div>

                <button class="btn btn-red">Lưu phòng</button>

                <a href="{{ route('admin.rooms.index') }}"
                   class="btn btn-outline-light ms-2">
                    Quay lại
                </a>

                @if ($room->exists)
                    <a href="{{ route('admin.rooms.seats.index', $room) }}"
                       class="btn btn-outline-info ms-2">
                        Quản lý sơ đồ ghế
                    </a>
                @endif

            </form>
        </div>
    </div>
</div>
@endsection