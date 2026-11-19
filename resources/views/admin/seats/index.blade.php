@extends('layouts.app')

@section('title', 'Sơ đồ ghế - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">Sơ đồ ghế: {{ $room->name }}</h1>
            <p class="text-secondary mb-0">{{ $room->cinema->name }} · {{ $room->seats()->count() }}/{{ $room->total_seats }} ghế</p>
        </div>
        <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-light">Quay lại phòng chiếu</a>
    </div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Thêm ghế</h2>
            <form method="POST" action="{{ route('admin.rooms.seats.store', $room) }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-sm-3 col-md-2"><label class="form-label">Hàng (1–26)</label><input name="row_number" type="number" min="1" max="26" value="{{ old('row_number', 1) }}" class="form-control" required></div>
                <div class="col-sm-3 col-md-2"><label class="form-label">Số ghế</label><input name="seat_number" type="number" min="1" max="999" value="{{ old('seat_number', 1) }}" class="form-control" required></div>
                <div class="col-sm-3 col-md-3"><label class="form-label">Loại ghế</label><select name="seat_type" class="form-select"><option value="standard">Thường</option><option value="vip">VIP</option><option value="couple">Đôi</option></select></div>
                <div class="col-sm-3 col-md-2"><label class="form-label">Trạng thái</label><select name="is_active" class="form-select"><option value="1">Hoạt động</option><option value="0">Tạm ngưng</option></select></div>
                <div class="col-md-3"><button class="btn btn-red w-100">Thêm ghế</button></div>
            </form>
        </div>
    </div>

    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Chỉnh sơ đồ theo hàng / cột</h2>
            <div class="row g-4">
                <form method="POST" action="{{ route('admin.rooms.seats.rows.store', $room) }}" class="col-lg-6 row g-2 align-items-end">
                    @csrf
                    <div class="col-4"><label class="form-label">Hàng</label><input name="row_number" type="number" min="1" max="26" class="form-control" required></div>
                    <div class="col-4"><label class="form-label">Số ghế</label><input name="seat_count" type="number" min="1" max="999" value="10" class="form-control" required></div>
                    <div class="col-4"><label class="form-label">Loại</label><select name="seat_type" class="form-select"><option value="standard">Thường</option><option value="vip">VIP</option><option value="couple">Đôi</option></select></div>
                    <div class="col-12"><button class="btn btn-outline-light w-100">Tạo hàng và đánh số tự động</button></div>
                </form>
                <form method="POST" action="{{ route('admin.rooms.seats.columns.store', $room) }}" class="col-lg-6 row g-2 align-items-end">
                    @csrf
                    <div class="col-4"><label class="form-label">Số cột</label><input name="seat_number" type="number" min="1" max="999" class="form-control" required></div>
                    <div class="col-4"><label class="form-label">Loại ghế</label><select name="seat_type" class="form-select"><option value="standard">Thường</option><option value="vip">VIP</option><option value="couple">Đôi</option></select></div>
                    <div class="col-4"><button class="btn btn-outline-light w-100">Thêm cột</button></div>
                </form>
                <div class="col-12">
                    <span class="form-label d-block">Xóa cột có sẵn:</span>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($rows->flatten()->pluck('seat_number')->unique()->sort() as $columnNumber)
                            <form method="POST" action="{{ route('admin.rooms.seats.columns.destroy', [$room, 'seatNumber' => $columnNumber]) }}" onsubmit="return confirm('Xóa các ghế ở cột số {{ $columnNumber }}? Ghế có lịch sử đặt vé sẽ không thể xóa.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Cột {{ $columnNumber }}</button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
            <p class="small text-secondary mt-3 mb-0">Hàng/cột được đánh số tự động theo vị trí. Hệ thống từ chối vượt sức chứa và không xóa vị trí đã có lịch sử vé.</p>
        </div>
    </div>

    <div class="card bg-dark border-secondary mb-3">
        <div class="card-body text-center">
            <div class="mx-auto border border-light rounded-pill py-2 mb-4" style="max-width: 560px;">MÀN HÌNH</div>
            @forelse ($rows as $rowNumber => $seats)
                <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 mb-3">
                    <span class="text-secondary me-2">{{ chr(64 + (int) $rowNumber) }}</span>
                    @foreach ($seats as $seat)
                        <div class="dropdown">
                            <button class="btn btn-sm {{ !$seat->is_active ? 'btn-secondary' : ($seat->seat_type === 'vip' ? 'btn-warning' : ($seat->seat_type === 'couple' ? 'btn-info' : 'btn-outline-light')) }}"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                    title="{{ $seat->seat_code }} · {{ $seat->seat_type }}{{ $seat->is_active ? '' : ' · Tạm ngưng' }}">
                                {{ $seat->seat_code }}
                            </button>
                            <div class="dropdown-menu dropdown-menu-dark p-3" style="min-width: 270px;">
                                <form method="POST" action="{{ route('admin.rooms.seats.update', [$room, $seat]) }}">
                                    @csrf @method('PUT')
                                    <label class="form-label small">Hàng</label><input type="number" name="row_number" min="1" max="26" value="{{ $seat->row_number }}" class="form-control form-control-sm mb-2" required>
                                    <label class="form-label small">Số ghế</label><input type="number" name="seat_number" min="1" max="999" value="{{ $seat->seat_number }}" class="form-control form-control-sm mb-2" required>
                                    <label class="form-label small">Loại ghế</label><select name="seat_type" class="form-select form-select-sm mb-2"><option value="standard" @selected($seat->seat_type === 'standard')>Thường</option><option value="vip" @selected($seat->seat_type === 'vip')>VIP</option><option value="couple" @selected($seat->seat_type === 'couple')>Đôi</option></select>
                                    <label class="form-label small">Trạng thái</label><select name="is_active" class="form-select form-select-sm mb-3"><option value="1" @selected($seat->is_active)>Hoạt động</option><option value="0" @selected(!$seat->is_active)>Tạm ngưng</option></select>
                                    <button class="btn btn-primary btn-sm w-100">Lưu ghế {{ $seat->seat_code }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.rooms.seats.destroy', [$room, $seat]) }}" class="mt-2" onsubmit="return confirm('Xóa ghế {{ $seat->seat_code }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm w-100" {{ $seat->bookingSeats()->exists() ? 'disabled' : '' }}>Xóa ghế</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                    <form method="POST" action="{{ route('admin.rooms.seats.rows.destroy', [$room, 'rowNumber' => $rowNumber]) }}" onsubmit="return confirm('Xóa toàn bộ hàng {{ chr(64 + (int) $rowNumber) }}?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Xóa hàng</button>
                    </form>
                </div>
            @empty
                <div class="text-secondary py-3">Phòng chưa có ghế. Thêm vị trí ghế ở biểu mẫu phía trên.</div>
            @endforelse
            <div class="d-flex justify-content-center flex-wrap gap-3 small mt-4">
                <span><span class="badge bg-secondary">■</span> Thường</span><span><span class="badge bg-warning">■</span> VIP</span><span><span class="badge bg-info">■</span> Ghế đôi</span><span><span class="badge bg-dark border">■</span> Tạm ngưng</span>
            </div>
        </div>
    </div>
</div>
@endsection
