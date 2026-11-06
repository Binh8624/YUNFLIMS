@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="rounded-circle bg-danger d-flex align-items-center justify-content-center text-white fw-bold" style="width: 56px; height: 56px; font-size: 1.2rem;">
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
            @if ($errors->any())
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card bg-dark border-secondary mb-4">
                <div class="card-body p-4">
                    <h3 class="mb-3">Thông tin cá nhân</h3>

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Họ tên</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-red mt-4">Lưu thay đổi</button>
                    </form>
                </div>
            </div>

            <div class="card bg-dark border-secondary">
                <div class="card-body p-4">
                    <h3 class="mb-3">Đổi mật khẩu</h3>

                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Mật khẩu hiện tại</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mật khẩu mới</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Xác nhận mật khẩu</label>
                                <input type="password" name="password_confirmation" class="form-control" required>
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
