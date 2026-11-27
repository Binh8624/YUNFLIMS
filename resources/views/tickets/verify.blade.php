@extends('layouts.app')

@section('title', 'Xác minh vé - YUNFLIMS')

@section('content')
<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="card bg-dark border-secondary text-white mx-auto" style="max-width: 760px">
        <div class="card-body p-4">
            @if ($ticketStatus === 'not_found')
                <h1 class="h3 text-danger">Không tìm thấy mã vé</h1>
                <p class="text-secondary">Mã vé không tồn tại hoặc không hợp lệ.</p>
            @else
                @php
                    $statusLabels = [
                        'valid' => ['Vé hợp lệ', 'text-success'],
                        'used' => ['Vé đã được sử dụng', 'text-warning'],
                        'cancelled' => ['Vé đã hủy hoặc hoàn tiền', 'text-danger'],
                        'unpaid' => ['Vé chưa thanh toán', 'text-warning'],
                        'not_started' => ['Chưa đến giờ check-in', 'text-info'],
                    ];
                    [$statusLabel, $statusClass] = $statusLabels[$ticketStatus];
                @endphp

                <h1 class="h3 {{ $statusClass }}">{{ $statusLabel }}</h1>
                <p class="text-secondary mb-4">{{ $booking->booking_code }}</p>

                <dl class="row mb-0">
                    <dt class="col-sm-4">Phim</dt>
                    <dd class="col-sm-8">{{ $booking->showtime->movie->title }}</dd>
                    <dt class="col-sm-4">Rạp / phòng</dt>
                    <dd class="col-sm-8">{{ $booking->showtime->room->cinema->name }} / {{ $booking->showtime->room->name }}</dd>
                    <dt class="col-sm-4">Suất chiếu</dt>
                    <dd class="col-sm-8">{{ $booking->showtime->starts_at->format('d/m/Y H:i') }}</dd>
                    <dt class="col-sm-4">Ghế</dt>
                    <dd class="col-sm-8">{{ $booking->bookingSeats->map(fn ($item) => $item->seat->seat_code ?? ($item->seat->row_number . $item->seat->seat_number))->join(', ') }}</dd>
                    @if ($ticketStatus === 'used' && $ticketUsedAt)
                        <dt class="col-sm-4">Đã check-in lúc</dt>
                        <dd class="col-sm-8">{{ $ticketUsedAt->format('d/m/Y H:i:s') }}</dd>
                    @endif
                </dl>

                @if ($ticketStatus === 'valid')
                    <p class="alert alert-success mt-3 mb-0">Vé đã thanh toán và sẵn sàng sử dụng.</p>
                @elseif ($ticketStatus === 'not_started')
                    <p class="alert alert-info mt-3 mb-0">
                        Chỉ có thể check-in từ {{ $booking->showtime->starts_at->format('d/m/Y H:i') }}.
                    </p>
                @endif

                @if ($ticketCheckins->isNotEmpty())
                    <div class="mt-4">
                        <h2 class="h5">Lịch sử check-in</h2>
                        <ul class="mb-0">
                            @foreach ($ticketCheckins as $checkin)
                                <li>
                                    {{ $checkin->checked_in_at->format('d/m/Y H:i:s') }}
                                    @if ($checkin->actor)
                                        — {{ $checkin->actor->name }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($canCheckIn && $ticketStatus === 'valid')
                    <form method="POST" action="{{ route('tickets.checkin', ['bookingCode' => $booking->booking_code]) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="btn btn-success">Xác nhận check-in vé</button>
                    </form>
                @endif
            @endif

            @if ($canCheckIn)
                <div class="mt-4 d-flex flex-wrap gap-2">
                    <a href="{{ route('tickets.scan') }}" class="btn btn-outline-light">Mở quét vé</a>
                    <form method="POST" action="{{ route('tickets.lookup') }}" class="d-flex gap-2">
                        @csrf
                        <input type="text" name="booking_code" class="form-control"
                               placeholder="Nhập mã vé" maxlength="64" required>
                        <button type="submit" class="btn btn-outline-light text-nowrap">Tra cứu mã vé</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
