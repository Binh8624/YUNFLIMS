@extends('layouts.app')

@section('title', $movie->title . ' - YUNFLIMS')

@section('content')
<div class="container py-5 movie-detail">
    <div class="row g-4 align-items-start">
        <div class="col-lg-4">
            <img
                src="{{ $movie->poster ?? 'https://placehold.co/600x900/1b1b22/e50914?text=YUNFLIMS' }}"
                alt="{{ $movie->title }}"
                class="movie-poster movie-detail-poster"
            >
        </div>

        <div class="col-lg-8 movie-detail-copy">
            <span class="badge badge-red mb-3 px-3 py-2">
                {{ $movie->label ?? 'PHIM' }}
            </span>

            <h1 class="display-6 fw-bold mb-3">{{ $movie->title }}</h1>

            <div class="d-flex flex-wrap gap-3 movie-facts mb-4">
                @foreach ($movie->genre_labels as $genre)
                    <span class="badge bg-secondary">{{ $genre }}</span>
                @endforeach

                <span><i class="bi bi-clock"></i> {{ $movie->duration }} phút</span>

                <span
                    class="badge {{ $movie->age_rating_verified ? 'bg-success' : 'bg-warning text-dark' }}"
                    title="{{ $movie->age_rating_verified ? 'Phân loại đã xác minh' : 'Phân loại đang chờ xác minh' }}"
                >
                    <i class="bi bi-person-badge me-1"></i>
                    {{ $movie->ageRatingLabel() }}
                </span>

                <span>
                    <i class="bi bi-tag"></i>
                    {{ ucfirst(str_replace('_', ' ', $movie->status)) }}
                </span>

                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <i class="bi bi-badge-3d"></i>
                    @forelse ($movie->formats as $format)
                        <span class="badge bg-secondary">{{ $format->name }}</span>
                    @empty
                        <span class="text-secondary">Định dạng chưa cập nhật</span>
                    @endforelse
                </div>
            </div>

            <div class="mb-3">
                <span class="text-warning">
                    @for ($star = 1; $star <= 5; $star++)
                        <i class="bi {{ $star <= round((float) $movie->reviews_avg_rating) ? 'bi-star-fill' : 'bi-star' }}"></i>
                    @endfor
                </span>
                <strong class="ms-2">
                    {{ number_format((float) $movie->reviews_avg_rating, 1) }}/5
                </strong>
                <span class="text-secondary">
                    ({{ $movie->reviews_count }} đánh giá)
                </span>
            </div>

            <p class="lead text-light">
                {{ $movie->description ?: 'Thông tin phim đang được cập nhật.' }}
            </p>

            {{-- THÔNG TIN CHI TIẾT PHIM --}}
            <div class="card bg-dark border-secondary mb-4 cinema-panel">
                <div class="card-body">
                    <h2 class="h4 fw-bold mb-3">Thông tin phim</h2>

                    <div class="movie-metadata">
                        @if ($movie->release_date)
                            <div class="movie-metadata-row">
                                <span><i class="bi bi-calendar-event me-2"></i>Ngày khởi chiếu</span>
                                <strong>{{ $movie->release_date->format('d/m/Y') }}</strong>
                            </div>
                        @endif

                        @if ($movie->country)
                            <div class="movie-metadata-row">
                                <span><i class="bi bi-globe me-2"></i>Quốc gia</span>
                                <strong>{{ $movie->country }}</strong>
                            </div>
                        @endif

                        @if ($movie->director)
                            <div class="movie-metadata-row">
                                <span><i class="bi bi-camera-reels me-2"></i>Đạo diễn</span>
                                <strong>{{ $movie->director }}</strong>
                            </div>
                        @endif

                        @if ($movie->cast_members)
                            <div class="movie-metadata-row">
                                <span><i class="bi bi-people me-2"></i>Diễn viên</span>
                                <strong>{{ $movie->cast_members }}</strong>
                            </div>
                        @endif

                        @if ($movie->original_language)
                            <div class="movie-metadata-row">
                                <span><i class="bi bi-translate me-2"></i>Ngôn ngữ gốc</span>
                                <strong>{{ $movie->original_language }}</strong>
                            </div>
                        @endif

                        @if ($movie->subtitle_languages)
                            <div class="movie-metadata-row">
                                <span><i class="bi bi-chat-square-text me-2"></i>Phụ đề</span>
                                <strong>{{ $movie->subtitle_languages }}</strong>
                            </div>
                        @endif

                        <div class="movie-metadata-row">
                            <span><i class="bi bi-mic me-2"></i>Lồng tiếng</span>
                            <strong>{{ $movie->is_dubbed ? 'Có' : 'Không' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            {{-- THƯ VIỆN ẢNH --}}
            @if (!empty($movie->gallery))
                <div class="mb-4">
                    <h2 class="h4 fw-bold mb-3">Hình ảnh phim</h2>

                    <div class="row g-3">
                        @foreach ($movie->gallery as $image)
                            <div class="col-6 col-md-4">
                                <a href="{{ $image }}" target="_blank" rel="noopener noreferrer">
                                    <img
                                        src="{{ $image }}"
                                        alt="Hình ảnh {{ $movie->title }}"
                                        class="img-fluid rounded movie-gallery-image"
                                        loading="lazy"
                                    >
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="d-flex flex-wrap gap-3 mb-4">
                @if ($movie->status === 'now_showing')
                    <a
                        href="{{ route('showtimes.index', array_filter(['movie_id' => $movie->id, 'date' => $date])) }}"
                        class="btn btn-red btn-lg"
                    >
                        Mua vé
                    </a>
                @else
                    <span class="btn btn-outline-secondary btn-lg disabled">
                        Sắp chiếu
                    </span>
                @endif

                @if ($movie->trailer_url)
                    <a
                        href="{{ $movie->trailer_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-outline-light btn-lg"
                    >
                        <i class="bi bi-play-circle"></i>
                        Xem trailer
                    </a>
                @endif

                @auth
                    <form method="POST" action="{{ route('movies.favorite', $movie) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-lg">
                            <i class="bi {{ auth()->user()->isFavoriteMovie($movie) ? 'bi-heart-fill text-danger' : 'bi-heart' }} me-2"></i>
                            {{ auth()->user()->isFavoriteMovie($movie) ? 'Đã yêu thích' : 'Yêu thích' }}
                        </button>
                    </form>
                @endauth

                <button
                    type="button"
                    class="btn btn-outline-light btn-lg"
                    id="share-movie"
                    data-share-title="{{ $movie->title }}"
                    data-share-url="{{ request()->url() }}"
                >
                    <i class="bi bi-share me-2"></i>
                    Chia sẻ phim
                </button>
            </div>

            <div class="card bg-dark border-secondary mb-4 cinema-panel" id="schedule">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <h2 class="h4 mb-1">Lịch chiếu tổng hợp</h2>
                        <p class="text-secondary mb-0">
                            Xem các suất chiếu thực tế của phim này và những phim khác theo ngày, thành phố, rạp và định dạng.
                        </p>
                    </div>

                    @if ($movie->status === 'now_showing')
                        <a
                            href="{{ route('showtimes.index', array_filter(['movie_id' => $movie->id, 'date' => $date])) }}"
                            class="btn btn-red flex-shrink-0"
                        >
                            <i class="bi bi-calendar2-week me-2"></i>
                            Chọn suất chiếu
                        </a>
                    @endif
                </div>
            </div>

            {{-- ĐÁNH GIÁ PHIM --}}
            <div class="card bg-dark border-secondary cinema-panel" id="movie-reviews">
                <div class="card-body">
                    <h2 class="h4 mb-3">Đánh giá phim</h2>

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    @auth
                        @if ($canReview)
                            <form method="POST" action="{{ route('movies.reviews.store', $movie) }}" class="mb-4">
                                @csrf

                                <div class="mb-3">
                                    <label for="rating" class="form-label">Số sao</label>
                                    <select id="rating" name="rating" class="form-select" required style="max-width: 220px;">
                                        @foreach (range(5, 1) as $rating)
                                            <option value="{{ $rating }}" @selected(old('rating', $myReview?->rating ?? 5) == $rating)>
                                                {{ $rating }} sao
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="review-comment" class="form-label">Nhận xét</label>
                                    <textarea
                                        id="review-comment"
                                        name="comment"
                                        rows="3"
                                        maxlength="2000"
                                        class="form-control"
                                        placeholder="Chia sẻ cảm nhận của bạn"
                                    >{{ old('comment', $myReview?->comment) }}</textarea>
                                </div>

                                <button type="submit" class="btn btn-red">
                                    {{ $myReview ? 'Cập nhật đánh giá' : 'Gửi đánh giá' }}
                                </button>
                            </form>
                        @else
                            <div class="alert alert-secondary mb-4">
                                <i class="bi bi-info-circle me-2"></i>
                                Bạn cần mua vé, thanh toán và sử dụng vé thành công để đánh giá phim này.
                            </div>
                        @endif
                    @else
                        <p class="text-secondary">Đăng nhập để đánh giá phim.</p>
                        <a class="btn btn-outline-light mb-4" href="{{ route('login', ['redirect' => request()->fullUrl()]) }}">
                            Đăng nhập
                        </a>
                    @endauth

                    <div class="review-list">
                        @forelse ($reviews as $review)
                            <article class="border-top border-secondary py-3">
                                <div class="d-flex justify-content-between gap-3">
                                    <strong>{{ $review->user?->name ?? 'Người dùng' }}</strong>
                                    <time class="small text-secondary" datetime="{{ $review->created_at->toIso8601String() }}">
                                        {{ $review->created_at->format('d/m/Y') }}
                                    </time>
                                </div>

                                <div class="text-warning my-1">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <i class="bi {{ $star <= $review->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                    @endfor
                                </div>

                                @if ($review->comment)
                                    <p class="mb-0 text-light">{{ $review->comment }}</p>
                                @endif
                            </article>
                        @empty
                            <p class="text-secondary mb-0">Chưa có đánh giá nào. Hãy là người đầu tiên.</p>
                        @endforelse
                    </div>

                    @if ($reviews->hasPages())
                        <div class="mt-3">
                            {{ $reviews->fragment('movie-reviews')->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($relatedMovies->isNotEmpty())
        <section class="mt-5" aria-labelledby="related-movies-title">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                <h2 class="section-title mb-0" id="related-movies-title">Phim cùng thể loại</h2>
                <a
                    href="{{ route('movies.index', array_filter(['genre' => $relatedGenre])) }}"
                    class="btn btn-outline-light btn-sm"
                >
                    Xem thêm
                </a>
            </div>

            <div class="row g-3">
                @foreach ($relatedMovies as $relatedMovie)
                    <div class="col-6 col-md-4 col-lg-3">
                        @include('partials.movie-card', ['movie' => $relatedMovie])
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>

@push('scripts')
<script>
    const shareButton = document.getElementById('share-movie');

    shareButton?.addEventListener('click', async function () {
        const shareData = {
            title: shareButton.dataset.shareTitle,
            url: shareButton.dataset.shareUrl,
        };

        if (navigator.share) {
            try {
                await navigator.share(shareData);
                return;
            } catch (error) {
                if (error.name === 'AbortError') return;
            }
        }

        try {
            if (!navigator.clipboard?.writeText) {
                window.prompt('Sao chép đường dẫn phim:', shareData.url);
                return;
            }

            await navigator.clipboard.writeText(shareData.url);
            shareButton.innerHTML = '<i class="bi bi-check2 me-2"></i>Đã sao chép liên kết';
        } catch {
            shareButton.textContent = 'Không thể sao chép liên kết.';
        }
    });
</script>
@endpush

<style>
    .movie-metadata-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: .75rem 0;
        border-bottom: 1px solid #3a3a43;
    }

    .movie-metadata-row:last-child {
        border-bottom: 0;
    }

    .movie-metadata-row span {
        color: #aaa;
        flex-shrink: 0;
    }

    .movie-metadata-row strong {
        text-align: right;
        color: #fff;
        overflow-wrap: anywhere;
    }

    .movie-gallery-image {
        width: 100%;
        height: 180px;
        object-fit: cover;
        border: 1px solid #3a3a43;
        transition: transform .2s ease;
    }

    .movie-gallery-image:hover {
        transform: scale(1.03);
    }

    @media (max-width: 575px) {
        .movie-metadata-row {
            flex-direction: column;
            gap: .3rem;
        }

        .movie-metadata-row strong {
            text-align: left;
        }
    }
</style>
@endsection