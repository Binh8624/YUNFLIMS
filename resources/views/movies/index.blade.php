@extends('layouts.app')

@section('title', 'Khám phá phim - YUNFLIMS Cinema')

@section('content')
<section class="catalog-heading">
    <div class="container py-5">
        <span class="hero-kicker">MÀN ẢNH RỘNG ĐANG CHỜ BẠN</span>
        <h1 class="display-5 fw-bold mt-2 mb-2">Khám phá phim</h1>
        <p class="text-secondary mb-0">Tìm phim theo tên, thể loại và phân loại độ tuổi đã được xác minh.</p>
    </div>
</section>

<section class="container pb-5">
    <form method="GET" action="{{ route('movies.index') }}" class="catalog-filter cinema-panel p-3 p-lg-4 mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label class="form-label" for="catalog-search">Tìm phim</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input id="catalog-search" type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Tên phim..." list="catalog-movie-suggestions" data-movie-suggestions="{{ route('movies.suggestions') }}">
                    <datalist id="catalog-movie-suggestions"></datalist>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="catalog-genre">Thể loại</label>
                <select id="catalog-genre" name="genre" class="form-select">
                    <option value="">Tất cả</option>
                    @foreach ($genres as $genre)
                        <option value="{{ $genre }}" @selected(request('genre') === $genre)>{{ $genre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="catalog-age">Độ tuổi</label>
                <select id="catalog-age" name="age_rating" class="form-select">
                    <option value="">Tất cả</option>
                    @foreach ($ageRatings as $ageRating)
                        <option value="{{ $ageRating }}" @selected(request('age_rating') === $ageRating)>{{ $ageRating }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="catalog-sort">Sắp xếp</label>
                <select id="catalog-sort" name="sort" class="form-select">
                    <option value="newest" @selected(request('sort', 'newest') === 'newest')>Mới cập nhật</option>
                    <option value="popular" @selected(request('sort') === 'popular')>Phổ biến</option>
                    <option value="rated" @selected(request('sort') === 'rated')>Đánh giá cao</option>
                    <option value="title" @selected(request('sort') === 'title')>Tên A–Z</option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <button class="btn btn-red w-100" type="submit"><i class="bi bi-funnel me-2" aria-hidden="true"></i>Lọc phim</button>
            </div>
        </div>
    </form>

    <nav class="catalog-tabs mb-4" aria-label="Lọc phim theo trạng thái">
        <a href="{{ route('movies.index', request()->except('status')) }}" class="{{ request('status') ? '' : 'active' }}" @if (! request('status')) aria-current="page" @endif>Tất cả phim</a>
        <a href="{{ route('movies.index', array_merge(request()->except('status'), ['status' => 'now_showing'])) }}" class="{{ request('status') === 'now_showing' ? 'active' : '' }}" @if (request('status') === 'now_showing') aria-current="page" @endif>Đang chiếu</a>
        <a href="{{ route('movies.index', array_merge(request()->except('status'), ['status' => 'coming_soon'])) }}" class="{{ request('status') === 'coming_soon' ? 'active' : '' }}" @if (request('status') === 'coming_soon') aria-current="page" @endif>Sắp chiếu</a>
    </nav>

    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
        <h2 class="h5 mb-0">Danh sách phim</h2>
        <span class="text-secondary small">{{ $movies->total() }} phim</span>
    </div>

    @if ($movies->isEmpty())
        <div class="cinema-panel text-center p-4 p-md-5">
            <i class="bi bi-film feature-icon" aria-hidden="true"></i>
            <h2 class="h4 mt-3">Chưa tìm thấy phim phù hợp</h2>
            <p class="text-secondary mb-3">Thử thay đổi từ khóa hoặc bộ lọc để xem thêm phim.</p>
            <a href="{{ route('movies.index') }}" class="btn btn-outline-light">Xóa bộ lọc</a>
        </div>
    @else
        <div class="row g-3 g-lg-4">
            @foreach ($movies as $movie)
                <div class="col-6 col-md-4 col-lg-3">
                    @include('partials.movie-card', ['movie' => $movie])
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $movies->links() }}
        </div>
    @endif
</section>
@endsection
