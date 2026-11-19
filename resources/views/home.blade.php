@extends('layouts.app')

@section('title', 'YUNFLIMS Cinema - Phim hay, đặt vé nhanh')

@section('content')
<div class="home-page">
    <section class="cinema-hero" aria-label="Phim nổi bật">
        <div
            id="featuredMovies"
            class="carousel slide carousel-fade"
            data-home-carousel
            aria-label="Phim nổi bật"
        >
            @if ($featured->count() > 1)
                <div class="carousel-indicators">
                    @foreach ($featured as $index => $movie)
                        <button
                            type="button"
                            data-bs-target="#featuredMovies"
                            data-bs-slide-to="{{ $index }}"
                            class="{{ $loop->first ? 'active' : '' }}"
                            @if ($loop->first) aria-current="true" @endif
                            aria-label="Phim nổi bật {{ $loop->iteration }}"
                        ></button>
                    @endforeach
                </div>
            @endif

            <div class="carousel-inner">
                @forelse ($featured as $movie)
                    <article class="carousel-item {{ $loop->first ? 'active' : '' }}">
                        <img
                            class="hero-backdrop"
                            src="{{ $movie->poster }}"
                            alt=""
                            @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif
                        >
                        <div class="container">
                            <div class="hero-content">
                                <span class="hero-kicker">PHIM NỔI BẬT</span>
                                <h1>{{ $movie->title }}</h1>
                                @if ($movie->description)
                                    <p class="hero-description mb-3">{{ $movie->description }}</p>
                                @endif
                                <div class="hero-meta" aria-label="Thông tin phim">
                                    @if ($movie->genre_labels)
                                        <span><i class="bi bi-film" aria-hidden="true"></i>{{ implode(' · ', array_slice($movie->genre_labels, 0, 3)) }}</span>
                                    @endif
                                    @if ($movie->duration)
                                        <span><i class="bi bi-clock" aria-hidden="true"></i>{{ $movie->duration }} phút</span>
                                    @endif
                                    <span><i class="bi bi-badge-3d" aria-hidden="true"></i>{{ $movie->format?->name ?? 'Định dạng chưa cập nhật' }}</span>
                                    <span><i class="bi bi-person-badge" aria-hidden="true"></i>{{ $movie->ageRatingLabel() }}</span>
                                    @if ($movie->reviews_count > 0)
                                        <span><i class="bi bi-star-fill" aria-hidden="true"></i>{{ number_format((float) $movie->reviews_avg_rating, 1) }}/5</span>
                                    @endif
                                </div>
                                <div class="hero-actions">
                                    <a href="{{ route('showtimes.index', ['movie_id' => $movie->id]) }}" class="btn btn-red">
                                        <i class="bi bi-ticket-perforated me-2" aria-hidden="true"></i>Đặt vé ngay
                                    </a>
                                    <a href="{{ route('movies.show', $movie) }}" class="btn btn-outline-light">
                                        Xem chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="carousel-item active">
                        <div class="container">
                            <div class="hero-content">
                                <span class="hero-kicker">YUNFLIMS CINEMA</span>
                                <h1>Sẵn sàng cho bộ phim tiếp theo?</h1>
                                <p class="hero-description">Khám phá các phim đang chiếu và chọn suất phù hợp với bạn.</p>
                                <div class="hero-actions">
                                    <a href="{{ route('movies.index') }}" class="btn btn-red">Khám phá phim</a>
                                    <a href="{{ route('showtimes.index') }}" class="btn btn-outline-light">Xem lịch chiếu</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($featured->count() > 1)
                <button class="carousel-control-prev" type="button" data-bs-target="#featuredMovies" data-bs-slide="prev" aria-label="Phim nổi bật trước">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#featuredMovies" data-bs-slide="next" aria-label="Phim nổi bật tiếp theo">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                </button>
            @endif
        </div>
    </section>

    <section class="container home-hot-section" aria-labelledby="hot-movies-title">
        <div class="home-section-heading">
            <div>
                <span class="hero-kicker">ĐƯỢC QUAN TÂM TẠI YUNFLIMS</span>
                <h2 class="section-title mt-2 mb-0" id="hot-movies-title">Phim hot</h2>
            </div>
            <a href="{{ route('movies.index', ['status' => 'now_showing']) }}" class="btn btn-outline-light btn-sm">
                Khám phá phim <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
            </a>
        </div>
        @if ($featured->isEmpty())
            <div class="cinema-panel home-empty-state">
                <i class="bi bi-stars" aria-hidden="true"></i>
                <span>Phim nổi bật sẽ xuất hiện khi có dữ liệu đặt vé/đánh giá hoặc được quản trị viên chọn.</span>
                <a href="{{ route('showtimes.index') }}">Xem lịch chiếu</a>
            </div>
        @else
            <div class="home-movies-grid home-hot-grid">
                @foreach ($featured as $movie)
                    <div>@include('partials.movie-card', ['movie' => $movie])</div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="container home-now-showing" id="movies" aria-labelledby="now-showing-title">
        <div class="home-section-heading">
            <div>
                <span class="hero-kicker">CHỌN PHIM, ĐẶT CHỖ</span>
                <h2 class="section-title mt-2 mb-0" id="now-showing-title">Phim đang chiếu</h2>
            </div>
            <div class="home-section-actions">
                <form method="GET" action="{{ route('movies.index') }}" class="home-search" role="search">
                    <label class="visually-hidden" for="home-movie-search">Tìm phim</label>
                    <input
                        id="home-movie-search"
                        type="search"
                        name="search"
                        class="form-control"
                        placeholder="Tìm phim..."
                    >
                    <button class="btn" type="submit" aria-label="Tìm phim">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                </form>
                <a href="{{ route('movies.index') }}" class="btn btn-outline-light btn-sm">
                    Xem tất cả <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                </a>
            </div>
        </div>

        @if ($movies->isEmpty())
            <div class="cinema-panel home-empty-state">
                <i class="bi bi-film" aria-hidden="true"></i>
                <span>Hiện chưa có phim đang chiếu để hiển thị.</span>
                <a href="{{ route('showtimes.index') }}">Xem lịch chiếu</a>
            </div>
        @else
            <div class="home-movies-grid">
                @foreach ($movies as $movie)
                    <article class="home-movie-card">
                        <a
                            href="{{ route('movies.show', $movie) }}"
                            class="home-movie-poster"
                            aria-label="Xem chi tiết phim {{ $movie->title }}"
                        >
                            <img src="{{ $movie->poster }}" alt="{{ $movie->title }}" loading="lazy">
                            <span class="home-movie-rating">{{ $movie->ageRatingLabel() }}</span>
                        </a>
                        <div class="home-movie-info">
                            <h3 class="home-movie-title">
                                <a href="{{ route('movies.show', $movie) }}">{{ $movie->title }}</a>
                            </h3>
                            <p class="home-movie-meta">
                                @if ($movie->genre_labels)
                                    <span>{{ implode(' · ', array_slice($movie->genre_labels, 0, 2)) }}</span>
                                @endif
                                @if ($movie->duration)
                                    <span>{{ $movie->duration }} phút</span>
                                @endif
                                <span>{{ $movie->format?->name ?? 'Định dạng chưa cập nhật' }}</span>
                                @if ($movie->reviews_count > 0)
                                    <span class="home-movie-score"><i class="bi bi-star-fill" aria-hidden="true"></i> {{ number_format((float) $movie->reviews_avg_rating, 1) }}</span>
                                @endif
                            </p>
                            <div class="home-movie-actions">
                                <a href="{{ route('movies.show', $movie) }}" class="btn btn-outline-light">Chi tiết</a>
                                <a href="{{ route('showtimes.index', ['movie_id' => $movie->id]) }}" class="btn btn-red">Đặt vé</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="container home-upcoming-section" id="coming-soon" aria-labelledby="coming-soon-title">
        <div class="home-section-heading">
            <div>
                <span class="hero-kicker">SẮP RA MẮT</span>
                <h2 class="section-title mt-2 mb-0" id="coming-soon-title">Phim sắp chiếu</h2>
            </div>
            <a href="{{ route('movies.index', ['status' => 'coming_soon']) }}" class="btn btn-outline-light btn-sm">
                Xem tất cả <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
            </a>
        </div>

        @if ($comingSoon->isEmpty())
            <div class="cinema-panel home-empty-state">
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                <span>Hiện chưa có phim sắp chiếu được cập nhật.</span>
                <a href="{{ route('movies.index') }}">Khám phá phim</a>
            </div>
        @else
            <div class="home-movies-grid home-upcoming-grid">
                @foreach ($comingSoon as $movie)
                    <article class="home-movie-card home-upcoming-card">
                        <a
                            href="{{ route('movies.show', $movie) }}"
                            class="home-movie-poster"
                            aria-label="Xem chi tiết phim {{ $movie->title }}"
                        >
                            <img src="{{ $movie->poster }}" alt="{{ $movie->title }}" loading="lazy">
                            <span class="home-movie-rating">{{ $movie->ageRatingLabel() }}</span>
                        </a>
                        <div class="home-movie-info">
                            <h3 class="home-movie-title">
                                <a href="{{ route('movies.show', $movie) }}">{{ $movie->title }}</a>
                            </h3>
                            <p class="home-movie-meta home-release-date">
                                @if ($movie->genre_labels)
                                    <span>{{ implode(' · ', array_slice($movie->genre_labels, 0, 2)) }}</span>
                                @endif
                                <span class="badge bg-secondary">{{ $movie->format?->name ?? 'Định dạng chưa cập nhật' }}</span>
                                <i class="bi bi-calendar3" aria-hidden="true"></i>
                                Ngày khởi chiếu đang cập nhật
                            </p>
                            <div class="home-movie-actions">
                                <a href="{{ route('movies.show', $movie) }}" class="btn btn-outline-light">Xem chi tiết</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="container home-promotions-section" id="promotions" aria-labelledby="promotions-title">
        <div class="home-section-heading">
            <div>
                <span class="hero-kicker">ƯU ĐÃI YUNFLIMS</span>
                <h2 class="section-title mt-2 mb-0" id="promotions-title">Khuyến mãi nổi bật</h2>
            </div>
        </div>
        <div class="home-promotion-empty">
            <div class="home-promotion-icon" aria-hidden="true">
                <i class="bi bi-stars"></i>
            </div>
            <div class="home-promotion-copy">
                <h3>Ưu đãi đang được cập nhật</h3>
                <p>Hiện hệ thống chưa có dữ liệu chương trình khuyến mãi. Vui lòng quay lại sau để khám phá các ưu đãi mới.</p>
            </div>
            <a href="{{ route('services.vouchers') }}" class="btn btn-outline-light">
                Xem thông tin ưu đãi <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
            </a>
        </div>
    </section>
</div>
@endsection
