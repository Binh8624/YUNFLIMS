
@extends('layouts.app')

@section('title', 'Đăng ký - YUNFLIMS')

@section('content')
<div class="container account-shell">
    <div class="account-card">
        <div class="text-center mb-4">
            <span class="hero-kicker">THÀNH VIÊN YUNFLIMS</span>
            <h1 class="mt-2 mb-2">Tạo tài khoản</h1>
            <p class="text-secondary mb-0">Lưu lại vé và quản lý trải nghiệm xem phim của bạn.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="register-name" class="form-label">Họ và tên</label>
                <input id="register-name" type="text" name="name"
                       class="form-control"
                       value="{{ old('name') }}" required
                       autocomplete="name">
            </div>

            <div class="mb-3">
                <label for="register-email" class="form-label">Email</label>
                <input id="register-email" type="email" name="email"
                       class="form-control"
                       value="{{ old('email') }}" required
                       autocomplete="email">
            </div>

            <div class="mb-3">
                <label for="register-password" class="form-label">Mật khẩu</label>
                <input id="register-password" type="password" name="password"
                       class="form-control" required
                       minlength="8" autocomplete="new-password">
                <small class="text-secondary">
                    Tối thiểu 8 ký tự.
                </small>
            </div>

            <div class="mb-4">
                <label for="register-password-confirmation" class="form-label">Xác nhận mật khẩu</label>
                <input id="register-password-confirmation" type="password" name="password_confirmation"
                       class="form-control" required
                       autocomplete="new-password">
            </div>

            <button class="btn btn-red w-100 py-2" type="submit">
                <i class="bi bi-person-plus me-2"></i>Tạo tài khoản
            </button>
        </form>

        <p class="text-center mt-4 mb-0">
            Đã có tài khoản?
            <a href="{{ route('login') }}" class="text-danger">
                Đăng nhập
            </a>
        </p>
    </div>
</div>
@endsection
