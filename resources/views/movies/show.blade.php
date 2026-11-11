@extends('layouts.app')

@section('title', $movie->title . ' - YUNFLIMS')

@section('content')
<div class="container py-5 movie-detail">
    <div class="row g-4 align-items-start">
        <div class="col-lg-4">
            <img src="{{ $movie->poster ?? 'https://placehold.co/600x900/1b1b22/e50914?text=YUNFLIMS' }}" alt="{{ $movie->title }}" class="movie-poster movie-detail-poster">
        </div>

        <div class="col-lg-8 movie-detail-copy">
            <span class="badge badge-red mb-3 px-3 py-2">{{ $movie->label ?? 'PHIM' }}</span>
            <h1 class="display-6 fw-bold mb-3">{{ $movie->title }}</h1>

            <div class="d-flex flex-wrap gap-3 movie-facts mb-4">
                <span><i class="bi bi-film"></i> {{ $movie->genre }}</span>
                <span><i class="bi bi-clock"></i> {{ $movie->duration }} phút</span>
                <span><i class="bi bi-person-badge"></i> {{ $movie->age_rating }}</span>
                <span><i class="bi bi-tag"></i> {{ ucfirst(str_replace('_', ' ', $movie->status)) }}</span>
            </div>

            <div class="mb-3">
                <span class="text-warning" aria-label="Điểm trung bình {{ number_format((float) $movie->reviews_avg_rating, 1) }} trên 5">
                    @for ($star = 1; $star <= 5; $star++)
                        <i class="bi {{ $star <= round((float) $movie->reviews_avg_rating) ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                    @endfor
                </span>
                <strong class="ms-2">{{ number_format((float) $movie->reviews_avg_rating, 1) }}/5</strong>
                <span class="text-secondary">({{ $movie->reviews_count }} đánh giá)</span>
            </div>

            @if ($movie->formats->isNotEmpty())
                <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                    <span class="text-secondary">Định dạng:</span>
                    @foreach ($movie->formats as $format)
                        <span class="badge bg-secondary">{{ $format->name }}</span>
                    @endforeach
                </div>
            @endif

            <p class="lead text-light">{{ $movie->description ?: 'Thông tin phim đang được cập nhật.' }}</p>

            <div class="d-flex flex-wrap gap-3 mb-4">
                <a href="#schedule" class="btn btn-red btn-lg">Đặt vé</a>
                @if ($movie->trailer_url)
                    <a href="{{ $movie->trailer_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-play-circle"></i> Xem trailer
                    </a>
                @endif
                @if ($movie->status === 'coming_soon')
                    <span class="btn btn-outline-secondary btn-lg disabled">Sắp chiếu</span>
                @endif
                <button type="button" class="btn btn-outline-light btn-lg" id="share-movie" data-share-title="{{ $movie->title }}" data-share-url="{{ request()->url() }}">
                    <i class="bi bi-share me-2" aria-hidden="true"></i>Chia sẻ phim
                </button>
            </div>

            <div class="card bg-dark border-secondary mb-4 cinema-panel" id="schedule">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="mb-0">Chọn ngày và rạp</h4>
                        <span class="text-secondary small">Chỉ hiển thị suất chiếu tương lai</span>
                    </div>

                    <form method="GET" action="{{ route('movies.show', $movie) }}" class="mb-3">
                        @if (request()->filled('cinema_id'))
                            <input type="hidden" name="cinema_id" value="{{ request('cinema_id') }}">
                        @endif
                        <div class="show-date-grid" aria-label="Chọn ngày chiếu">
                            @php($weekdays = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'])
                            @foreach ($showDates as $showDate)
                                @php($dateValue = $showDate->toDateString())
                                <button
                                    type="submit"
                                    name="date"
                                    value="{{ $dateValue }}"
                                    class="show-date-option {{ $dateValue === $date ? 'active' : '' }}"
                                    aria-pressed="{{ $dateValue === $date ? 'true' : 'false' }}"
                                >
                                    <span>{{ $showDate->format('d/m') }}</span>
                                    <strong>{{ $showDate->format('d') }}</strong>
                                    <small>{{ $weekdays[$showDate->dayOfWeek] }}</small>
                                </button>
                            @endforeach
                        </div>
                    </form>

                    <form method="GET" action="{{ route('movies.show', $movie) }}" class="row g-2 align-items-end mb-4">
                        <input type="hidden" name="date" value="{{ $date }}">
                        <div class="col-sm-8 col-md-6">
                            <label for="show-cinema" class="form-label">Rạp chiếu</label>
                            <select id="show-cinema" name="cinema_id" class="form-select">
                                <option value="">Tất cả rạp</option>
                                @foreach ($cinemas as $cinema)
                                    <option value="{{ $cinema->id }}" @selected(request('cinema_id') == $cinema->id)>{{ $cinema->name }} — {{ $cinema->city }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-4 col-md-3">
                            <button class="btn btn-outline-light w-100">Lọc rạp</button>
                        </div>
                    </form>

                    @if ($movie->showtimes->isEmpty())
                        <div class="alert alert-secondary border-0 mb-0">Không có suất chiếu phù hợp trong ngày đã chọn. Chọn ngày khác để xem lịch.</div>
                    @else
                        <div class="showtime-cinema-list">
                            @foreach ($movie->showtimes->groupBy(fn ($showtime) => $showtime->room->cinema->id ?? 0) as $cinemaShowtimes)
                                @php($cinema = $cinemaShowtimes->first()->room->cinema)
                                <section class="showtime-cinema">
                                    <h5>{{ $cinema->name ?? 'YUNFLIMS' }}</h5>
                                    <p class="showtime-city">{{ $cinema->city ?? '' }}</p>
                                    @foreach ($cinemaShowtimes->groupBy(fn ($showtime) => $showtime->room_id . '-' . $showtime->format_id) as $roomShowtimes)
                                        @php($firstShowtime = $roomShowtimes->first())
                                        <div class="showtime-room">
                                            <div class="showtime-room-heading">
                                                <strong>{{ $firstShowtime->room->name }}</strong>
                                                <span class="badge badge-red">{{ $firstShowtime->format?->name ?? '2D' }}</span>
                                            </div>
                                            <div class="showtime-time-list">
                                                @foreach ($roomShowtimes as $showtime)
                                                    <a
                                                        href="{{ route('bookings.seats', $showtime) }}"
                                                        class="showtime-time"
                                                        aria-label="Chọn suất {{ $showtime->starts_at->format('H:i') }}"
                                                    >
                                                        <strong>{{ $showtime->starts_at->format('H:i') }}</strong>
                                                        <small>{{ $firstShowtime->format?->name ?? 'Định dạng chiếu' }}</small>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </section>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card bg-dark border-secondary cinema-panel">
                <div class="card-body">
                    <h4 class="mb-3">Đánh giá phim</h4>
                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                        </div>
                    @endif
                    @auth
                        <form method="POST" action="{{ route('movies.reviews.store', $movie) }}" class="mb-4">
                            @csrf
                            <div class="mb-3">
                                <label for="rating" class="form-label">Số sao</label>
                                <select id="rating" name="rating" class="form-select" required style="max-width: 220px;">
                                    @foreach (range(5, 1) as $rating)
                                        <option value="{{ $rating }}" @selected(old('rating', $myReview?->rating ?? 5) == $rating)>{{ $rating }} sao</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="review-comment" class="form-label">Nhận xét</label>
                                <textarea id="review-comment" name="comment" rows="3" maxlength="2000" class="form-control" placeholder="Chia sẻ cảm nhận của bạn">{{ old('comment', $myReview?->comment) }}</textarea>
                            </div>
                            <button class="btn btn-red">{{ $myReview ? 'Cập nhật đánh giá' : 'Gửi đánh giá' }}</button>
                        </form>
                    @else
                        <p class="text-secondary">Đăng nhập để đánh giá phim.</p>
                        <a class="btn btn-outline-light mb-4" href="{{ route('login', ['redirect' => request()->fullUrl()]) }}">Đăng nhập</a>
                    @endauth

                    @forelse ($movie->reviews as $review)
                        <article class="border-top border-secondary py-3">
                            <div class="d-flex justify-content-between gap-3">
                                <strong>{{ $review->user->name }}</strong>
                                <time class="small text-secondary" datetime="{{ $review->created_at->toIso8601String() }}">{{ $review->created_at->format('d/m/Y') }}</time>
                            </div>
                            <div class="text-warning my-1" aria-label="{{ $review->rating }} trên 5 sao">
                                @for ($star = 1; $star <= 5; $star++)
                                    <i class="bi {{ $star <= $review->rating ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                                @endfor
                            </div>
                            @if ($review->comment)<p class="mb-0 text-light">{{ $review->comment }}</p>@endif
                        </article>
                    @empty
                        <p class="text-secondary mb-0">Chưa có đánh giá nào. Hãy là người đầu tiên.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @if ($relatedMovies->isNotEmpty())
        <section class="mt-5" aria-labelledby="related-movies-title">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                <h2 class="section-title mb-0" id="related-movies-title">Phim cùng thể loại</h2>
                <a href="{{ route('movies.index', ['genre' => $movie->genre]) }}" class="btn btn-outline-light btn-sm">Xem thêm</a>
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
                if (error.name === 'AbortError') {
                    return;
                }

                shareButton.textContent = 'Không thể chia sẻ. Hãy sao chép liên kết từ thanh địa chỉ.';
                return;
            }
        }

        try {
            if (! navigator.clipboard?.writeText) {
                window.prompt('Sao chép đường dẫn phim:', shareData.url);
                return;
            }

            await navigator.clipboard.writeText(shareData.url);
            shareButton.innerHTML = '<i class="bi bi-check2 me-2" aria-hidden="true"></i>Đã sao chép liên kết';
        } catch {
            shareButton.textContent = 'Không thể sao chép. Hãy sao chép liên kết từ thanh địa chỉ.';
        }
    });
</script>
@endpush

<style>
    #schedule .show-date-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: .5rem;
        margin-bottom: 1.25rem;
    }

    #schedule .show-date-option {
        display: flex;
        min-height: 76px;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .05rem;
        color: #ddd;
        background: #151519;
        border: 1px solid #3a3a43;
        border-radius: .55rem;
        line-height: 1.2;
    }

    #schedule .show-date-option span,
    #schedule .show-date-option small {
        color: #aaa;
        font-size: .72rem;
    }

    #schedule .show-date-option strong {
        font-size: 1.2rem;
    }

    #schedule .show-date-option:hover,
    #schedule .show-date-option.active {
        color: #fff;
        background: #e50914;
        border-color: #e50914;
    }

    #schedule .show-date-option.active span,
    #schedule .show-date-option.active small,
    #schedule .show-date-option:hover span,
    #schedule .show-date-option:hover small {
        color: #fff;
    }

    #schedule .showtime-cinema {
        padding: 1rem 0;
        border-top: 1px solid #3a3a43;
    }

    #schedule .showtime-cinema h5 {
        margin-bottom: .15rem;
    }

    #schedule .showtime-city {
        color: #999;
        font-size: .85rem;
        margin-bottom: .75rem;
    }

    #schedule .showtime-room + .showtime-room {
        margin-top: 1rem;
    }

    #schedule .showtime-room-heading {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: .6rem;
    }

    #schedule .showtime-time-list {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    #schedule .showtime-time {
        display: inline-flex;
        min-width: 82px;
        flex-direction: column;
        align-items: center;
        padding: .45rem .7rem;
        color: #fff;
        text-decoration: none;
        background: #17171c;
        border: 1px solid #a91620;
        border-radius: .45rem;
        transition: background-color .15s ease, border-color .15s ease;
    }

    #schedule .showtime-time:hover,
    #schedule .showtime-time:focus-visible {
        color: #fff;
        background: #e50914;
        border-color: #e50914;
    }

    #schedule .showtime-time small {
        color: #bbb;
        font-size: .7rem;
    }

    #schedule .showtime-time:hover small,
    #schedule .showtime-time:focus-visible small {
        color: #fff;
    }

    #schedule .btn-outline-danger {
        color: #ff6b73;
        border-color: #e50914;
    }

    #schedule .btn-outline-danger:hover,
    #schedule .btn-outline-danger:focus-visible {
        color: #fff;
        background: #e50914;
        border-color: #e50914;
    }

    @media (max-width: 575px) {
        #schedule .show-date-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }
</style>

@endsection
