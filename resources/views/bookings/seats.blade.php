@extends('layouts.app')

@section('title', 'Chọn ghế - YUNFLIMS')

@section('content')
<div class="container py-5">
    <h2 class="mb-3">Chọn ghế</h2>

    <div class="card bg-dark text-white border-secondary mb-4">
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

    <div class="text-center mb-4">
        <div class="bg-light text-dark rounded py-2 mx-auto"
             style="max-width:600px;">
            MÀN HÌNH
        </div>
    </div>

    <form method="POST"
          action="{{ route('bookings.store', $showtime) }}">
        @csrf

        <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
            <span><span class="badge bg-secondary">■</span> Ghế trống</span>
            <span><span class="badge bg-danger">■</span> Đã đặt</span>
            <span><span class="badge bg-success">■</span> Đang chọn</span>
            <span><span class="badge bg-dark border">■</span> Khóa / hỏng</span>
        </div>

        @foreach ($seats->groupBy('row_number') as $rowNumber => $rowSeats)
            <div class="d-flex flex-wrap justify-content-center align-items-center gap-2 mb-2">
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

        <div class="text-center">
            <p>
                Tổng tiền:
                <strong id="total">0 đ</strong>
            </p>

            <button type="submit" class="btn btn-danger px-5" id="submit-booking">
                Xác nhận đặt vé
            </button>
        </div>
    </form>
</div>

<style>
    .seat-label { cursor: pointer; }
    .seat-checkbox {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .seat {
        display: inline-flex;
        min-width: 44px;
        height: 38px;
        padding: 0 6px;
        align-items: center;
        justify-content: center;
        background: #555;
        color: white;
        border-radius: 6px;
        font-size: 12px;
    }
    .seat-checkbox:checked + .seat { background: #198754; }
    .seat-label:has(.seat-checkbox:disabled) { cursor: not-allowed; }
    .seat.booked { background: #dc3545; }
    .seat.disabled-seat { background: #25252b; color: #777; border: 1px dashed #666; }
    .seat.vip { outline: 2px solid #ffc107; }
    .seat.couple { min-width: 62px; border-radius: 12px; }
</style>

<script>
    const checkboxes = document.querySelectorAll('.seat-checkbox');
    const totalElement = document.getElementById('total');

    function updateTotal() {
        let total = 0;
        let selectedCount = 0;

        checkboxes.forEach(function (checkbox) {
            if (checkbox.checked) {
                total += Number(checkbox.dataset.price);
                selectedCount++;
            }
        });

        totalElement.textContent =
            total.toLocaleString('vi-VN') + ' đ';
        document.getElementById('submit-booking').disabled = selectedCount === 0;
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