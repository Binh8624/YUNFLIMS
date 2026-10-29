@extends('layouts.app')

@section('title', '500 - Lỗi hệ thống')

@section('content')
<div class="container py-5">
    <div class="card bg-dark border-secondary mx-auto text-center" style="max-width: 640px;">
        <div class="card-body p-5">
            <div class="display-1 fw-bold text-danger mb-3">500</div>
            <h2 class="mb-3">Lỗi hệ thống</h2>
            <p class="text-secondary mb-4">Hệ thống đang gặp sự cố. Vui lòng thử lại sau hoặc liên hệ quản trị viên.</p>
            <a href="{{ route('home') }}" class="btn btn-red">Quay lại trang chủ</a>
        </div>
    </div>
</div>
@endsection
