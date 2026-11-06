@extends('layouts.app')

@section('title', '403 - Không có quyền truy cập')

@section('content')
<div class="container py-5">
    <div class="card bg-dark border-secondary mx-auto text-center" style="max-width: 640px;">
        <div class="card-body p-5">
            <div class="display-1 fw-bold text-danger mb-3">403</div>
            <h2 class="mb-3">Không có quyền truy cập</h2>
            <p class="text-secondary mb-4">Bạn không có quyền xem hoặc thực hiện hành động trên trang này.</p>
            <a href="{{ route('home') }}" class="btn btn-red">Về trang chủ</a>
        </div>
    </div>
</div>
@endsection
