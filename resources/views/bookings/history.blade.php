@extends('layouts.app')

@section('title', 'Lịch sử đặt vé - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h2 class="mb-0">Lịch sử đặt vé</h2>
        <a href="{{ route('showtimes.index') }}" class="btn btn-red">Đặt vé mới</a>
    </div>

    @if ($bookings->isEmpty())
        <div class="alert alert-secondary border-0">
            Bạn chưa có đơn đặt vé nào.
        </div>
    @else
        <div class="row g-3">
            @foreach ($bookings as $booking)
                <div class="col-12">
                    <div class="card bg-dark text-white border-secondary">
                        <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                    <strong class="text-warning">{{ $booking->booking_code }}</strong>
                                    <span class="badge {{ $booking->status === 'confirmed' ? 'bg-success' : ($booking->status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                        {{ $booking->status === 'confirmed' ? 'Đã xác nhận' : ($booking->status === 'pending' ? 'Chờ thanh toán' : 'Đã hủy') }}
                                    </span>
                                </div>

                                <p class="mb-1"><strong>Phim:</strong> {{ $booking->showtime->movie->title }}</p>
                                <p class="mb-1"><strong>Rạp:</strong> {{ $booking->showtime->room->cinema->name ?? 'YUNFLIMS' }}</p>
                                <p class="mb-1"><strong>Suất chiếu:</strong> {{ $booking->showtime->starts_at->format('d/m/Y H:i') }}</p>
                                <p class="mb-0"><strong>Ghế:</strong>
                                    @foreach ($booking->bookingSeats as $seat)
                                        {{ $seat->seat->seat_code ?? ($seat->seat->row_number . $seat->seat->seat_number) }}{{ !$loop->last ? ', ' : '' }}
                                    @endforeach
                                </p>
                            </div>

                            <div class="text-end">
                                <p class="mb-2 text-warning h5">{{ number_format($booking->total_amount) }} đ</p>
                                <a href="{{ route('bookings.show', $booking) }}" class="btn btn-outline-light btn-sm">Xem chi tiết</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $bookings->links() }}
        </div>
    @endif
</div>
@endsection
