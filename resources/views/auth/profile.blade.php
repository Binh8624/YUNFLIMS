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
                    </nav>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card bg-dark border-secondary mb-4 cinema-panel">
                <div class="card-body p-4">
                    <h3 class="mb-3">Thông tin cá nhân</h3>

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
