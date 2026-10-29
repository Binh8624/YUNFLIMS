
@extends('layouts.app')

@section('title', 'Đăng nhập - YUNFLIMS')

@section('content')
<div class="container py-5" style="max-width: 560px;">
    <div class="p-4 p-md-5 rounded-4"
         style="background: #1b1b22; border: 1px solid #303039;">

        <h2 class="fw-bold mb-2">ĐĂNG NHẬP</h2>
        <p class="text-secondary mb-4">
            Chào mừng bạn trở lại YUNFLIMS.
        </p>

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
                <label class="form-label">Email</label>
                <input type="email" name="email"
                       class="form-control"
                       value="{{ old('email') }}" required
                       autocomplete="username">
            </div>

            <div class="mb-3">
                <label class="form-label">Mật khẩu</label>
                <input type="password" name="password"
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
                ĐĂNG NHẬP
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
