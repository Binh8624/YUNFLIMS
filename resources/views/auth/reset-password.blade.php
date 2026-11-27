@extends('layouts.app')

@section('title', 'Đặt lại mật khẩu - YUNFLIMS')

@section('content')
<div class="container account-shell">
    <div class="account-card">
        <h1 class="h3 mb-3">Đặt lại mật khẩu</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="mb-3">
                <label for="reset-email" class="form-label">Email</label>
                <input id="reset-email" type="email" name="email" class="form-control"
                       value="{{ old('email', $email) }}" required autocomplete="email">
            </div>
            <div class="mb-3">
                <label for="reset-password" class="form-label">Mật khẩu mới</label>
                <input id="reset-password" type="password" name="password" class="form-control"
                       required autocomplete="new-password">
                <small class="text-secondary">Tối thiểu 12 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt.</small>
            </div>
            <div class="mb-4">
                <label for="reset-password-confirmation" class="form-label">Xác nhận mật khẩu</label>
                <input id="reset-password-confirmation" type="password" name="password_confirmation"
                       class="form-control" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-red w-100">Lưu mật khẩu mới</button>
        </form>
    </div>
</div>
@endsection
