@extends('layouts.app')

@section('title', 'Phim yêu thích - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4 flex-wrap">
        <div>
            <div class="hero-kicker">TÀI KHOẢN</div>
            <h1 class="section-title mt-2 mb-0">Phim yêu thích</h1>
        </div>
        <a href="{{ route('profile') }}" class="btn btn-outline-light">Quay lại hồ sơ</a>
    </div>

    @if ($favorites->isEmpty())
        <div class="cinema-panel p-5 text-center">
            <i class="bi bi-heart display-4 text-danger d-block mb-3"></i>
            <h3 class="mb-2">Chưa có phim yêu thích</h3>
            <p class="text-secondary mb-0">Lưu lại những bộ phim bạn muốn theo dõi để dễ đặt vé sau này.</p>
        </div>
    @else
        <div class="row g-4">
            @foreach ($favorites as $movie)
                <div class="col-md-6 col-lg-4">
                    @include('partials.movie-card', ['movie' => $movie])
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $favorites->links() }}
        </div>
    @endif
</div>
@endsection
