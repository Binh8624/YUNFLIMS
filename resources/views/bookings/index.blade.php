@extends('layouts.app')

@section('title', 'Suất chiếu - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
            <span class="hero-kicker">LỊCH CHIẾU</span>
            <h1 class="section-title mt-2 mb-1">Chọn suất chiếu</h1>
            <p class="text-secondary mb-0">Chọn rạp, giờ chiếu và vị trí ghế phù hợp với bạn.</p>
        </div>
        <a href="{{ route('home') }}#movies" class="btn btn-outline-light">
            <i class="bi bi-film me-2"></i>Khám phá phim
        </a>
    </div>

    <form method="GET" action="{{ route('showtimes.index') }}" class="schedule-filter cinema-panel p-3 p-lg-4 mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-xl-3">
                <label for="schedule-search" class="form-label">Tìm phim</label>
                <input id="schedule-search" type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Tên phim...">
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label for="schedule-city" class="form-label">Thành phố</label>
                <select id="schedule-city" name="city" class="form-select">
                    <option value="">Tất cả thành phố</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city }}" @selected(request('city') === $city)>{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label for="schedule-cinema" class="form-label">Cụm rạp</label>
                <select id="schedule-cinema" name="cinema_id" class="form-select">
                    <option value="">Tất cả rạp</option>
                    @foreach ($cinemas as $cinema)
                        <option value="{{ $cinema->id }}" @selected((string) request('cinema_id') === (string) $cinema->id)>{{ $cinema->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label for="schedule-date" class="form-label">Ngày chiếu</label>
                <input id="schedule-date" type="date" name="date" min="{{ now()->toDateString() }}" value="{{ $date }}" class="form-control">
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label for="schedule-genre" class="form-label">Thể loại</label>
                <select id="schedule-genre" name="genre" class="form-select">
                    <option value="">Tất cả</option>
                    @foreach ($genres as $genre)
                        <option value="{{ $genre }}" @selected(request('genre') === $genre)>{{ $genre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 col-xl-1">
                <button class="btn btn-red w-100" type="submit" aria-label="Lọc lịch chiếu"><i class="bi bi-funnel" aria-hidden="true"></i><span class="d-xl-none ms-2">Lọc</span></button>
            </div>
        </div>
    </form>

    @forelse ($showtimes as $showtime)
        <article class="showtime-card mb-3">
            <img
                src="{{ $showtime->movie->poster ?? 'https://placehold.co/600x900/1b1b22/e50914?text=YUNFLIMS' }}"
                alt="{{ $showtime->movie->title }}"
                class="showtime-card-poster"
                loading="lazy"
            >
            <div>
                <span class="badge badge-red mb-2">{{ $showtime->format?->name ?? '2D' }}</span>
                <h2>{{ $showtime->movie->title }}</h2>
                <div class="showtime-card-meta mb-2">
                    @if ($showtime->movie->genre)
                        <span><i class="bi bi-film me-1"></i>{{ $showtime->movie->genre }}</span>
                    @endif
                    @if ($showtime->movie->duration)
                        <span><i class="bi bi-clock me-1"></i>{{ $showtime->movie->duration }} phút</span>
                    @endif
                    @if ($showtime->movie->age_rating)
                        <span><i class="bi bi-person-badge me-1"></i>{{ $showtime->movie->age_rating }}</span>
                    @endif
                    <span><i class="bi bi-building me-1"></i>{{ $showtime->room->cinema->name ?? 'YUNFLIMS' }}</span>
                    <span><i class="bi bi-door-open me-1"></i>{{ $showtime->room->name }}</span>
                    <span><i class="bi bi-calendar-event me-1"></i>{{ $showtime->starts_at->format('d/m/Y') }}</span>
                </div>
                <p class="mb-0 text-light fw-semibold">
                    <i class="bi bi-clock me-1 text-warning"></i>{{ $showtime->starts_at->format('H:i') }}
                    <span class="text-secondary mx-2">·</span>
                    @if ($showtime->room->active_seats_count === 0)
                        <span class="text-secondary">Sơ đồ ghế chưa sẵn sàng</span>
                    @elseif ($showtime->unavailable_seats_count >= $showtime->room->active_seats_count)
                        <span class="text-danger">Đã hết ghế</span>
                    @else
                        <span class="text-success">Còn ghế</span>
                    @endif
                </p>
                <small class="text-secondary d-block mt-1">Giá vé được tính theo hạng ghế và áp dụng tại bước chọn ghế.</small>
            </div>
            <div class="showtime-card-action">
                @if ($showtime->room->active_seats_count > 0 && $showtime->unavailable_seats_count < $showtime->room->active_seats_count)
                    <a href="{{ route('bookings.seats', $showtime) }}" class="btn btn-red">
                        <i class="bi bi-grid-3x3-gap me-2"></i>Chọn ghế
                    </a>
                @else
                    <span class="btn btn-outline-secondary disabled" aria-disabled="true">Không khả dụng</span>
                @endif
            </div>
        </article>
    @empty
        <div class="cinema-panel p-4 p-md-5 text-center">
            <i class="bi bi-calendar-x feature-icon"></i>
            <h2 class="h4 mt-3">Chưa có suất chiếu phù hợp</h2>
            <p class="text-secondary mb-0">Lịch chiếu mới sẽ được cập nhật sớm. Vui lòng quay lại sau.</p>
        </div>
    @endforelse

    {{ $showtimes->links() }}
</div>
@endsection