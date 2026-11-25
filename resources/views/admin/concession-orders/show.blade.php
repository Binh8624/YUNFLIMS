
@extends('layouts.app')

@section('title', 'Chi tiết đơn bắp nước - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="mb-4">
        <a href="{{ route('admin.concession-orders.index') }}"
           class="text-decoration-none text-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách
        </a>

        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
            <div>
                <h2 class="fw-bold mb-1">CHI TIẾT ĐƠN HÀNG</h2>
                <p class="text-secondary mb-0">Mã đơn: {{ $concessionOrder->order_code }}</p>
            </div>
            <span class="badge bg-dark fs-6">
                {{ number_format($concessionOrder->total_amount, 0, ',', '.') }}đ
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
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

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold py-3">SẢN PHẨM TRONG ĐƠN</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Sản phẩm</th>
                                <th>Đơn giá</th>
                                <th>Số lượng</th>
                                <th class="text-end pe-3">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($concessionOrder->items as $item)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold">{{ $item->product_name }}</div>
                                        @if(!$item->product)
                                            <small class="text-secondary">Sản phẩm hiện không còn trong danh mục</small>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        {{ number_format($item->unit_price, 0, ',', '.') }}đ
                                    </td>
                                    <td>{{ $item->quantity }}</td>
                                    <td class="text-end pe-3 fw-semibold text-nowrap">
                                        {{ number_format($item->subtotal, 0, ',', '.') }}đ
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Tạm tính</span>
                        <span>{{ number_format($concessionOrder->subtotal, 0, ',', '.') }}đ</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Giảm giá</span>
                        <span>-{{ number_format($concessionOrder->discount_amount, 0, ',', '.') }}đ</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Tổng thanh toán</span>
                        <span class="text-primary">
                            {{ number_format($concessionOrder->total_amount, 0, ',', '.') }}đ
                        </span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3">THÔNG TIN ĐƠN HÀNG</div>
                <div class="card-body">
                    <p><strong>Ngày tạo:</strong> {{ $concessionOrder->created_at->format('d/m/Y H:i:s') }}</p>
                    <p><strong>Phương thức thanh toán:</strong> {{ $concessionOrder->payment_method ?: 'Chưa xác định' }}</p>
                    <p><strong>Mã tham chiếu:</strong> {{ $concessionOrder->payment_reference ?: '—' }}</p>
                    <p class="mb-0"><strong>Ghi chú:</strong> {{ $concessionOrder->note ?: 'Không có' }}</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold py-3">KHÁCH HÀNG</div>
                <div class="card-body">
                    <p><strong>Họ tên:</strong> {{ $concessionOrder->user->name ?? 'Khách vãng lai' }}</p>
                    <p><strong>Email:</strong> {{ $concessionOrder->user->email ?? '—' }}</p>
                    <p class="mb-0"><strong>ID khách hàng:</strong> {{ $concessionOrder->user_id ?? '—' }}</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold py-3">CẬP NHẬT TRẠNG THÁI ĐƠN</div>
                <div class="card-body">
                    <p class="mb-3">
                        Trạng thái hiện tại:
                        <strong>{{ $concessionOrder->status }}</strong>
                    </p>

                    @if($concessionOrder->status === 'pending')
                        <form method="POST" action="{{ route('admin.concession-orders.update-status', $concessionOrder) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="confirmed">
                            <button class="btn btn-primary w-100 mb-2"
                                    onclick="return confirm('Xác nhận đơn và trừ tồn kho sản phẩm?')">
                                Xác nhận đơn hàng
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.concession-orders.update-status', $concessionOrder) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <button class="btn btn-outline-danger w-100" disabled
                                    title="Đơn chờ xác nhận chưa được xử lý theo luồng hủy này">
                                Hủy đơn
                            </button>
                        </form>
                    @elseif($concessionOrder->status === 'confirmed')
                        <form method="POST" action="{{ route('admin.concession-orders.update-status', $concessionOrder) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="preparing">
                            <button class="btn btn-primary w-100 mb-2">Bắt đầu chuẩn bị</button>
                        </form>

                        <form method="POST" action="{{ route('admin.concession-orders.update-status', $concessionOrder) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <button class="btn btn-outline-danger w-100"
                                    onclick="return confirm('Hủy đơn và hoàn lại tồn kho?')">
                                Hủy đơn
                            </button>
                        </form>
                    @elseif($concessionOrder->status === 'preparing')
                        <form method="POST" action="{{ route('admin.concession-orders.update-status', $concessionOrder) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="completed">
                            <button class="btn btn-success w-100 mb-2">Hoàn thành đơn</button>
                        </form>

                        <form method="POST" action="{{ route('admin.concession-orders.update-status', $concessionOrder) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <button class="btn btn-outline-danger w-100"
                                    onclick="return confirm('Hủy đơn và hoàn lại tồn kho?')">
                                Hủy đơn
                            </button>
                        </form>
                    @else
                        <p class="text-secondary mb-0">Đơn hàng đã kết thúc, không thể thay đổi trạng thái.</p>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3">THANH TOÁN</div>
                <div class="card-body">
                    <p>
                        Trạng thái:
                        <strong>{{ $concessionOrder->payment_status }}</strong>
                    </p>

                    @if($concessionOrder->status !== 'cancelled' && $concessionOrder->payment_status !== 'refunded')
                        <form method="POST"
                              action="{{ route('admin.concession-orders.update-payment', $concessionOrder) }}">
                            @csrf
                            @method('PATCH')

                            <label class="form-label">Cập nhật thanh toán</label>
                            <select name="payment_status" class="form-select mb-3" required>
                                <option value="pending" @selected($concessionOrder->payment_status === 'pending')>
                                    Chưa thanh toán
                                </option>
                                <option value="paid" @selected($concessionOrder->payment_status === 'paid')>
                                    Đã thanh toán
                                </option>
                                @if($concessionOrder->payment_status === 'paid')
                                    <option value="refunded">Đã hoàn tiền</option>
                                @endif
                            </select>

                            <label class="form-label">Mã tham chiếu thanh toán</label>
                            <input type="text" name="payment_reference" class="form-control mb-3"
                                   value="{{ $concessionOrder->payment_reference }}">

                            <button class="btn btn-outline-primary w-100"
                                    onclick="return confirm('Cập nhật trạng thái thanh toán?')">
                                Lưu thanh toán
                            </button>
                        </form>
                    @else
                        <p class="text-secondary mb-0">Không thể cập nhật thanh toán cho đơn này.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
