@extends('layouts.app')

@section('title', 'Thêm phim - YUNFLIMS')

@section('content')
<div class="container py-5" style="max-width: 900px;">
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

        <h5 class="fw-bold mb-3">Thông tin cơ bản</h5>

        <div class="mb-3">
            <label class="form-label">Tên phim *</label>
            <input type="text" name="title" class="form-control"
                   value="{{ old('title') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Slug SEO</label>
            <input type="text" name="slug" class="form-control"
                   value="{{ old('slug') }}"
                   placeholder="Để trống sẽ tự tạo từ tên phim">
            <div class="form-text">Ví dụ: avengers-endgame</div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Thể loại *</label>
                <input type="text" name="genre" class="form-control"
                       value="{{ old('genre') }}" required
                       placeholder="Hành động, Phiêu lưu">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Thời lượng (phút) *</label>
                <input type="number" name="duration" class="form-control"
                       value="{{ old('duration') }}" min="1" max="65535" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Nhãn phim</label>
                <input type="text" name="label" class="form-control"
                       value="{{ old('label') }}" placeholder="BOM TẤN">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Ngày khởi chiếu</label>
                <input type="date" name="release_date" class="form-control"
                       value="{{ old('release_date') }}">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">URL poster</label>
            <input type="url" name="poster" class="form-control"
                   value="{{ old('poster') }}"
                   placeholder="https://example.com/poster.jpg">
        </div>

        <div class="mb-3">
            <label class="form-label">Thư viện ảnh (mỗi URL một dòng)</label>
            <textarea name="gallery_urls" class="form-control" rows="4"
                      placeholder="https://example.com/image-1.jpg&#10;https://example.com/image-2.jpg">{{ old('gallery_urls') }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Mô tả phim</label>
            <textarea name="description" class="form-control"
                      rows="4">{{ old('description') }}</textarea>
        </div>

        <hr class="border-secondary my-4">

        <h5 class="fw-bold mb-3">Thông tin sản xuất</h5>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Quốc gia</label>
                <input type="text" name="country" class="form-control"
                       value="{{ old('country') }}" placeholder="Mỹ, Hàn Quốc, Nhật Bản...">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Đạo diễn</label>
                <input type="text" name="director" class="form-control"
                       value="{{ old('director') }}">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Diễn viên</label>
            <textarea name="cast_members" class="form-control" rows="2"
                      placeholder="Tên diễn viên, phân cách bằng dấu phẩy">{{ old('cast_members') }}</textarea>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Ngôn ngữ gốc</label>
                <input type="text" name="original_language" class="form-control"
                       value="{{ old('original_language') }}" placeholder="Tiếng Anh">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Ngôn ngữ phụ đề</label>
                <input type="text" name="subtitle_languages" class="form-control"
                       value="{{ old('subtitle_languages') }}" placeholder="Tiếng Việt, Tiếng Anh">
            </div>
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox"
                   name="is_dubbed" value="1" id="movie-dubbed"
                   @checked(old('is_dubbed'))>
            <label class="form-check-label" for="movie-dubbed">
                Có phiên bản lồng tiếng
            </label>
        </div>

        <hr class="border-secondary my-4">

        <h5 class="fw-bold mb-3">Phân loại và trạng thái</h5>

        <div class="mb-3">
            <label class="form-label">Trailer URL</label>
            <input type="url" name="trailer_url" class="form-control"
                   value="{{ old('trailer_url') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Phân loại độ tuổi *</label>
            <select name="age_rating" class="form-select" required>
                @foreach ([
                    'P' => 'P - Phổ biến mọi lứa tuổi',
                    'K' => 'K - Dưới 13 tuổi cần người giám hộ',
                    'T13' => 'T13 - Từ đủ 13 tuổi',
                    'T16' => 'T16 - Từ đủ 16 tuổi',
                    'T18' => 'T18 - Từ đủ 18 tuổi',
                    'C' => 'C - Không được phép phổ biến'
                ] as $value => $label)
                    <option value="{{ $value }}"
                        @selected(old('age_rating', 'P') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <div class="form-text text-warning">
                Chỉ xác nhận sau khi đối chiếu thông tin chính thức.
            </div>

            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox"
                       name="age_rating_verified" value="1"
                       id="age-rating-verified"
                       @checked(old('age_rating_verified'))>
                <label class="form-check-label" for="age-rating-verified">
                    Tôi đã xác minh phân loại chính thức này
                </label>
            </div>
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox"
                   name="is_featured" value="1" id="movie-featured"
                   @checked(old('is_featured'))>
            <label class="form-check-label" for="movie-featured">
                Đánh dấu phim nổi bật trên trang chủ
            </label>
        </div>

        <div class="mb-4">
            <label class="form-label">Định dạng phim *</label>
            <div class="border rounded p-3">
                @foreach ($formats as $format)
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox"
                               name="format_ids[]" value="{{ $format->id }}"
                               id="movie-format-{{ $format->id }}"
                               @checked(in_array(
                                   (string) $format->id,
                                   array_map('strval', old('format_ids', []))
                               ))>
                        <label class="form-check-label"
                               for="movie-format-{{ $format->id }}">
                            {{ $format->name }}
                        </label>
                    </div>
                @endforeach
            </div>
            <div class="form-text">
                Có thể chọn nhiều định dạng mà phim hỗ trợ.
            </div>
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

        <button type="submit" class="btn btn-red">Lưu phim</button>
        <a href="{{ route('admin.movies.index') }}"
           class="btn btn-outline-light ms-2">Quay lại</a>
    </form>
</div>
@endsection