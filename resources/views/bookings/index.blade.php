@extends('layouts.app')

@section('title', 'Lịch Chiếu Phim - YUNFLIMS Cinema')

@section('content')
<div class="showtimes-page">
    <!-- Header & Hero Banner -->
    <header class="showtimes-hero">
        <div class="container text-center py-5">
            <span class="badge badge-purple-subtle mb-3 px-3 py-2 text-uppercase">
                <i class="bi bi-camera-reels me-1"></i> Hệ thống rạp chiếu YUNFLIMS
            </span>
            <h1 class="display-5 fw-bold text-white mb-2">LỊCH CHIẾU PHIM</h1>
            <p class="lead hero-description mx-auto" style="max-width: 650px;">
                Khám phá lịch chiếu và lựa chọn suất chiếu phù hợp tại các cụm rạp YUNFLIMS.
            </p>
        </div>
    </header>

    <div class="container pb-5">
        <!-- Thanh chọn ngày 7 ngày tiếp theo -->
        <section class="date-selector-wrapper mb-4" aria-label="Chọn ngày chiếu">
            <div class="date-scroll-container">
                @foreach ($days as $day)
                    @php
                        $isSelected = ($day['date'] === $selectedDate);
                        $dateUrl = route('showtimes.index', array_filter([
                            'date' => $day['date'],
                            'cinema_id' => $cinemaId,
                            'movie_id' => $movieId,
                        ]));
                    @endphp
                    <a href="{{ $dateUrl }}"
                       class="date-pill {{ $isSelected ? 'active' : '' }}"
                       aria-current="{{ $isSelected ? 'date' : 'false' }}">
                        @if ($day['is_today'])
                            <span class="date-badge-today">HÔM NAY</span>
                        @else
                            <span class="date-weekday">{{ $day['day_of_week_short'] }}</span>
                        @endif
                        <strong class="date-day">{{ $day['day'] }}</strong>
                        <span class="date-month">Thg {{ $day['month'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        <!-- Thanh bộ lọc lịch chiếu (Rạp, Phim, Đặt lại) -->
        <section class="filter-panel card border-0 mb-4" aria-label="Bộ lọc lịch chiếu">
            <div class="card-body p-3 p-md-4">
                <form method="GET" action="{{ route('showtimes.index') }}" class="row g-3 align-items-end" id="showtimesFilterForm">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">

                    <!-- Lọc theo Rạp -->
                    <div class="col-12 col-md-5">
                        <label for="filterCinema" class="form-label filter-label">
                            <i class="bi bi-geo-alt-fill text-purple me-1"></i> Rạp chiếu
                        </label>
                        <select id="filterCinema" name="cinema_id" class="form-select custom-dark-select" onchange="document.getElementById('showtimesFilterForm').submit()">
                            <option value="">-- Tất cả rạp chiếu --</option>
                            @foreach ($cinemas as $cinema)
                                <option value="{{ $cinema->id }}" @selected($cinemaId == $cinema->id)>
                                    {{ $cinema->name }} @if($cinema->city) ({{ $cinema->city }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Lọc theo Phim -->
                    <div class="col-12 col-md-5">
                        <label for="filterMovie" class="form-label filter-label">
                            <i class="bi bi-film text-purple me-1"></i> Phim đang chiếu
                        </label>
                        <select id="filterMovie" name="movie_id" class="form-select custom-dark-select" onchange="document.getElementById('showtimesFilterForm').submit()">
                            <option value="">-- Tất cả phim --</option>
                            @foreach ($movies as $movie)
                                <option value="{{ $movie->id }}" @selected($movieId == $movie->id)>
                                    {{ $movie->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Nút thao tác -->
                    <div class="col-12 col-md-2 d-flex gap-2">
                        @if ($cinemaId || $movieId)
                            <a href="{{ route('showtimes.index', ['date' => $selectedDate]) }}"
                               class="btn btn-outline-reset w-100"
                               title="Đặt lại bộ lọc về mặc định">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Đặt lại
                            </a>
                        @else
                            <button type="submit" class="btn btn-purple w-100">
                                <i class="bi bi-funnel me-1"></i> Lọc
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </section>

        <!-- Danh sách phim và suất chiếu -->
        @if ($groupedByMovie->isNotEmpty())
            <div class="movie-schedule-list d-flex flex-column gap-4">
                @foreach ($groupedByMovie as $group)
                    @php
                        $movie = $group['movie'];
                        $cinemasInGroup = $group['cinemas'];
                        $ageRating = strtoupper(trim($movie->age_rating ?? 'P'));
                        $ageBadgeClass = match ($ageRating) {
                            'P' => 'badge-age-p',
                            'K' => 'badge-age-k',
                            'C13', 'T13', '13+' => 'badge-age-13',
                            'C16', 'T16', '16+' => 'badge-age-16',
                            'C18', 'T18', '18+' => 'badge-age-18',
                            default => 'badge-age-p',
                        };
                    @endphp

                    <article class="movie-schedule-card">
                        <div class="row g-0">
                            <!-- Cột bên trái: Poster & Thông tin tóm tắt -->
                            <div class="col-12 col-lg-3 col-xl-3 movie-poster-column">
                                <div class="movie-poster-box">
                                    <img src="{{ $movie->poster ?? 'https://placehold.co/600x900/191722/7c3aed?text=' . urlencode($movie->title) }}"
                                         alt="{{ $movie->title }}"
                                         class="movie-poster-img"
                                         loading="lazy">
                                    <span class="movie-age-badge {{ $ageBadgeClass }}">
                                        {{ $movie->age_rating ?? 'P' }}
                                    </span>
                                </div>

                                <div class="movie-quick-meta p-3">
                                    <!-- Tên phim trên Mobile / Tablet -->
                                    <div class="d-flex align-items-center gap-2 mb-1 d-lg-none flex-wrap">
                                        <span class="movie-age-badge-inline {{ $ageBadgeClass }}">
                                            {{ $movie->age_rating ?? 'P' }}
                                        </span>
                                        <h2 class="h5 fw-bold mb-0">
                                            <a href="{{ route('movies.show', $movie) }}" class="movie-title-link text-decoration-none">
                                                {{ $movie->title }}
                                            </a>
                                        </h2>
                                    </div>

                                    <!-- Tên phim bên dưới poster cho Desktop -->
                                    <div class="d-none d-lg-block mb-1">
                                        <h3 class="h6 fw-bold mb-0">
                                            <a href="{{ route('movies.show', $movie) }}" class="movie-title-link text-decoration-none">
                                                {{ $movie->title }}
                                            </a>
                                        </h3>
                                    </div>

                                    <!-- Ngay bên dưới tên phim: Thể loại • Thời lượng (màu xám sáng #D1D5DB) -->
                                    <div class="movie-meta-line d-flex align-items-center flex-wrap gap-1 my-2">
                                        <span class="movie-meta-item">{{ $movie->genre }}</span>
                                        <span class="movie-meta-separator">•</span>
                                        <span class="movie-meta-item">{{ $movie->duration }} phút</span>
                                    </div>

                                    @if ($movie->trailer_url)
                                        <a href="{{ $movie->trailer_url }}" target="_blank" rel="noopener noreferrer"
                                           class="btn btn-sm btn-outline-trailer w-100 mt-1">
                                            <i class="bi bi-play-circle-fill me-1"></i> Xem Trailer
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <!-- Cột bên phải: Danh sách rạp & suất chiếu -->
                            <div class="col-12 col-lg-9 col-xl-9 movie-schedule-column">
                                <div class="p-3 p-md-4">
                                    <!-- Khối thông tin phim chính trên Desktop -->
                                    <div class="movie-desktop-header d-none d-lg-block pb-3 mb-3 border-bottom border-secondary-subtle">
                                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                            <span class="movie-age-badge-inline {{ $ageBadgeClass }}">
                                                {{ $movie->age_rating ?? 'P' }}
                                            </span>
                                            <h2 class="h4 fw-bold mb-0">
                                                <a href="{{ route('movies.show', $movie) }}" class="movie-title-link text-decoration-none">
                                                    {{ $movie->title }}
                                                </a>
                                            </h2>
                                        </div>

                                        <!-- Ngay bên dưới tên phim: Thể loại • Thời lượng (Màu xám sáng #D1D5DB, độ tương phản cao) -->
                                        <div class="movie-meta-line d-flex align-items-center flex-wrap gap-2">
                                            <span class="movie-meta-item">
                                                <i class="bi bi-film text-purple me-1"></i>{{ $movie->genre }}
                                            </span>
                                            <span class="movie-meta-separator">•</span>
                                            <span class="movie-meta-item">
                                                <i class="bi bi-clock text-purple me-1"></i>{{ $movie->duration }} phút
                                            </span>
                                            @if ($movie->formats->isNotEmpty())
                                                <span class="movie-meta-separator">•</span>
                                                <span class="d-inline-flex gap-1">
                                                    @foreach ($movie->formats as $format)
                                                        <span class="badge badge-format-tag">{{ $format->name }}</span>
                                                    @endforeach
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Nhóm theo từng Rạp -->
                                    <div class="cinema-showtimes-container d-flex flex-column gap-4">
                                        @foreach ($cinemasInGroup as $cinemaItem)
                                            @php
                                                $cinema = $cinemaItem['cinema'];
                                                $rooms = $cinemaItem['rooms'];
                                            @endphp

                                            <section class="cinema-group-block">
                                                <div class="cinema-header mb-2 d-flex align-items-center gap-2">
                                                    <i class="bi bi-building-fill text-purple fs-5"></i>
                                                    <h3 class="h6 fw-bold text-uppercase mb-0 cinema-name-title">
                                                        {{ $cinema->name }}
                                                    </h3>
                                                    @if ($cinema->city)
                                                        <span class="badge badge-city-subtle small">{{ $cinema->city }}</span>
                                                    @endif
                                                </div>

                                                @if ($cinema->address)
                                                    <p class="cinema-address small mb-3">
                                                        <i class="bi bi-geo-alt me-1 text-purple"></i> {{ $cinema->address }}
                                                    </p>
                                                @endif

                                                <!-- Nhóm theo Phòng & Định dạng -->
                                                <div class="room-schedule-list d-flex flex-column gap-3">
                                                    @foreach ($rooms as $roomItem)
                                                        @php
                                                            $room = $roomItem['room'];
                                                            $format = $roomItem['format'];
                                                            $showtimes = $roomItem['showtimes'];
                                                        @endphp

                                                        <div class="room-schedule-item p-3">
                                                            <div class="room-info mb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <span class="room-name fw-semibold">
                                                                        <i class="bi bi-door-open-fill text-purple me-1"></i> {{ $room->name }}
                                                                    </span>
                                                                    <span class="badge badge-format-pill">
                                                                        {{ $format?->name ?? '2D' }}
                                                                    </span>
                                                                </div>
                                                                <span class="room-showtime-count small">
                                                                    {{ $showtimes->count() }} suất chiếu
                                                                </span>
                                                            </div>

                                                            <!-- Danh sách các nút giờ chiếu -->
                                                            <div class="showtime-pills-grid">
                                                                @foreach ($showtimes as $showtime)
                                                                    @php
                                                                        $startFormatted = $showtime->starts_at->format('H:i');
                                                                        $endTime = $showtime->ends_at
                                                                            ? $showtime->ends_at->format('H:i')
                                                                            : $showtime->starts_at->copy()->addMinutes($movie->duration ?: 120)->format('H:i');
                                                                    @endphp

                                                                    <a href="{{ route('bookings.seats', $showtime) }}"
                                                                       class="showtime-slot-btn"
                                                                       title="Chọn suất chiếu lúc {{ $startFormatted }} - Giá: {{ number_format($showtime->price) }} đ"
                                                                       aria-label="Suất chiếu {{ $startFormatted }} đến {{ $endTime }}, giá {{ number_format($showtime->price) }} đồng">
                                                                        <span class="slot-time">{{ $startFormatted }}</span>
                                                                        <span class="slot-end">~ {{ $endTime }}</span>
                                                                        <span class="slot-price">{{ number_format($showtime->price) }} đ</span>
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </section>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <!-- Trạng thái không có suất chiếu (Empty State) -->
            <section class="empty-state-card card border-0 text-center py-5 px-3">
                <div class="card-body py-4">
                    <div class="empty-icon-wrapper mb-3">
                        <i class="bi bi-calendar2-x"></i>
                    </div>
                    <h2 class="h4 fw-bold text-white mb-2">Hiện chưa có suất chiếu cho ngày này</h2>
                    <p class="empty-description mx-auto mb-4" style="max-width: 540px;">
                        @if ($cinemaId || $movieId)
                            Không tìm thấy suất chiếu nào phù hợp với bộ lọc bạn đã chọn trong ngày
                            <strong>{{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }}</strong>.
                        @else
                            Hiện tại hệ thống rạp YUNFLIMS chưa có suất chiếu được xếp lịch cho ngày
                            <strong>{{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }}</strong>.
                        @endif
                        Vui lòng chọn ngày khác hoặc thử điều chỉnh bộ lọc.
                    </p>

                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        @if ($cinemaId || $movieId)
                            <a href="{{ route('showtimes.index', ['date' => $selectedDate]) }}" class="btn btn-purple">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Xóa bộ lọc
                            </a>
                        @endif

                        @php
                            $todayDate = now()->toDateString();
                            $tomorrowDate = now()->addDay()->toDateString();
                        @endphp

                        @if ($selectedDate !== $todayDate)
                            <a href="{{ route('showtimes.index', ['date' => $todayDate]) }}" class="btn btn-outline-purple">
                                <i class="bi bi-calendar-check me-1"></i> Xem lịch Hôm Nay
                            </a>
                        @endif

                        @if ($selectedDate !== $tomorrowDate)
                            <a href="{{ route('showtimes.index', ['date' => $tomorrowDate]) }}" class="btn btn-outline-reset">
                                <i class="bi bi-calendar-plus me-1"></i> Xem lịch Ngày Mai
                            </a>
                        @endif
                    </div>
                </div>
            </section>
        @endif
    </div>
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
