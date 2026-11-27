@extends('layouts.app')

@section('title', 'Quên mật khẩu - YUNFLIMS')

@section('content')
<div class="container account-shell">
    <div class="account-card">
        <h1 class="h3 mb-3">Quên mật khẩu</h1>
        <p class="text-secondary">Nhập email tài khoản để nhận liên kết đặt lại mật khẩu.</p>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label for="reset-email" class="form-label">Email</label>
            <input id="reset-email" type="email" name="email" class="form-control mb-3"
                   value="{{ old('email') }}" required autocomplete="email" autofocus>
            <button type="submit" class="btn btn-red w-100">Gửi liên kết đặt lại</button>
        </form>
    </div>
</div>
@endsection
