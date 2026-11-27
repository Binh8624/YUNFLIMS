@extends('layouts.app')

@section('title', 'Quy tắc giá vé - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="mb-4">
        <h1 class="h2 fw-bold mb-1">Quy tắc giá vé</h1>
        <p class="text-secondary mb-0">
            Quy tắc có ưu tiên cao hơn và cụ thể hơn sẽ được áp dụng trước.
            Không cộng dồn quy tắc. Giá được lưu vào từng vé khi đặt.
        </p>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- QUẢN LÝ NGÀY LỄ --}}
    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">
            <h2 class="h5 fw-bold mb-3">Quản lý ngày lễ / Tết</h2>

            <form method="POST" action="{{ route('admin.pricing.holidays.store') }}" class="row g-3 mb-4">
                @csrf

                <div class="col-md-5">
                    <label class="form-label">Tên ngày lễ</label>
                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        maxlength="120"
                        placeholder="Ví dụ: Tết Nguyên Đán"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Ngày</label>
                    <input type="date" name="holiday_date" class="form-control" required>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-red w-100">
                        Thêm ngày lễ
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tên ngày lễ</th>
                            <th>Ngày áp dụng</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($holidays as $holiday)
                            <tr>
                                <td>{{ $holiday->name }}</td>
                                <td>{{ $holiday->holiday_date->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    <form
                                        method="POST"
                                        action="{{ route('admin.pricing.holidays.destroy', $holiday) }}"
                                        onsubmit="return confirm('Xóa ngày lễ này?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-secondary py-3">
                                    Chưa có ngày lễ nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TẠO QUY TẮC GIÁ --}}
    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">
            <h2 class="h5 fw-bold mb-3">Tạo quy tắc giá</h2>

            <form method="POST" action="{{ route('admin.pricing.store') }}" class="row g-3">
                @csrf

                <div class="col-md-4">
                    <label class="form-label">Tên</label>
                    <input name="name" class="form-control" required maxlength="120">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Hạng ghế</label>
                    <select name="seat_type" class="form-select">
                        <option value="">Mọi hạng</option>
                        <option value="standard">Thường</option>
                        <option value="vip">VIP</option>
                        <option value="couple">Đôi</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Định dạng</label>
                    <select name="format_id" class="form-select">
                        <option value="">Mọi định dạng</option>
                        @foreach ($formats as $format)
                            <option value="{{ $format->id }}">{{ $format->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Ngày áp dụng</label>
                    <select name="day_type" class="form-select">
                        <option value="any">Mọi ngày</option>
                        <option value="weekday">Ngày thường</option>
                        <option value="weekend">Cuối tuần</option>
                        <option value="holiday">Ngày lễ / Tết</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Ưu tiên</label>
                    <input type="number" name="priority" class="form-control" value="0" min="0" max="65535" required>
                </div>

                <div class="col-md-2">
    <label class="form-label">Từ giờ (24h)</label>
    <input
        type="text"
        name="time_start"
        class="form-control"
        placeholder="22:00"
        pattern="^([01][0-9]|2[0-3]):[0-5][0-9]$"
        maxlength="5"
    >
</div>

<div class="col-md-2">
    <label class="form-label">Đến giờ (24h)</label>
    <input
        type="text"
        name="time_end"
        class="form-control"
        placeholder="02:00"
        pattern="^([01][0-9]|2[0-3]):[0-5][0-9]$"
        maxlength="5"
    >
</div>

                <div class="col-md-2">
                    <label class="form-label">Kiểu giá</label>
                    <select name="amount_type" class="form-select">
                        <option value="surcharge">Cộng vào giá cơ bản</option>
                        <option value="fixed">Giá cố định</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Số tiền (đ)</label>
                    <input type="number" name="amount" class="form-control" min="0" step="1000" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Trạng thái</label>
                    <select name="is_active" class="form-select">
                        <option value="1">Hoạt động</option>
                        <option value="0">Tạm ngưng</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-red w-100">
                        Tạo quy tắc
                    </button>
                </div>
            </form>

            <div class="small text-secondary mt-3">
                Có thể nhập khung giờ qua đêm, ví dụ từ 22:00 đến 02:00.
                Để trống cả hai ô giờ nếu áp dụng cả ngày.
            </div>
        </div>
    </div>

    {{-- DANH SÁCH QUY TẮC --}}
    <div class="card bg-dark border-secondary">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tên / Ưu tiên</th>
                        <th>Điều kiện</th>
                        <th>Thời gian</th>
                        <th>Giá</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td>
                                {{ $rule->name }}
                                <div class="small text-secondary">
                                    Ưu tiên {{ $rule->priority }}
                                </div>
                            </td>

                            <td>
                                {{ $rule->seat_type ?: 'Mọi ghế' }}
                                · {{ $rule->format?->name ?? 'Mọi định dạng' }}
                                ·
                                @switch($rule->day_type)
                                    @case('weekday') Ngày thường @break
                                    @case('weekend') Cuối tuần @break
                                    @case('holiday') Ngày lễ / Tết @break
                                    @default Mọi ngày
                                @endswitch
                            </td>

                            <td>
                                {{ $rule->time_start ? substr($rule->time_start, 0, 5) : 'Cả ngày' }}
                                {{ $rule->time_end ? '–' . substr($rule->time_end, 0, 5) : '' }}
                            </td>

                            <td>
                                {{ $rule->amount_type === 'fixed' ? 'Giá cố định' : 'Phụ thu' }}
                                {{ number_format((float) $rule->amount, 0, ',', '.') }} đ
                            </td>

                            <td>
                                {{ $rule->is_active ? 'Hoạt động' : 'Tạm ngưng' }}
                            </td>

                            <td class="text-nowrap">
                                <form
                                    method="POST"
                                    action="{{ route('admin.pricing.update', $rule) }}"
                                    class="d-inline-flex gap-2"
                                >
                                    @csrf
                                    @method('PUT')

                                    <input type="hidden" name="name" value="{{ $rule->name }}">
                                    <input type="hidden" name="seat_type" value="{{ $rule->seat_type }}">
                                    <input type="hidden" name="format_id" value="{{ $rule->format_id }}">
                                    <input type="hidden" name="day_type" value="{{ $rule->day_type }}">
                                    <input type="hidden" name="time_start" value="{{ $rule->time_start ? substr($rule->time_start, 0, 5) : '' }}">
                                    <input type="hidden" name="time_end" value="{{ $rule->time_end ? substr($rule->time_end, 0, 5) : '' }}">
                                    <input type="hidden" name="amount_type" value="{{ $rule->amount_type }}">
                                    <input type="hidden" name="amount" value="{{ $rule->amount }}">
                                    <input type="hidden" name="priority" value="{{ $rule->priority }}">
                                    <input type="hidden" name="is_active" value="{{ $rule->is_active ? 0 : 1 }}">

                                    <button type="submit" class="btn btn-sm btn-outline-light">
                                        {{ $rule->is_active ? 'Tắt' : 'Bật' }}
                                    </button>
                                </form>

                                <form
                                    method="POST"
                                    action="{{ route('admin.pricing.destroy', $rule) }}"
                                    class="d-inline"
                                    onsubmit="return confirm('Xóa quy tắc?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        Xóa
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">
                                Chưa có quy tắc giá.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $rules->links() }}</div>
</div>
@endsection