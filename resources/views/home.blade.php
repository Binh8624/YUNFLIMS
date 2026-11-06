@extends('layouts.app')

@section('title', 'YUNFLIMS Cinema - Đặt vé xem phim')

@section('content')
<section class="events-section" aria-labelledby="events-title">
    <div class="container">
        <div class="events-heading">
            <h1 id="events-title">EVENT</h1>
        </div>

        <div class="events-tabs">
            <a href="{{ route('services.index') }}" class="events-tab">
                <i class="bi bi-ticket-perforated me-2"></i>Thành viên YUNFLIMS&nbsp; | &nbsp;Tin mới &amp; Ưu đãi
            </a>
        </div>

        <div class="events-grid">
            <a class="event-card" href="{{ route('services.vouchers') }}">
                <img src="https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&amp;fit=crop&amp;w=800&amp;q=85" alt="Ưu đãi vé xem phim" loading="eager">
                <span>Đồng giá vé xem phim</span>
            </a>
            <a class="event-card" href="{{ route('services.vouchers') }}">
                <img src="https://images.unsplash.com/photo-1517604931442-7e0c8ed2963c?auto=format&amp;fit=crop&amp;w=800&amp;q=85" alt="Gói xem phim online" loading="eager">
                <span>Gói xem phim tiện lợi</span>
            </a>
            <a class="event-card" href="{{ route('services.vouchers') }}">
                <img src="https://images.unsplash.com/photo-1585647347483-22b66260dfff?auto=format&amp;fit=crop&amp;w=800&amp;q=85" alt="Quà tặng thành viên" loading="eager">
                <span>Quà tặng thành viên</span>
            </a>
            <a class="event-card" href="{{ route('services.gift-cards') }}">
                <img src="https://images.unsplash.com/photo-1464349153735-7db50ed83c84?auto=format&amp;fit=crop&amp;w=800&amp;q=85" alt="Ưu đãi sinh nhật" loading="eager">
                <span>Ưu đãi sinh nhật</span>
            </a>
        </div>

        <div class="events-grid events-grid--featured">
            <a class="event-card" href="{{ route('services.vouchers') }}">
                <img src="https://images.unsplash.com/photo-1485846234645-a62644f84728?auto=format&amp;fit=crop&amp;w=800&amp;q=85" alt="Ưu đãi phim đặc biệt" loading="lazy">
                <span>Ưu đãi phim đặc biệt</span>
            </a>
            <a class="event-card" href="{{ route('services.index') }}">
                <img src="https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&amp;fit=crop&amp;w=1200&amp;q=85" alt="Chương trình thành viên YUNFLIMS" loading="lazy">
                <span>Khám phá chương trình thành viên YUNFLIMS</span>
            </a>
            <a class="event-card" href="{{ route('services.gift-cards') }}">
                <img src="https://images.unsplash.com/photo-1533920379810-6bedac961555?auto=format&amp;fit=crop&amp;w=800&amp;q=85" alt="Thuê rạp và sự kiện" loading="lazy">
                <span>Thuê rạp, sự kiện &amp; vé nhóm</span>
            </a>
        </div>
        <div class="events-divider"></div>
    </div>
</section>

<section class="hero">
    <div class="container">
        <div class="hero-content">
            <span class="badge badge-red mb-3 px-3 py-2">PHIM NỔI BẬT</span>
            <h1 class="display-4 fw-bold mb-3">
                THẾ GIỚI ĐIỆN ẢNH<br>
                <span style="color: #e50914;">TRONG TẦM TAY</span>
            </h1>
            <p class="text-light mb-4">
                Khám phá những bộ phim hấp dẫn, chọn suất chiếu phù hợp và đặt chỗ ngay để trải nghiệm điện ảnh trọn vẹn.
            </p>
            <div class="d-flex flex-wrap gap-3">
                <a href="#movies" class="btn btn-red btn-lg">Khám phá phim</a>
                <a href="{{ route('showtimes.index') }}" class="btn btn-outline-light btn-lg">Lịch chiếu</a>
            </div>
        </div>
    </div>
</section>

<section class="container py-5" id="movies">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="section-title mb-0">TÌM PHIM YÊU THÍCH</h2>
        </div>
        <form method="GET" action="{{ route('home') }}" class="d-flex gap-2 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Tìm phim, thể loại..." style="min-width: 220px;">
            <select name="status" class="form-select" style="max-width: 170px;">
                <option value="">Tất cả trạng thái</option>
                <option value="now_showing" {{ request('status') === 'now_showing' ? 'selected' : '' }}>Đang chiếu</option>
                <option value="coming_soon" {{ request('status') === 'coming_soon' ? 'selected' : '' }}>Sắp chiếu</option>
            </select>
            <button type="submit" class="btn btn-red">Tìm kiếm</button>
        </form>
    </div>

    @if ($featured->isNotEmpty())
        <div class="mb-5">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="mb-0 text-white">Phim nổi bật</h3>
            </div>
            <div class="row g-4">
                @foreach ($featured as $movie)
                    <div class="col-6 col-md-4 col-lg-3">
                        @include('partials.movie-card', ['movie' => $movie])
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0 text-white">Danh sách phim</h3>
        <span class="text-secondary small">{{ $movies->count() }} phim</span>
    </div>

    @if ($movies->isEmpty())
        <div class="alert alert-secondary border-0">
            Không tìm thấy phim phù hợp. Hãy thử từ khóa hoặc trạng thái khác.
        </div>
    @else
        <div class="row g-4">
            @foreach ($movies as $movie)
                <div class="col-6 col-md-4 col-lg-3">
                    @include('partials.movie-card', ['movie' => $movie])
                </div>
            @endforeach
        </div>
    @endif

    @if ($comingSoon->isNotEmpty())
        <div class="mt-5">
            <h3 class="mb-3 text-white">Sắp chiếu</h3>
            <div class="row g-4">
                @foreach ($comingSoon as $movie)
                    <div class="col-6 col-md-4 col-lg-3">
                        @include('partials.movie-card', ['movie' => $movie])
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>

<section class="container py-4" id="showtimes">
    <div class="text-center mb-4">
        <h2 class="section-title d-inline-block">ĐẶT VÉ NHANH</h2>
        <p class="text-secondary mt-3">Chỉ với vài bước để sẵn sàng cho bộ phim yêu thích.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="feature-box text-center">
                <i class="bi bi-film feature-icon"></i>
                <h5 class="mt-3">1. Chọn phim</h5>
                <p class="text-secondary mb-0">Tìm bộ phim bạn muốn thưởng thức.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box text-center">
                <i class="bi bi-calendar-event feature-icon"></i>
                <h5 class="mt-3">2. Chọn suất chiếu</h5>
                <p class="text-secondary mb-0">Chọn ngày, rạp và giờ chiếu phù hợp.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box text-center">
                <i class="bi bi-ticket-perforated feature-icon"></i>
                <h5 class="mt-3">3. Nhận vé</h5>
                <p class="text-secondary mb-0">Chọn ghế và xác nhận đặt vé.</p>
            </div>
        </div>
    </div>
</section>

<section class="container py-5" id="services">
    <div class="p-4 p-md-5 rounded-4" style="background: linear-gradient(120deg, #501016, #211116);">
        <div class="row align-items-center g-3">
            <div class="col-md-8">
                <h2 class="fw-bold">SẴN SÀNG CHO SUẤT PHIM TIẾP THEO?</h2>
                <p class="mb-0 text-light">Khám phá danh sách phim và lên kế hoạch cho buổi xem phim cùng YUNFLIMS.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="#movies" class="btn btn-light btn-lg fw-bold">Xem danh sách phim</a>
            </div>
        </div>
    </div>
</section>
@endsection
