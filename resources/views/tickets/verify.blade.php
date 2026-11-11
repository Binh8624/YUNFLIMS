@extends('layouts.app')

@section('title', 'Xác minh vé - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="card bg-dark border-success text-white mx-auto" style="max-width: 640px">
        <div class="card-body p-4 text-center">
            <i class="bi bi-patch-check-fill text-success display-4"></i>
            <h1 class="h3 mt-3">Vé hợp lệ</h1>
            <p class="text-secondary">Thanh toán đã được xác nhận.</p>
            <hr class="border-secondary">
            <dl class="row text-start mb-0">
                <dt class="col-sm-4">Mã vé</dt><dd class="col-sm-8">{{ $booking->booking_code }}</dd>
                <dt class="col-sm-4">Phim</dt><dd class="col-sm-8">{{ $booking->showtime->movie->title }}</dd>
                <dt class="col-sm-4">Rạp / phòng</dt><dd class="col-sm-8">{{ $booking->showtime->room->cinema->name }} / {{ $booking->showtime->room->name }}</dd>
                <dt class="col-sm-4">Suất chiếu</dt><dd class="col-sm-8">{{ $booking->showtime->starts_at->format('d/m/Y H:i') }}</dd>
                <dt class="col-sm-4">Ghế</dt><dd class="col-sm-8">{{ $booking->bookingSeats->map(fn ($item) => $item->seat->seat_code ?? ($item->seat->row_number . $item->seat->seat_number))->join(', ') }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection
