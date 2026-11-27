
@extends('layouts.app')

@section('title', 'Chọn bắp nước - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="mb-4">
        <a href="{{ route('bookings.seats', $booking->showtime_id) }}"
           class="text-decoration-none text-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>

        <h2 class="fw-bold mt-3 mb-1">BẮP NƯỚC</h2>
        <p class="text-secondary mb-0">
            Chọn đồ ăn và thức uống để thanh toán cùng vé xem phim.
        </p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="fw-bold">{{ $booking->showtime->movie->title }}</div>

            <div class="text-secondary small">
                Mã đặt vé: {{ $booking->booking_code }}
            </div>

            <div class="text-secondary small">
                Ghế:
                {{ $booking->bookingSeats->map(fn($item) => $item->seat->seat_code ?? ($item->seat->row_number . $item->seat->seat_number))->implode(', ') }}
            </div>

            <div class="mt-2">
                <span class="text-secondary">Tiền vé:</span>
                <strong>
                    {{ number_format($booking->bookingSeats->sum('price'), 0, ',', '.') }}đ
                </strong>
            </div>
        </div>
    </div>

    <form method="POST"
          action="{{ route('bookings.concessions.save', $booking) }}">
        @csrf

        @if($products->isEmpty())
            <div class="alert alert-secondary">
                Hiện chưa có sản phẩm bắp nước. Bạn có thể tiếp tục thanh toán vé.
            </div>
        @else
            <div class="row g-4">
                @foreach($products as $product)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm">
                            @if($product->image)
                                <img src="{{ $product->image }}"
                                     class="card-img-top"
                                     alt="{{ $product->name }}"
                                     style="height: 170px; object-fit: cover;">
                            @else
                                <div class="d-flex align-items-center justify-content-center bg-dark text-white"
                                     style="height: 170px;">
                                    <i class="bi bi-cup-straw fs-1"></i>
                                </div>
                            @endif

                            <div class="card-body d-flex flex-column">
                                <h6 class="fw-bold">{{ $product->name }}</h6>

                                @if($product->description)
                                    <p class="small text-secondary">
                                        {{ $product->description }}
                                    </p>
                                @endif

                                <div class="fw-bold text-primary mb-2">
                                    {{ number_format($product->price, 0, ',', '.') }}đ
                                </div>

                                <div class="small text-secondary mb-2">
                                    Còn {{ $product->stock_quantity }} sản phẩm
                                </div>

                                <label class="form-label small mt-auto">
                                    Số lượng
                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    name="items[{{ $product->id }}]"
                                    min="0"
                                    max="{{ min(10, $product->stock_quantity) }}"
                                    value="{{ old('items.' . $product->id, $selectedItems[$product->id] ?? 0) }}"
                                    {{ $product->stock_quantity < 1 ? 'disabled' : '' }}
                                >
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="fw-bold">Hoàn tất lựa chọn</div>
                    <small class="text-secondary">
                        Bạn có thể bỏ qua nếu không muốn mua bắp nước.
                    </small>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('bookings.payment', $booking) }}"
                       class="btn btn-outline-secondary">
                        Bỏ qua
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Tiếp tục thanh toán
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
