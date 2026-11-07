@extends('layouts.app')

@section('title', 'Suất chiếu - YUNFLIMS')

@section('content')
<div class="container py-5">
    <h2 class="mb-4">Chọn suất chiếu</h2>

    @forelse ($showtimes as $showtime)
        <div class="card bg-dark text-white border-secondary mb-3">
            <div class="card-body d-flex justify-content-between
                        align-items-center flex-wrap gap-3">
                <div>
                    <h5>{{ $showtime->movie->title }}</h5>

                    <p class="mb-1">
                        Rạp: {{ $showtime->room->cinema->name ?? 'YUNFLIMS' }}
                    </p>

                    <p class="mb-1">
                        Phòng: {{ $showtime->room->name }}
                    </p>

                    <p class="mb-0">
                        {{ $showtime->starts_at->format('d/m/Y H:i') }}
                    </p>

                    <strong class="text-warning">
                        Từ {{ number_format($showtime->price) }} đ/ghế
                    </strong>
                </div>

                <a href="{{ route('bookings.seats', $showtime) }}"
                   class="btn btn-danger">
                    Chọn ghế
                </a>
            </div>
        </div>
    @empty
        <p>Hiện chưa có suất chiếu nào.</p>
    @endforelse

    {{ $showtimes->links() }}
</div>
@endsection@extends('layouts.app')

@section('title', 'Suất chiếu - YUNFLIMS')

@section('content')
<div class="container py-5">
    <h2 class="mb-4">Chọn suất chiếu</h2>

    @forelse ($showtimes as $showtime)
        <div class="card bg-dark text-white border-secondary mb-3">
            <div class="card-body d-flex justify-content-between
                        align-items-center flex-wrap gap-3">
                <div>
                    <h5>{{ $showtime->movie->title }}</h5>

                    <p class="mb-1">
                        Rạp: {{ $showtime->room->cinema->name ?? 'YUNFLIMS' }}
                    </p>

                    <p class="mb-1">
                        Phòng: {{ $showtime->room->name }}
                    </p>

                    <p class="mb-0">
                        {{ $showtime->starts_at->format('d/m/Y H:i') }}
                    </p>

                    <strong class="text-warning">
                        Từ {{ number_format($showtime->price) }} đ/ghế
                    </strong>
                </div>

                <a href="{{ route('bookings.seats', $showtime) }}"
                   class="btn btn-danger">
                    Chọn ghế
                </a>
            </div>
        </div>
    @empty
        <p>Hiện chưa có suất chiếu nào.</p>
    @endforelse

    {{ $showtimes->links() }}
</div>
@endsection