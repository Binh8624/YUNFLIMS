@extends('layouts.app')

@section('title', '404 - Không tìm thấy trang')

@section('content')
<div class="container py-5">
    <div class="card bg-dark border-secondary mx-auto text-center" style="max-width: 640px;">
        <div class="card-body p-5">
            <div class="display-1 fw-bold text-warning mb-3">404</div>
            <h2 class="mb-3">Không tìm thấy trang</h2>
            <p class="text-secondary mb-4">Trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển.</p>
            <a href="{{ route('home') }}" class="btn btn-red">Quay lại trang chủ</a>
        </div>
    </div>
</div>
@endsection
