@extends('layouts.app')

@section('title', 'Khuyến mãi - YUNFLIMS Cinema')

@section('content')
<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="{{ route('services.index') }}" class="text-decoration-none text-secondary">Dịch vụ</a></li>
            <li class="breadcrumb-item active text-light" aria-current="page">Khuyến mãi</li>
        </ol>
    </nav>

    <span class="hero-kicker">ƯU ĐÃI YUNFLIMS</span>
    <h1 class="display-6 fw-bold mt-2">Khuyến mãi &amp; voucher</h1>
    <div class="cinema-panel p-4 p-md-5 mt-4 text-center">
        <i class="bi bi-stars feature-icon" aria-hidden="true"></i>
        <h2 class="h4 mt-3">Chưa có chương trình khuyến mãi</h2>
        <p class="text-secondary mb-3">Chương trình, mã giảm giá và thời hạn ưu đãi chưa được cấu hình trong hệ thống.</p>
        <a href="{{ route('showtimes.index') }}" class="btn btn-red">Xem lịch chiếu</a>
    </div>
</div>
@endsection
