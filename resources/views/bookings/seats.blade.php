@extends('layouts.app')

@section('title', 'Chọn ghế - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="booking-step">
        <span class="booking-step-number">02</span>
        <div>
            <span class="hero-kicker">ĐẶT VÉ</span>
            <h1 class="h3 mb-0 mt-1">Chọn ghế yêu thích</h1>
        </div>
    </div>

    <div class="card bg-dark text-white border-secondary mb-4 cinema-panel">
        <div class="card-body">
            <h4>{{ $showtime->movie->title }}</h4>
            <p class="mb-1">
                Rạp: {{ $showtime->room->cinema->name ?? 'YUNFLIMS' }}
            </p>
            <p class="mb-1">
                Phòng: {{ $showtime->room->name }}
            </p>
            <p class="mb-0">
                Suất chiếu: {{ $showtime->starts_at->format('d/m/Y H:i') }}
            </p>
            <p class="mb-1">
                Định dạng: {{ $showtime->format?->name ?? '2D' }}
            </p>
            <p class="mb-0">
                Giá được tính theo hạng ghế, định dạng, ngày và khung giờ.
            </p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="text-center mb-4 px-3">
        <div class="seat-screen mx-auto">
            <div class="seat-screen-logo" aria-label="YUNFLIMS">
                YUNFLIMS<span>.</span>
            </div>
            <div class="seat-screen-label">MÀN HÌNH</div>
        </div>
    </div>

    <form method="POST"
          action="{{ route('bookings.store', $showtime) }}">
        @csrf

        <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
            <span><span class="badge bg-secondary">■</span> Ghế thường</span>
            <span><span class="badge bg-warning text-dark">■</span> Ghế VIP</span>
            <span><span class="badge bg-info text-dark">■</span> Ghế đôi</span>
            <span><span class="badge bg-danger">■</span> Đã đặt / đang giữ</span>
            <span><span class="badge bg-success">■</span> Đang chọn</span>
            <span><span class="badge bg-dark border">■</span> Khóa / hỏng</span>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
            @foreach (['standard' => 'Ghế thường', 'vip' => 'Ghế VIP', 'couple' => 'Ghế đôi'] as $seatType => $seatLabel)
                @if ($seat = $seats->firstWhere('seat_type', $seatType))
                    <span>{{ $seatLabel }}: <strong>{{ number_format($priceMap[$seat->id], 0, ',', '.') }} đ</strong></span>
                @endif
            @endforeach
        </div>

        <div class="seat-map-scroll" aria-label="Sơ đồ ghế, có thể cuộn ngang trên màn hình nhỏ">
            <div class="seat-map">
                @foreach ($seats->groupBy('row_number') as $rowNumber => $rowSeats)
                    <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2 mb-2 seat-row">
                <span class="text-secondary me-2">{{ chr(64 + (int) $rowNumber) }}</span>
                @foreach ($rowSeats as $seat)
                    @php
                        $booked = in_array($seat->id, $bookedSeatIds);
                        $disabled = $booked || ! $seat->is_active;
                    @endphp

                    <label class="seat-label" title="{{ $seat->seat_type }} · {{ number_format($priceMap[$seat->id]) }} đ">
                        <input
                            type="checkbox"
                            name="seats[]"
                            value="{{ $seat->id }}"
                            class="seat-checkbox"
                            data-price="{{ $priceMap[$seat->id] }}"
                            aria-label="Ghế {{ $seat->seat_code ?? ($seat->row_number . $seat->seat_number) }}, {{ $seat->seat_type }}, {{ number_format($priceMap[$seat->id]) }} đồng"
                            {{ $disabled ? 'disabled' : '' }}
                            {{ in_array($seat->id, old('seats', [])) ? 'checked' : '' }}
                        >

                        <span class="seat {{ $booked ? 'booked' : '' }} {{ ! $seat->is_active ? 'disabled-seat' : '' }} {{ $seat->seat_type }}">
                            {{ $seat->seat_code ?? ($seat->row_number . $seat->seat_number) }}
                        </span>
                    </label>
                @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <div id="selected-seats" class="selected-seats mb-3" aria-live="polite">
            <span class="text-secondary">Chưa chọn ghế</span>
        </div>

        <div class="booking-total-bar">
            <div>
                <p>Tổng tiền</p>
                <strong id="total">0 đ</strong>
                <span id="selected-count" class="small text-secondary ms-2">0 ghế</span>
            </div>
            <button type="submit" class="btn btn-danger px-5" id="submit-booking">
                Xác nhận đặt vé
            </button>
        </div>
    </form>
</div>

<script>
    const checkboxes = document.querySelectorAll('.seat-checkbox');
    const totalElement = document.getElementById('total');
    const selectedList = document.getElementById('selected-seats');
    const selectedCountElement = document.getElementById('selected-count');

    function updateTotal() {
        let total = 0;
        const selected = [];

        checkboxes.forEach(function (checkbox) {
            if (checkbox.checked) {
                total += Number(checkbox.dataset.price);
                selected.push(checkbox);
            }
        });

        totalElement.textContent =
            total.toLocaleString('vi-VN') + ' đ';
        selectedCountElement.textContent = selected.length + ' ghế';
        document.getElementById('submit-booking').disabled = selected.length === 0;
        selectedList.replaceChildren();

        if (selected.length === 0) {
            const emptyMessage = document.createElement('span');
            emptyMessage.className = 'text-secondary';
            emptyMessage.textContent = 'Chưa chọn ghế';
            selectedList.append(emptyMessage);
            return;
        }

        selected.forEach(function (checkbox) {
            const seat = document.createElement('span');
            seat.className = 'badge bg-success px-3 py-2';
            seat.textContent = checkbox.closest('.seat-label').querySelector('.seat').textContent.trim();
            selectedList.append(seat);
        });
    }

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const selected = document.querySelectorAll(
                '.seat-checkbox:checked'
            );

            if (selected.length > 10) {
                checkbox.checked = false;
                alert('Mỗi lần chỉ được đặt tối đa 10 ghế.');
            }

            updateTotal();
        });
    });

    updateTotal();
</script>
@endsection