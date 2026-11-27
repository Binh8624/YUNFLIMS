@extends('layouts.app')

@section('title', 'Lịch chiếu tổng hợp - YUNFLIMS')

@section('content')
<div class="container py-5 schedule-page">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
            <span class="hero-kicker">LỊCH CHIẾU</span>
            <h1 class="section-title mt-2 mb-1">Lịch chiếu tổng hợp</h1>
            <p class="text-secondary mb-0">Lịch chiếu thực tế theo ngày, thành phố, rạp và định dạng.</p>
        </div>
        <a href="{{ route('movies.index') }}" class="btn btn-outline-light">
            <i class="bi bi-film me-2" aria-hidden="true"></i>Khám phá phim
        </a>
    </div>

    @if ($selectedMovie)
        <div class="schedule-selected-movie mb-3">
            <span>Đang ưu tiên phim: <strong>{{ $selectedMovie->title }}</strong></span>
            <a href="{{ route('showtimes.index', request()->except('movie_id')) }}" class="btn btn-sm btn-outline-light">Xem tất cả phim</a>
        </div>
    @endif

    <nav class="schedule-date-strip mb-3" aria-label="Chọn ngày chiếu">
        @foreach ($showDates as $showDate)
            @php($day = \Illuminate\Support\Carbon::parse($showDate))
            @php($weekdays = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'])
            <a
                href="{{ route('showtimes.index', array_merge(request()->query(), ['date' => $showDate])) }}"
                class="schedule-date-option {{ $showDate === $date ? 'active' : '' }}"
                @if ($showDate === $date) aria-current="date" @endif
            >
                <small>{{ $day->isToday() ? 'Hôm nay' : $weekdays[$day->dayOfWeek] }}</small>
                <strong>{{ $day->format('d') }}</strong>
                <span>{{ $day->format('m/Y') }}</span>
            </a>
        @endforeach
        @if ($showDates->isEmpty())
            <span class="text-secondary px-3 py-2">Chưa có ngày chiếu nào được công bố.</span>
        @endif
    </nav>

    <form method="GET" action="{{ route('showtimes.index') }}" class="schedule-filter cinema-panel p-3 p-lg-4 mb-4">
        <input type="hidden" name="date" value="{{ $date }}">
        @if ($filters['movie_id'] ?? null)
            <input type="hidden" name="movie_id" value="{{ $filters['movie_id'] }}">
        @endif
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-xl-2">
                <label for="schedule-search" class="form-label">Tìm phim</label>
                <input id="schedule-search" type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Tên phim...">
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label for="schedule-city" class="form-label">Thành phố</label>
                <select id="schedule-city" name="city" class="form-select" data-schedule-city>
                    <option value="">Tất cả thành phố</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city }}" @selected(($filters['city'] ?? '') === $city)>{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label for="schedule-cinema" class="form-label">Cụm rạp</label>
                <select id="schedule-cinema" name="cinema_id" class="form-select">
                    <option value="">Tất cả rạp</option>
                    @foreach ($cinemas as $cinema)
                        <option value="{{ $cinema->id }}" @selected((string) ($filters['cinema_id'] ?? '') === (string) $cinema->id)>{{ $cinema->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label for="schedule-format" class="form-label">Định dạng</label>
                <select id="schedule-format" name="format" class="form-select">
                    <option value="">Tất cả định dạng</option>
                    @foreach ($formats as $format)
                        <option value="{{ $format->slug }}" @selected(($filters['format'] ?? '') === $format->slug)>{{ $format->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label for="schedule-genre" class="form-label">Thể loại</label>
                <select id="schedule-genre" name="genre" class="form-select">
                    <option value="">Tất cả</option>
                    @foreach ($genres as $genre)
                        <option value="{{ $genre }}" @selected(($filters['genre'] ?? '') === $genre)>{{ $genre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 col-xl-2">
                <button class="btn btn-red w-100" type="submit" aria-label="Lọc lịch chiếu"><i class="bi bi-funnel" aria-hidden="true"></i><span class="d-xl-none ms-2">Lọc</span></button>
            </div>
        </div>
    </form>

    @forelse ($showtimes->groupBy(fn ($showtime) => $showtime->room->cinema->id) as $cinemaShowtimes)
        @php($cinema = $cinemaShowtimes->first()->room->cinema)
        <section class="schedule-cinema-card mb-3" aria-label="Lịch chiếu tại {{ $cinema->name }}">
            <header class="schedule-cinema-heading">
                <div class="schedule-cinema-icon" aria-hidden="true"><i class="bi bi-building"></i></div>
                <div>
                    <h2>{{ $cinema->name }}</h2>
                    <p class="mb-0">{{ $cinema->address }} · {{ $cinema->city }}</p>
                </div>
            </header>

            @foreach ($cinemaShowtimes->groupBy('movie_id') as $movieShowtimes)
                @php($movie = $movieShowtimes->first()->movie)
                <article class="schedule-movie">
                    <img src="{{ $movie->poster ?? 'https://placehold.co/600x900/1b1b22/e50914?text=YUNFLIMS' }}" alt="{{ $movie->title }}" loading="lazy">
                    <div class="schedule-movie-content">
                        <div class="schedule-movie-heading">
                            <div>
                                <h3><a href="{{ route('movies.show', $movie) }}">{{ $movie->title }}</a></h3>
                                <div class="schedule-movie-meta">
                                    <span>{{ implode(' · ', array_slice($movie->genre_labels, 0, 3)) }}</span>
                                    @if ($movie->duration)<span>{{ $movie->duration }} phút</span>@endif
                                    <span class="badge {{ $movie->age_rating_verified ? 'bg-success' : 'bg-warning text-dark' }}" title="{{ $movie->age_rating_verified ? 'Phân loại đã xác minh' : 'Phân loại đang chờ xác minh' }}">{{ $movie->ageRatingLabel() }}</span>
                                </div>
                            </div>
                            <span class="badge badge-red">{{ $movie->format?->name ?? 'Định dạng chưa cập nhật' }}</span>
                        </div>

                        @foreach ($movieShowtimes->groupBy(fn ($showtime) => $showtime->room_id.'-'.$showtime->format_id) as $roomShowtimes)
                            @php($firstShowtime = $roomShowtimes->first())
                            <div class="schedule-room">
                                <div class="schedule-room-heading">
                                    <strong>{{ $firstShowtime->room->name }}</strong>
                                    <span>{{ $firstShowtime->room->format?->name ?? 'Chưa thiết lập định dạng' }}</span>
                                </div>
                                <div class="schedule-time-list">
                                    @foreach ($roomShowtimes as $showtime)
                                        @php($hasSeats = $showtime->room->active_seats_count > 0 && $showtime->unavailable_seats_count < $showtime->room->active_seats_count)
                                        @if ($hasSeats)
                                            <a href="{{ route('bookings.seats', $showtime) }}" class="schedule-time" aria-label="{{ $movie->title }}, {{ $showtime->starts_at->format('d/m H:i') }}, {{ $firstShowtime->room->name }}">
                                                <strong>{{ $showtime->starts_at->format('H:i') }}</strong>
                                                <small>{{ $showtime->format?->name }}</small>
                                            </a>
                                        @else
                                            <span class="schedule-time is-unavailable" aria-label="Suất {{ $showtime->starts_at->format('H:i') }} đã hết ghế hoặc chưa có sơ đồ ghế">
                                                <strong>{{ $showtime->starts_at->format('H:i') }}</strong>
                                                <small>Hết ghế</small>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </section>
    @empty
        <div class="cinema-panel p-4 p-md-5 text-center">
            <i class="bi bi-calendar-x feature-icon" aria-hidden="true"></i>
            @if ($selectedMovie && ! $hasSelectedMovieShowtimes)
                <h2 class="h4 mt-3">Hiện chưa có lịch chiếu cho bộ phim này. Vui lòng quay lại sau.</h2>
                <p class="text-secondary mb-3">Bạn có thể xem lịch chiếu của các phim khác trong hệ thống.</p>
            @else
                <h2 class="h4 mt-3">Chưa có suất chiếu phù hợp</h2>
                <p class="text-secondary mb-3">Không có lịch chiếu thực tế khớp với các bộ lọc đã chọn. Hãy thử ngày hoặc rạp khác.</p>
            @endif
            <a href="{{ route('showtimes.index') }}" class="btn btn-outline-light">Xóa bộ lọc</a>
        </div>
    @endforelse
</div>

<style>
    /* Biến màu chủ đạo theo phong cách Dark Theme & Tím Neon Điện Ảnh */
    :root {
        --primary: #7c3aed;
        --primary-light: #a78bfa;
        --primary-glow: rgba(124, 58, 237, 0.4);
        --background: #0d0c13;
        --surface: #171523;
        --surface-hover: #221f33;
        --surface-card: #151320;
        --border: #332d47;
        --border-subtle: #29243a;
        --text-white: #ffffff;
        --text-light: #d1d5db; /* Xám sáng (#D1D5DB) tương phản cao chuẩn WCAG */
        --text-secondary: #9ca3af;
        --text-purple: #c4b5fd;
    }

    .showtimes-page {
        min-height: 80vh;
        background-color: var(--background);
        color: var(--text-white);
    }

    /* Đảm bảo toàn bộ text-muted trong trang không bị chìm trên nền tối */
    .showtimes-page .text-muted {
        color: var(--text-light) !important;
    }

    /* Hero Banner */
    .showtimes-hero {
        background: radial-gradient(circle at 50% 0%, rgba(124, 58, 237, 0.25) 0%, rgba(13, 12, 19, 0) 70%);
        border-bottom: 1px solid var(--border);
        margin-bottom: 2rem;
    }

    .hero-description {
        color: #d1d5db !important;
        font-size: 1.1rem;
    }

    .badge-purple-subtle {
        background: rgba(124, 58, 237, 0.22);
        color: #ddd6fe;
        border: 1px solid rgba(167, 139, 250, 0.4);
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .text-purple {
        color: var(--primary-light) !important;
    }

    /* Thanh chọn ngày */
    .date-selector-wrapper {
        position: relative;
    }

    .date-scroll-container {
        display: flex;
        gap: 0.75rem;
        overflow-x: auto;
        padding: 0.5rem 0.25rem 1rem 0.25rem;
        scroll-snap-type: x mandatory;
        scrollbar-width: thin;
        scrollbar-color: var(--border) transparent;
    }

    .date-scroll-container::-webkit-scrollbar {
        height: 6px;
    }

    .date-scroll-container::-webkit-scrollbar-thumb {
        background-color: var(--border);
        border-radius: 999px;
    }

    .date-pill {
        flex: 1;
        min-width: 90px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 0.75rem 0.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: #d1d5db;
        transition: all 0.25s ease;
        scroll-snap-align: start;
        position: relative;
    }

    .date-pill:hover {
        background: var(--surface-hover);
        border-color: var(--primary);
        color: #ffffff;
        transform: translateY(-2px);
    }

    .date-pill.active {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        border-color: #c4b5fd;
        color: #ffffff;
        box-shadow: 0 6px 18px rgba(124, 58, 237, 0.45);
        transform: translateY(-2px);
    }

    .date-badge-today {
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.5px;
        color: #fde047;
        margin-bottom: 2px;
    }

    .date-pill.active .date-badge-today {
        color: #fef08a;
    }

    .date-weekday {
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        color: #d1d5db;
    }

    .date-pill.active .date-weekday {
        color: #ffffff;
    }

    .date-day {
        font-size: 1.55rem;
        font-weight: 800;
        line-height: 1.2;
        color: #ffffff;
    }

    .date-month {
        font-size: 0.78rem;
        color: #d1d5db;
    }

    .date-pill.active .date-month {
        color: #ffffff;
    }

    /* Filter Panel */
    .filter-panel {
        background: var(--surface);
        border: 1px solid var(--border) !important;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .filter-label {
        color: #e5e7eb !important;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .custom-dark-select {
        background-color: #110f1a;
        color: #ffffff;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.65rem 0.95rem;
        font-size: 0.92rem;
    }

    .custom-dark-select:focus {
        background-color: #171424;
        color: #ffffff;
        border-color: var(--primary);
        box-shadow: 0 0 0 0.25rem rgba(124, 58, 237, 0.3);
    }

    .custom-dark-select option {
        background-color: #191722;
        color: #ffffff;
    }

    .btn-purple {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: #ffffff;
        border: none;
        font-weight: 600;
        padding: 0.65rem 1rem;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .btn-purple:hover {
        background: linear-gradient(135deg, #6d28d9 0%, #7c3aed 100%);
        color: #ffffff;
        box-shadow: 0 4px 14px rgba(124, 58, 237, 0.5);
    }

    .btn-outline-purple {
        background: transparent;
        color: #ddd6fe;
        border: 1px solid var(--primary-light);
        font-weight: 600;
        padding: 0.65rem 1rem;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .btn-outline-purple:hover {
        background: var(--primary);
        color: #ffffff;
        box-shadow: 0 4px 14px rgba(124, 58, 237, 0.4);
    }

    .btn-outline-reset {
        background: transparent;
        color: #e5e7eb;
        border: 1px solid var(--border);
        font-weight: 600;
        padding: 0.65rem 1rem;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .btn-outline-reset:hover {
        background: #242033;
        color: #ffffff;
        border-color: var(--primary-light);
    }

    /* Movie Schedule Card */
    .movie-schedule-card {
        background: var(--surface-card);
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        transition: transform 0.25s ease, border-color 0.25s ease;
    }

    .movie-schedule-card:hover {
        border-color: rgba(167, 139, 250, 0.5);
    }

    /* Movie Poster Column */
    .movie-poster-column {
        background: #110f1a;
        border-right: 1px solid var(--border);
    }

    .movie-poster-box {
        position: relative;
        width: 100%;
        aspect-ratio: 2 / 3;
        background: #09080e;
        overflow: hidden;
    }

    .movie-poster-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.35s ease;
    }

    .movie-schedule-card:hover .movie-poster-img {
        transform: scale(1.03);
    }

    .movie-age-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        padding: 4px 8px;
        font-size: 0.75rem;
        font-weight: 800;
        border-radius: 6px;
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.6);
        z-index: 2;
    }

    .movie-age-badge-inline {
        display: inline-block;
        padding: 3px 8px;
        font-size: 0.75rem;
        font-weight: 800;
        border-radius: 6px;
        color: #ffffff;
    }

    /* Màu nhãn độ tuổi */
    .badge-age-p { background-color: #10b981; }  /* Phổ biến mọi lứa tuổi */
    .badge-age-k { background-color: #3b82f6; }  /* Dưới 13 có người giám hộ */
    .badge-age-13 { background-color: #f59e0b; } /* 13+ */
    .badge-age-16 { background-color: #f97316; } /* 16+ */
    .badge-age-18 { background-color: #ef4444; } /* 18+ */

    /* YÊU CẦU 1 & 2: Tên phim màu trắng sáng (#ffffff) nổi bật */
    .movie-title-link {
        color: #ffffff !important;
        font-weight: 800;
        letter-spacing: -0.2px;
        transition: color 0.2s ease, text-shadow 0.2s ease;
    }

    .movie-title-link:hover {
        color: #ddd6fe !important;
        text-shadow: 0 0 10px rgba(167, 139, 250, 0.4);
    }

    /* YÊU CẦU 1 & 2: Thể loại và Thời lượng màu xám sáng (#D1D5DB) tương phản cao */
    .movie-meta-line {
        color: #d1d5db;
        font-size: 0.95rem;
        font-weight: 500;
        line-height: 1.5;
    }

    .movie-meta-item {
        color: #d1d5db;
        display: inline-flex;
        align-items: center;
    }

    .movie-meta-bullet {
        color: #a78bfa;
        font-weight: 800;
        margin: 0 0.25rem;
        font-size: 0.9rem;
    }

    .badge-format-tag {
        background: rgba(124, 58, 237, 0.22);
        color: #ddd6fe;
        border: 1px solid rgba(167, 139, 250, 0.4);
        font-weight: 700;
        font-size: 0.72rem;
        padding: 2px 7px;
        border-radius: 5px;
    }

    .btn-outline-trailer {
        background: transparent;
        color: #ddd6fe;
        border: 1px solid rgba(167, 139, 250, 0.5);
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }

    .btn-outline-trailer:hover {
        background: rgba(124, 58, 237, 0.25);
        color: #ffffff;
        border-color: var(--primary-light);
    }

    /* Cột lịch chiếu */
    .cinema-group-block {
        border-bottom: 1px dashed var(--border);
        padding-bottom: 1.25rem;
    }

    .cinema-group-block:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .cinema-name-title {
        color: #ffffff !important;
        font-size: 1.05rem;
        font-weight: 700;
    }

    .cinema-address {
        color: #d1d5db !important;
        font-size: 0.86rem;
    }

    .badge-city-subtle {
        background: rgba(167, 139, 250, 0.15);
        color: #e2e8f0;
        border: 1px solid rgba(167, 139, 250, 0.3);
        font-weight: 500;
    }

    .room-schedule-item {
        background: #181624;
        border: 1px solid rgba(51, 45, 71, 0.8);
        border-radius: 12px;
    }

    .room-name {
        color: #f9fafb !important;
        font-size: 0.95rem;
    }

    .room-showtime-count {
        color: #d1d5db !important;
    }

    .badge-format-pill {
        background: rgba(124, 58, 237, 0.25);
        color: #ede9fe;
        border: 1px solid rgba(167, 139, 250, 0.5);
        font-weight: 700;
        font-size: 0.75rem;
        padding: 3px 8px;
        border-radius: 6px;
    }

    /* Grid các nút giờ chiếu */
    .showtime-pills-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
    }

    .showtime-slot-btn {
        background: #0f0e16;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 0.5rem 0.85rem;
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        min-width: 96px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .showtime-slot-btn .slot-time {
        font-size: 1.1rem;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.2;
    }

    .showtime-slot-btn .slot-end {
        font-size: 0.72rem;
        color: #9ca3af;
        margin-top: 1px;
    }

    .showtime-slot-btn .slot-price {
        font-size: 0.75rem;
        color: #fbbf24;
        font-weight: 700;
        margin-top: 2px;
    }

    .showtime-slot-btn:hover {
        background: var(--primary);
        border-color: #c4b5fd;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(124, 58, 237, 0.5);
    }

    .showtime-slot-btn:hover .slot-time,
    .showtime-slot-btn:hover .slot-end,
    .showtime-slot-btn:hover .slot-price {
        color: #ffffff;
    }

    /* Empty state */
    .empty-state-card {
        background: var(--surface);
        border: 1px solid var(--border) !important;
        border-radius: 16px;
    }

    .empty-icon-wrapper {
        font-size: 3.5rem;
        color: #a78bfa;
        opacity: 0.85;
    }

    .empty-description {
        color: #d1d5db !important;
        font-size: 1rem;
    }

    /* Responsive adjustments */
    @media (max-width: 991.98px) {
        .movie-poster-column {
            border-right: none;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
        }

        .movie-poster-box {
            width: 140px;
            min-width: 140px;
            aspect-ratio: 2 / 3;
        }

        .movie-quick-meta {
            flex: 1;
            padding: 1rem !important;
        }
    }

    @media (max-width: 575.98px) {
        .movie-poster-column {
            flex-direction: column;
            align-items: stretch;
        }

        .movie-poster-box {
            width: 100%;
            max-width: 260px;
            margin: 1rem auto 0 auto;
            border-radius: 10px;
        }

        .showtime-slot-btn {
            min-width: 84px;
            padding: 0.45rem 0.65rem;
        }

        .showtime-slot-btn .slot-time {
            font-size: 1rem;
        }
    }
</style>
@endsection
