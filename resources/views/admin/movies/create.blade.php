
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
                    @foreach (['P' => 'P - Phổ biến mọi lứa tuổi', 'K' => 'K - Dưới 13 tuổi cần người giám hộ', 'T13' => 'T13 - Từ đủ 13 tuổi', 'T16' => 'T16 - Từ đủ 16 tuổi', 'T18' => 'T18 - Từ đủ 18 tuổi', 'C' => 'C - Không được phép phổ biến'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('age_rating', 'P') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="form-text text-warning">Chỉ xác nhận sau khi đối chiếu thông tin chính thức từ nhà phát hành hoặc cơ quan có thẩm quyền.</div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="age_rating_verified" value="1" id="age-rating-verified" @checked(old('age_rating_verified'))>
                    <label class="form-check-label" for="age-rating-verified">Tôi đã xác minh phân loại chính thức này</label>
                </div>
            </div>
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="movie-featured" @checked(old('is_featured'))>
            <label class="form-check-label" for="movie-featured">Đánh dấu phim nổi bật trên trang chủ</label>
        </div>

        <div class="mb-4">
            <label for="format_id" class="form-label">Định dạng phim *</label>
            <select id="format_id" name="format_id" class="form-select" required>
                <option value="">-- Chọn định dạng --</option>
                @foreach ($formats as $format)
                    <option value="{{ $format->id }}" @selected(old('format_id') == $format->id)>{{ $format->name }}</option>
                @endforeach
            </select>
        </div>

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
