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
            <h2 class="h4 mt-3">Chưa có suất chiếu phù hợp</h2>
            <p class="text-secondary mb-3">Không có lịch chiếu thực tế khớp với các bộ lọc đã chọn. Hãy thử ngày hoặc rạp khác.</p>
            <a href="{{ route('showtimes.index') }}" class="btn btn-outline-light">Xóa bộ lọc</a>
        </div>
    @endforelse
</div>
@endsection