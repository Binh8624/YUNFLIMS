
@extends('layouts.app')

@section('title', 'Thêm phim - YUNFLIMS')

@section('content')
<div class="container py-5" style="max-width: 850px;">

    <h2 class="fw-bold mb-4">THÊM PHIM MỚI</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.movies.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label">Tên phim *</label>
            <input type="text" name="title"
                   class="form-control"
                   value="{{ old('title') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Thể loại *</label>
            <input type="text" name="genre"
                   class="form-control"
                   value="{{ old('genre') }}" required
                   placeholder="Ví dụ: Hành động, Phiêu lưu">
        </div>

        <div class="mb-3">
            <label class="form-label">Thời lượng (phút) *</label>
            <input type="number" name="duration"
                   class="form-control"
                   value="{{ old('duration') }}"
                   min="1" max="65535" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Nhãn phim</label>
            <input type="text" name="label"
                   class="form-control"
                   value="{{ old('label') }}"
                   placeholder="Ví dụ: BOM TẤN">
        </div>

        <div class="mb-3">
            <label class="form-label">URL poster</label>
            <input type="url" name="poster"
                   class="form-control"
                   value="{{ old('poster') }}"
                   placeholder="https://example.com/poster.jpg">
        </div>

        <div class="mb-3">
            <label class="form-label">Mô tả phim</label>
            <textarea name="description" class="form-control"
                      rows="4">{{ old('description') }}</textarea>
        </div>

        <div class="row g-3">
            <div class="col-md-6 mb-3">
                <label class="form-label">Trailer URL</label>
                <input type="url" name="trailer_url" class="form-control" value="{{ old('trailer_url') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Phân loại độ tuổi *</label>
                <select name="age_rating" class="form-select" required>
                    @foreach (['P' => 'P - Phổ biến', 'T13' => 'T13', 'T16' => 'T16', 'T18' => 'T18', 'C' => 'C - Cấm phổ biến'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('age_rating', 'P') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <fieldset class="mb-4">
            <legend class="form-label">Định dạng phim *</legend>
            <div class="d-flex flex-wrap gap-3">
                @foreach ($formats as $format)
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="formats[]" value="{{ $format->id }}" @checked(in_array($format->id, old('formats', [])))>
                        <span class="form-check-label">{{ $format->name }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="mb-4">
            <label class="form-label">Trạng thái *</label>
            <select name="status" class="form-select" required>
                <option value="now_showing"
                    @selected(old('status', 'now_showing') === 'now_showing')>
                    Đang chiếu
                </option>
                <option value="coming_soon"
                    @selected(old('status') === 'coming_soon')>
                    Sắp chiếu
                </option>
                <option value="inactive"
                    @selected(old('status') === 'inactive')>
                    Ẩn
                </option>
            </select>
        </div>

        <button type="submit" class="btn btn-red">
            Lưu phim
        </button>

        <a href="{{ route('admin.movies.index') }}"
           class="btn btn-outline-light ms-2">
            Quay lại
        </a>
    </form>
</div>
@endsection
