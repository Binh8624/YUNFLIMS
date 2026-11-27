@extends('layouts.app')

@section('title', 'Thẻ quà tặng - YUNFLIMS Cinema')

@section('content')
<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="{{ route('services.index') }}" class="text-decoration-none text-secondary">Dịch vụ</a></li>
            <li class="breadcrumb-item active text-light" aria-current="page">Thẻ quà tặng</li>
        </ol>
    </nav>

    <span class="hero-kicker">MÓN QUÀ ĐIỆN ẢNH</span>
    <h1 class="display-6 fw-bold mt-2">Thẻ quà tặng</h1>
    <div class="cinema-panel p-4 p-md-5 mt-4 text-center">
        <i class="bi bi-gift feature-icon" aria-hidden="true"></i>
        <h2 class="h4 mt-3">Chưa có thẻ quà tặng để đặt mua</h2>
        <p class="text-secondary mb-3">Danh mục thẻ và quy trình thanh toán thẻ quà tặng chưa được tích hợp.</p>
        <a href="{{ route('services.index') }}" class="btn btn-outline-light">Khám phá dịch vụ</a>
    </div>
</div>
@endsection
