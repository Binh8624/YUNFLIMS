@extends('layouts.app')

@section('title', $movie->title . ' - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="row g-4 align-items-start">
        <div class="col-lg-4">
            <img src="{{ $movie->poster ?? 'https://placehold.co/600x900/1b1b22/e50914?text=YUNFLIMS' }}" alt="{{ $movie->title }}" class="movie-poster rounded-4 shadow" style="max-height: 520px; object-fit: cover;">
        </div>

        <div class="col-lg-8">
            <span class="badge badge-red mb-3 px-3 py-2">{{ $movie->label ?? 'PHIM' }}</span>
            <h1 class="display-6 fw-bold mb-3">{{ $movie->title }}</h1>

            <div class="d-flex flex-wrap gap-3 text-secondary mb-4">
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
            </div>

            <div class="card bg-dark border-secondary mb-4" id="schedule">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="mb-0">Chọn ngày và rạp</h4>
                        <span class="text-secondary small">Chỉ hiển thị suất chiếu tương lai</span>
                    </div>

                    <form method="GET" action="{{ route('movies.show', $movie) }}" class="row g-2 mb-4">
                        <div class="col-sm-5 col-md-4">
                            <label for="show-date" class="form-label">Ngày chiếu</label>
                            <input id="show-date" type="date" name="date" class="form-control" value="{{ $date }}" min="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-sm-5 col-md-5">
                            <label for="show-cinema" class="form-label">Rạp</label>
                            <select id="show-cinema" name="cinema_id" class="form-select">
                                <option value="">Tất cả rạp</option>
                                @foreach ($cinemas as $cinema)
                                    <option value="{{ $cinema->id }}" @selected(request('cinema_id') == $cinema->id)>{{ $cinema->name }} — {{ $cinema->city }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-2 col-md-3 d-flex align-items-end">
                            <button class="btn btn-outline-light w-100">Xem suất</button>
                        </div>
                    </form>

                    @if ($movie->showtimes->isEmpty())
                        <div class="alert alert-secondary border-0 mb-0">Không có suất chiếu phù hợp trong ngày đã chọn. Chọn ngày khác để xem lịch.</div>
                    @else
                        <div class="row g-3">
                            @foreach ($movie->showtimes as $showtime)
                                <div class="col-md-6 col-xl-4">
                                    <div class="border border-secondary rounded p-3 h-100">
                                        <div class="text-warning fw-semibold mb-2">
                                            {{ $showtime->room->cinema->name ?? 'YUNFLIMS' }}
                                        </div>
                                        <div class="mb-2">
                                            <strong>Phòng:</strong> {{ $showtime->room->name }} ({{ $showtime->room->room_code }})
                                        </div>
                                        <div class="mb-2">
                                            <strong>Thời gian:</strong>
                                            {{ $showtime->starts_at->format('d/m/Y H:i') }}
                                        </div>
                                        <div class="mb-2"><strong>Định dạng:</strong> {{ $showtime->format?->name ?? '2D' }}</div>
                                        <div class="mb-3">
                                            <strong>Giá:</strong> {{ number_format($showtime->price) }} đ
                                        </div>
                                        <a
                                            href="{{ route('bookings.seats', $showtime) }}"
                                            class="btn btn-outline-danger fw-bold px-4 py-2"
                                            aria-label="Chọn suất chiếu lúc {{ $showtime->starts_at->format('H:i') }} ngày {{ $showtime->starts_at->format('d/m/Y') }} cho phim {{ $movie->title }}"
                                        >
                                            <i class="bi bi-clock me-1" aria-hidden="true"></i>
                                            {{ $showtime->starts_at->format('H:i') }}
                                            <span class="small fw-normal ms-1">Chọn suất</span>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card bg-dark border-secondary">
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
</div>

<style>
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
</style>

@endsection
