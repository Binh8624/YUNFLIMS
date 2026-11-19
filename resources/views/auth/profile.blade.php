@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card bg-dark border-secondary h-100 cinema-panel">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="profile-avatar">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="mb-0">{{ $user->name }}</h4>
                            <small class="text-secondary">{{ $user->email }}</small>
                        </div>
                    </div>

                    <nav class="nav flex-column gap-2">
                        <a href="{{ route('profile') }}" class="btn btn-red text-start">Thông tin cá nhân</a>
                        <a href="{{ route('bookings.history') }}" class="btn btn-outline-light text-start">Lịch sử đặt vé</a>
                        <a href="{{ route('favorites.index') }}" class="btn btn-outline-light text-start">Phim yêu thích</a>
                    </nav>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card bg-dark border-secondary mb-4 cinema-panel">
                <div class="card-body p-4">
                    <h3 class="mb-3">Thông tin cá nhân</h3>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="border border-secondary rounded p-3 h-100">
                                <div class="text-secondary small text-uppercase">Hạng thành viên</div>
                                <div class="fs-5 fw-bold mt-2">{{ $user->memberTierLabel() }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border border-secondary rounded p-3 h-100">
                                <div class="text-secondary small text-uppercase">Điểm tích lũy</div>
                                <div class="fs-5 fw-bold mt-2">{{ number_format($user->loyalty_points ?? 0) }} điểm</div>
                            </div>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                        </div>
                    @endif
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="profile-name" class="form-label">Họ tên</label>
                                <input id="profile-name" type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required autocomplete="name">
                            </div>
                            <div class="col-md-6">
                                <label for="profile-email" class="form-label">Email</label>
                                <input id="profile-email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required autocomplete="email">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-red mt-4">Lưu thay đổi</button>
                    </form>
                </div>
            </div>

            <div class="card bg-dark border-secondary mb-4 cinema-panel">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                        <h3 class="mb-0">Phim yêu thích</h3>
                        <a href="{{ route('favorites.index') }}" class="btn btn-outline-light btn-sm">Xem tất cả</a>
                    </div>

                    @if ($favorites->isEmpty())
                        <p class="text-secondary mb-0">Bạn chưa lưu phim nào vào danh sách yêu thích.</p>
                    @else
                        <div class="row g-3">
                            @foreach ($favorites as $favorite)
                                <div class="col-md-6 col-xl-4">
                                    <div class="border border-secondary rounded overflow-hidden h-100">
                                        <a href="{{ route('movies.show', $favorite) }}" class="d-block text-decoration-none">
                                            <img src="{{ $favorite->poster ?? 'https://placehold.co/600x900/1b1b22/e50914?text=YUNFLIMS' }}" alt="{{ $favorite->title }}" class="img-fluid" style="aspect-ratio: 2 / 3; object-fit: cover; width: 100%;">
                                        </a>
                                        <div class="p-3">
                                            <h5 class="mb-2"><a href="{{ route('movies.show', $favorite) }}" class="text-white text-decoration-none">{{ $favorite->title }}</a></h5>
                                            <div class="small text-secondary">{{ $favorite->format?->name ?? 'Định dạng chưa cập nhật' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card bg-dark border-secondary cinema-panel">
                <div class="card-body p-4">
                    <h3 class="mb-3">Đổi mật khẩu</h3>

                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="current-password" class="form-label">Mật khẩu hiện tại</label>
                                <input id="current-password" type="password" name="current_password" class="form-control" required autocomplete="current-password">
                            </div>
                            <div class="col-md-4">
                                <label for="new-password" class="form-label">Mật khẩu mới</label>
                                <input id="new-password" type="password" name="password" class="form-control" required autocomplete="new-password">
                            </div>
                            <div class="col-md-4">
                                <label for="password-confirmation" class="form-label">Xác nhận mật khẩu</label>
                                <input id="password-confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-red mt-4">Đổi mật khẩu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
