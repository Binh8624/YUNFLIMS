<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'YUNFLIMS Cinema')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <style>
        :root {
            --ys-red: #e50914;
            --ys-bg: #101014;
            --ys-card: #1b1b22;
            --ys-text: #f5f5f5;
        }

        body {
            background: var(--ys-bg);
            color: var(--ys-text);
            font-family: Arial, sans-serif;
        }

        .navbar {
            background: #08080b;
            border-bottom: 1px solid #292930;
        }

        .brand {
            color: var(--ys-red) !important;
            font-size: 27px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .nav-link {
            color: #ddd !important;
            margin: 0 7px;
        }

        .nav-link:hover {
            color: var(--ys-red) !important;
        }

        .btn-red {
            background: var(--ys-red);
            color: white;
            border: none;
            font-weight: bold;
        }

        .btn-red:hover {
            background: #bd0610;
            color: white;
        }

        .hero {
            min-height: 440px;
            display: flex;
            align-items: center;
            background:
                linear-gradient(90deg, #101014 0%, rgba(16,16,20,.93) 35%, rgba(16,16,20,.15) 100%),
                url('https://image.tmdb.org/t/p/original/8b8R8l88Qje9dn9OE8PY05Nxl1X.jpg')
                center 30% / cover no-repeat;
        }

        .hero-content {
            max-width: 600px;
            padding-top: 65px;
            padding-bottom: 65px;
        }

        .section-title {
            font-size: 27px;
            font-weight: 800;
            border-left: 5px solid var(--ys-red);
            padding-left: 12px;
        }

        .movie-card {
            height: 100%;
            background: var(--ys-card);
            border-radius: 10px;
            overflow: hidden;
            transition: transform .25s ease;
        }

        .movie-card:hover {
            transform: translateY(-6px);
        }

        .movie-poster {
            width: 100%;
            aspect-ratio: 2 / 3;
            object-fit: cover;
            display: block;
            background: #292930;
        }

        .movie-info {
            padding: 15px;
        }

        .movie-title {
            font-size: 17px;
            font-weight: 700;
            min-height: 42px;
        }

        .movie-meta {
            font-size: 13px;
            color: #aaa;
        }

        .badge-red {
            background: var(--ys-red);
        }

        .feature-box {
            background: var(--ys-card);
            border: 1px solid #303039;
            border-radius: 10px;
            padding: 24px;
            height: 100%;
        }

        .feature-icon {
            color: var(--ys-red);
            font-size: 30px;
        }

        .admin-layout {
            display: grid;
            grid-template-columns: 230px minmax(0, 1fr);
            min-height: calc(100vh - 180px);
        }

        .admin-sidebar {
            background: #0b0b0f;
            border-right: 1px solid #292930;
            padding: 1.25rem .75rem;
        }

        .admin-sidebar .nav-link {
            border-radius: .5rem;
            margin: .15rem 0;
            padding: .65rem .8rem;
        }

        .admin-sidebar .nav-link.active,
        .admin-sidebar .nav-link:hover {
            color: #fff !important;
            background: #292930;
        }

        .admin-content {
            min-width: 0;
        }

        footer {
            background: #08080b;
            border-top: 1px solid #292930;
            color: #aaa;
        }

        footer a {
            color: #aaa;
            text-decoration: none;
        }

        footer a:hover {
            color: white;
        }

        @media (max-width: 767px) {
            .admin-layout {
                display: block;
            }

            .admin-sidebar {
                border-right: 0;
                border-bottom: 1px solid #292930;
                padding: .5rem;
            }

            .admin-sidebar .nav {
                flex-direction: row !important;
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            .admin-sidebar .nav-link {
                white-space: nowrap;
            }

            .hero {
                min-height: 360px;
                background-position: center;
            }

            .hero-content {
                padding-top: 45px;
                padding-bottom: 45px;
            }

            .hero h1 {
                font-size: 32px;
            }

            .section-title {
                font-size: 22px;
            }
        }
    </style>

    @stack('styles')
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand brand" href="{{ route('home') }}">
            YUNFLIMS<span class="text-white">.</span>
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavbar"
                aria-controls="mainNavbar"
                aria-expanded="false"
                aria-label="Mở menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('home') }}">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#movies">Phim</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#showtimes">Lịch chiếu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#services">Dịch vụ</a>
                </li>
            </ul>

            <div class="d-flex gap-2 mt-3 mt-lg-0 align-items-lg-center">
                @auth
                    <span class="text-light small">
                        Xin chào, {{ Auth::user()->name }}
                    </span>

                    <a href="{{ route('profile') }}"
                       class="btn btn-outline-light">
                        Hồ sơ
                    </a>

                    <a href="{{ route('bookings.history') }}"
                       class="btn btn-outline-light">
                        Vé của tôi
                    </a>

                    @if (Auth::user()->role === 'admin')
                        <a href="{{ route('admin.dashboard') }}"
                           class="btn btn-outline-light">
                            Quản trị
                        </a>
                    @endif

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-red">
                            Đăng xuất
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                       class="btn btn-outline-light">
                        Đăng nhập
                    </a>

                    <a href="{{ route('register') }}" class="btn btn-red">
                        Đăng ký
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<main>
    @if (session('success'))
        <div class="container mt-3">
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if (request()->is('admin*') && auth()->check() && auth()->user()->role === 'admin')
        <div class="admin-layout">
            <aside class="admin-sidebar" aria-label="Điều hướng quản trị">
                <div class="small text-uppercase text-secondary px-3 mb-2">Quản trị YUNFLIMS</div>
                <nav class="nav flex-column">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
                    <a class="nav-link {{ request()->routeIs('admin.movies.*') ? 'active' : '' }}" href="{{ route('admin.movies.index') }}"><i class="bi bi-film me-2"></i>Phim</a>
                    <a class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}" href="{{ route('admin.reviews.index') }}"><i class="bi bi-star me-2"></i>Đánh giá</a>
                    <a class="nav-link {{ request()->routeIs('admin.cinemas.*') ? 'active' : '' }}" href="{{ route('admin.cinemas.index') }}"><i class="bi bi-building me-2"></i>Rạp chiếu</a>
                    <a class="nav-link {{ request()->routeIs('admin.rooms.*') ? 'active' : '' }}" href="{{ route('admin.rooms.index') }}"><i class="bi bi-door-open me-2"></i>Phòng & ghế</a>
                    <a class="nav-link {{ request()->routeIs('admin.formats.*') ? 'active' : '' }}" href="{{ route('admin.formats.index') }}"><i class="bi bi-badge-3d me-2"></i>Định dạng</a>
                    <a class="nav-link {{ request()->routeIs('admin.showtimes.*') ? 'active' : '' }}" href="{{ route('admin.showtimes.index') }}"><i class="bi bi-calendar2-week me-2"></i>Suất chiếu</a>
                    <a class="nav-link {{ request()->routeIs('admin.pricing.*') ? 'active' : '' }}" href="{{ route('admin.pricing.index') }}"><i class="bi bi-tags me-2"></i>Bảng giá</a>
                    <a class="nav-link {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}" href="{{ route('admin.bookings.index') }}"><i class="bi bi-ticket-perforated me-2"></i>Đơn vé</a>
                    <a class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}"><i class="bi bi-credit-card me-2"></i>Thanh toán</a>
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><i class="bi bi-people me-2"></i>Người dùng</a>
                </nav>
            </aside>
            <div class="admin-content">
                @yield('content')
            </div>
        </div>
    @else
        @yield('content')
    @endif
</main>

<footer class="mt-5 py-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="brand mb-2">YUNFLIMS.</div>
                <p class="small">
                    Trải nghiệm điện ảnh theo cách của bạn.
                    Khám phá phim hay và tận hưởng những khoảnh khắc đáng nhớ.
                </p>
            </div>

            <div class="col-md-3">
                <h6 class="text-white">Khám phá</h6>
                <p><a href="#movies">Phim đang chiếu</a></p>
                <p><a href="#showtimes">Lịch chiếu</a></p>
            </div>

            <div class="col-md-4">
                <h6 class="text-white">Liên hệ</h6>
                <p class="small">Email: support@yunflims.example</p>
                <p class="small">Website đặt vé xem phim YUNFLIMS</p>
            </div>
        </div>

        <hr class="border-secondary">

        <p class="small text-center mb-0">
            &copy; {{ date('Y') }} YUNFLIMS Cinema. All rights reserved.
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')

</body>
</html>