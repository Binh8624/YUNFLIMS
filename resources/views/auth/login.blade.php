
@extends('layouts.app')

@section('title', 'Đăng nhập - YUNFLIMS')

@section('content')
<div class="container account-shell">
    <div class="account-card">
        <div class="text-center mb-4">
            <span class="hero-kicker">YUNFLIMS CINEMA</span>
            <h1 class="mt-2 mb-2">Chào mừng trở lại</h1>
            <p class="text-secondary mb-0">Đăng nhập để tiếp tục hành trình điện ảnh của bạn.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('login.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="login-email" class="form-label">Email</label>
                <input id="login-email" type="email" name="email"
                       class="form-control"
                       value="{{ old('email') }}" required
                       autocomplete="username">
            </div>

            <div class="mb-3">
                <label for="login-password" class="form-label">Mật khẩu</label>
                <input id="login-password" type="password" name="password"
                       class="form-control" required
                       autocomplete="current-password">
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox"
                       name="remember" value="1" id="remember">
                <label class="form-check-label" for="remember">
                    Ghi nhớ đăng nhập
                </label>
            </div>

            <button class="btn btn-red w-100 py-2" type="submit">
                <i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập
            </button>
        </form>

        <p class="text-center mt-4 mb-0">
            Chưa có tài khoản?
            <a href="{{ route('register') }}" class="text-danger">
                Đăng ký ngay
            </a>
        </p>
    </div>
</div>
@endsection
