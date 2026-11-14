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
                Phòng: {{ $showtime->room->name }} ({{ $showtime->room->format?->name }})
            </p>
            <p class="mb-0">
                Suất chiếu: {{ $showtime->starts_at->format('d/m/Y H:i') }}
            </p>
            <p class="mb-1">Định dạng: {{ $showtime->format?->name ?? 'Chưa thiết lập' }}</p>
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

        <div class="d-flex flex-wrap justify-content-center gap-3 mb-4 seat-legend">
            <span><span class="badge bg-secondary">■</span> Ghế thường</span>
            <span><span class="badge bg-warning text-dark">■</span> Ghế VIP</span>
            <span><span class="badge bg-info text-dark">■</span> Ghế đôi</span>
            <span><span class="badge bg-danger">■</span> Đã đặt</span>
            <span><span class="badge bg-warning text-dark">■</span> Đang được giữ</span>
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

        <div id="seat-map" class="seat-map-scroll" aria-label="Sơ đồ ghế, có thể cuộn ngang trên màn hình nhỏ">
            <div class="seat-map">
                @foreach ($seats->groupBy('row_number') as $rowNumber => $rowSeats)
                    <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2 mb-2 seat-row">
                <span class="text-secondary me-2 seat-row-label">{{ chr(64 + (int) $rowNumber) }}</span>
                @php
                    $previousSeatNumber = null;
                @endphp
                @foreach ($rowSeats as $seat)
                    @php
                        $booked = in_array($seat->id, $bookedSeatIds);
                        $held = in_array($seat->id, $heldSeatIds);
                        $status = ! $seat->is_active ? 'inactive' : ($booked ? 'booked' : ($held ? 'held' : 'available'));
                        $seatLabel = $seat->seat_code ?: (chr(64 + (int) $seat->row_number).$seat->seat_number);
                        $seatGap = $previousSeatNumber === null ? 0 : max(0, $seat->seat_number - $previousSeatNumber - 1);
                    @endphp

                    <label class="seat-label" style="--seat-gap: {{ $seatGap }}" title="Hàng {{ chr(64 + (int) $seat->row_number) }}, ghế {{ $seat->seat_number }} · {{ $seat->seat_type }} · {{ number_format($priceMap[$seat->id]) }} đ">
                        <input
                            type="checkbox"
                            name="seats[]"
                            value="{{ $seat->id }}"
                            class="seat-checkbox"
                            data-price="{{ $priceMap[$seat->id] }}"
                            data-row="{{ $seat->row_number }}"
                            data-position="{{ $seat->seat_number }}"
                            data-seat-type="{{ $seat->seat_type }}"
                            data-seat-label="{{ $seatLabel }}"
                            data-status="{{ $status }}"
                            aria-label="Hàng {{ chr(64 + (int) $seat->row_number) }}, ghế {{ $seat->seat_number }}, {{ $seat->seat_type }}, {{ $status }}, {{ number_format($priceMap[$seat->id]) }} đồng"
                            {{ in_array($status, ['booked', 'inactive']) ? 'disabled' : '' }}
                            {{ in_array($seat->id, old('seats', [])) ? 'checked' : '' }}
                        >

                        <span class="seat {{ $status }} {{ $seat->seat_type }}">
                            {{ $seatLabel }}
                        </span>
                    </label>
                    @php($previousSeatNumber = $seat->seat_number)
                @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <div id="selection-feedback" class="alert d-none" role="status" aria-live="polite"></div>
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
    const feedbackElement = document.getElementById('selection-feedback');
    const bookingForm = document.querySelector('form[action="{{ route('bookings.store', $showtime) }}"]');
    const submitButton = document.getElementById('submit-booking');
    const csrfToken = bookingForm.querySelector('[name="_token"]').value;
    const availabilityUrl = @json(route('bookings.seats.check', $showtime));
    let acceptedSelection = [];
    let checkingSelection = false;

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
        submitButton.disabled = selected.length === 0 || checkingSelection;
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
            seat.textContent = checkbox.dataset.seatLabel;
            selectedList.append(seat);
        });
    }

    function showFeedback(message) {
        feedbackElement.replaceChildren();
        feedbackElement.className = 'alert alert-warning';
        const text = document.createElement('span');
        text.textContent = message;
        feedbackElement.append(text);

        feedbackElement.classList.remove('d-none');
    }

    function clearFeedback() {
        feedbackElement.replaceChildren();
        feedbackElement.className = 'alert d-none';
    }

    function setSelection(seatIds) {
        const selected = new Set(seatIds.map(Number));
        checkboxes.forEach((checkbox) => {
            checkbox.checked = selected.has(Number(checkbox.value))
                && !['booked', 'inactive'].includes(checkbox.dataset.status);
        });
        updateTotal();
    }

    function updateUnavailableSeats(unavailableSeats) {
        Object.entries(unavailableSeats || {}).forEach(([seatId, status]) => {
            const checkbox = document.querySelector(`.seat-checkbox[value="${seatId}"]`);
            if (!checkbox) return;
            checkbox.checked = false;
            checkbox.disabled = status === 'booked';
            checkbox.dataset.status = status;
            checkbox.nextElementSibling.classList.remove('available', 'held', 'booked');
            checkbox.nextElementSibling.classList.add(status);
        });
    }

    async function validateSelection(seatIds) {
        const normalizedSeatIds = [...new Set(seatIds.map(Number))];
        if (normalizedSeatIds.length > 10) {
            setSelection(acceptedSelection);
            showFeedback('Mỗi lần chỉ được đặt tối đa 10 ghế.');
            return false;
        }

        checkingSelection = true;
        document.getElementById('seat-map').setAttribute('aria-busy', 'true');
        updateTotal();

        try {
            const response = await fetch(availabilityUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ seats: normalizedSeatIds }),
            });
            const result = await response.json();

            if (!response.ok) {
                const message = Object.values(result.errors || {}).flat()[0]
                    || 'Không thể kiểm tra ghế lúc này. Vui lòng thử lại.';
                throw new Error(message);
            }

            updateUnavailableSeats(result.unavailableSeats);
            if (!result.valid) {
                const unavailableIds = Object.keys(result.unavailableSeats || {}).map(Number);
                acceptedSelection = acceptedSelection.filter((seatId) => !unavailableIds.includes(seatId));
                setSelection(acceptedSelection);
                showFeedback(result.message || 'Không thể chọn các vị trí này.');
                return false;
            }

            normalizedSeatIds.forEach((seatId) => {
                const checkbox = document.querySelector(`.seat-checkbox[value="${seatId}"]`);
                if (!checkbox) return;
                checkbox.dataset.status = 'available';
                checkbox.nextElementSibling.classList.remove('held');
                checkbox.nextElementSibling.classList.add('available');
            });
            acceptedSelection = normalizedSeatIds;
            setSelection(acceptedSelection);
            clearFeedback();
            return true;
        } catch (error) {
            setSelection(acceptedSelection);
            showFeedback(error.message || 'Không thể kiểm tra ghế lúc này. Vui lòng thử lại.');
            return false;
        } finally {
            checkingSelection = false;
            document.getElementById('seat-map').removeAttribute('aria-busy');
            updateTotal();
        }
    }

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', function () {
            if (checkingSelection) {
                setSelection(acceptedSelection);
                return;
            }

            validateSelection(Array.from(document.querySelectorAll('.seat-checkbox:checked'))
                .map((seat) => Number(seat.value)));
        });
    });

    bookingForm.addEventListener('submit', (event) => {
        if (checkingSelection || acceptedSelection.length === 0) {
            event.preventDefault();
            return;
        }
        setSelection(acceptedSelection);
    });

    const initialSelection = Array.from(document.querySelectorAll('.seat-checkbox:checked'))
        .map((seat) => Number(seat.value));
    setSelection([]);
    if (initialSelection.length) validateSelection(initialSelection);
    else updateTotal();
</script>
@endsection