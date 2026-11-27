
@extends('layouts.app')

@section('title', 'Quản lý đơn bắp nước - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="mb-4">
        <h2 class="fw-bold mb-1">QUẢN LÝ ĐƠN HÀNG BẮP NƯỚC</h2>
        <p class="text-secondary mb-0">Theo dõi đơn hàng đồ ăn và thức uống</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" name="q" class="form-control"
                           value="{{ request('q') }}"
                           placeholder="Mã đơn, tên hoặc email khách hàng">
                </div>

                <div class="col-lg-3">
                    <label class="form-label">Trạng thái đơn</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="pending" @selected(request('status') === 'pending')>Chờ xác nhận</option>
                        <option value="confirmed" @selected(request('status') === 'confirmed')>Đã xác nhận</option>
                        <option value="preparing" @selected(request('status') === 'preparing')>Đang chuẩn bị</option>
                        <option value="completed" @selected(request('status') === 'completed')>Hoàn thành</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>Đã hủy</option>
                    </select>
                </div>

                <div class="col-lg-3">
                    <label class="form-label">Thanh toán</label>
                    <select name="payment_status" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="pending" @selected(request('payment_status') === 'pending')>Chưa thanh toán</option>
                        <option value="paid" @selected(request('payment_status') === 'paid')>Đã thanh toán</option>
                        <option value="refunded" @selected(request('payment_status') === 'refunded')>Đã hoàn tiền</option>
                    </select>
                </div>

                <div class="col-lg-2 d-flex gap-2">
                    <button class="btn btn-dark flex-grow-1">Lọc</button>
                    <a href="{{ route('admin.concession-orders.index') }}"
                       class="btn btn-outline-secondary">Xóa lọc</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái đơn</th>
                        <th>Thanh toán</th>
                        <th class="text-end pe-3">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $order->order_code }}</td>
                            <td>
                                @if($order->user)
                                    <div>{{ $order->user->name }}</div>
                                    <small class="text-secondary">{{ $order->user->email }}</small>
                                @else
                                    <span class="text-secondary">Khách vãng lai</span>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="fw-semibold text-nowrap">
                                {{ number_format($order->total_amount, 0, ',', '.') }}đ
                            </td>
                            <td>
                                @php
                                    $statusLabels = [
                                        'pending' => ['Chờ xác nhận', 'warning'],
                                        'confirmed' => ['Đã xác nhận', 'info'],
                                        'preparing' => ['Đang chuẩn bị', 'primary'],
                                        'completed' => ['Hoàn thành', 'success'],
                                        'cancelled' => ['Đã hủy', 'secondary'],
                                    ];
                                    $status = $statusLabels[$order->status] ?? [$order->status, 'secondary'];
                                @endphp
                                <span class="badge text-bg-{{ $status[1] }}">{{ $status[0] }}</span>
                            </td>
                            <td>
                                @if($order->payment_status === 'paid')
                                    <span class="badge bg-success">Đã thanh toán</span>
                                @elseif($order->payment_status === 'refunded')
                                    <span class="badge bg-secondary">Đã hoàn tiền</span>
                                @else
                                    <span class="badge bg-warning text-dark">Chưa thanh toán</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ route('admin.concession-orders.show', $order) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Xem chi tiết
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">
                                Chưa có đơn hàng bắp nước nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="card-footer bg-white">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
