
@extends('layouts.app')

@section('title', 'Đăng ký - YUNFLIMS')

@section('content')
<div class="container py-5" style="max-width: 560px;">
    <div class="p-4 p-md-5 rounded-4"
         style="background: #1b1b22; border: 1px solid #303039;">

        <h2 class="fw-bold mb-2">TẠO TÀI KHOẢN</h2>
        <p class="text-secondary mb-4">
            Tham gia YUNFLIMS để trải nghiệm điện ảnh.
        </p>

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
                <label class="form-label">Họ và tên</label>
                <input type="text" name="name"
                       class="form-control"
                       value="{{ old('name') }}" required
                       autocomplete="name">
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email"
                       class="form-control"
                       value="{{ old('email') }}" required
                       autocomplete="email">
            </div>

            <div class="mb-3">
                <label class="form-label">Mật khẩu</label>
                <input type="password" name="password"
                       class="form-control" required
                       minlength="8" autocomplete="new-password">
                <small class="text-secondary">
                    Tối thiểu 8 ký tự.
                </small>
            </div>

            <div class="mb-4">
                <label class="form-label">Xác nhận mật khẩu</label>
                <input type="password" name="password_confirmation"
                       class="form-control" required
                       autocomplete="new-password">
            </div>

            <button class="btn btn-red w-100 py-2" type="submit">
                ĐĂNG KÝ
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
