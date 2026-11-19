@extends('layouts.app')

@section('title', 'Quy tắc giá vé - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="mb-4"><h1 class="h2 fw-bold mb-1">Quy tắc giá vé</h1><p class="text-secondary mb-0">Quy tắc khớp cụ thể nhất trong cùng mức ưu tiên thắng; không cộng dồn quy tắc. Giá được lưu vào từng vé khi đặt.</p></div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <div class="card bg-dark border-secondary mb-4"><div class="card-body">
        <h2 class="h5">Tạo quy tắc</h2>
        <form method="POST" action="{{ route('admin.pricing.store') }}" class="row g-3">@csrf
            <div class="col-md-4"><label class="form-label">Tên</label><input name="name" class="form-control" required maxlength="120"></div>
            <div class="col-md-2"><label class="form-label">Hạng ghế</label><select name="seat_type" class="form-select"><option value="">Mọi hạng</option><option value="standard">Thường</option><option value="vip">VIP</option><option value="couple">Đôi</option></select></div>
            <div class="col-md-2"><label class="form-label">Định dạng</label><select name="format_id" class="form-select"><option value="">Mọi định dạng</option>@foreach ($formats as $format)<option value="{{ $format->id }}">{{ $format->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Ngày</label><select name="day_type" class="form-select"><option value="any">Mọi ngày</option><option value="weekday">Ngày thường</option><option value="weekend">Cuối tuần</option></select></div>
            <div class="col-md-2"><label class="form-label">Ưu tiên</label><input type="number" name="priority" class="form-control" value="0" min="0" max="65535" required></div>
            <div class="col-md-2"><label class="form-label">Từ giờ</label><input type="time" name="time_start" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Đến giờ</label><input type="time" name="time_end" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Kiểu giá</label><select name="amount_type" class="form-select"><option value="surcharge">Cộng vào giá cơ bản</option><option value="fixed">Giá cố định</option></select></div>
            <div class="col-md-2"><label class="form-label">Số tiền (đ)</label><input type="number" name="amount" class="form-control" min="0" step="1000" required></div>
            <div class="col-md-2"><label class="form-label">Trạng thái</label><select name="is_active" class="form-select"><option value="1">Hoạt động</option><option value="0">Tạm ngưng</option></select></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-red w-100">Tạo quy tắc</button></div>
        </form>
    </div></div>

    <div class="card bg-dark border-secondary"><div class="table-responsive"><table class="table table-dark table-hover align-middle mb-0">
        <thead><tr><th>Tên / Ưu tiên</th><th>Điều kiện</th><th>Thời gian</th><th>Giá</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>@forelse ($rules as $rule)
            <tr><td>{{ $rule->name }}<div class="small text-secondary">Ưu tiên {{ $rule->priority }}</div></td><td>{{ $rule->seat_type ?: 'Mọi ghế' }} · {{ $rule->format?->name ?? 'Mọi định dạng' }} · {{ $rule->day_type }}</td><td>{{ $rule->time_start ?: 'Cả ngày' }}{{ $rule->time_end ? '–' . $rule->time_end : '' }}</td><td>{{ $rule->amount_type === 'fixed' ? 'Giá cố định' : 'Phụ thu' }} {{ number_format((float) $rule->amount, 0, ',', '.') }} đ</td><td>{{ $rule->is_active ? 'Hoạt động' : 'Tạm ngưng' }}</td>
                <td class="text-nowrap"><form method="POST" action="{{ route('admin.pricing.update', $rule) }}" class="d-inline-flex gap-2">@csrf @method('PUT')<input type="hidden" name="name" value="{{ $rule->name }}"><input type="hidden" name="seat_type" value="{{ $rule->seat_type }}"><input type="hidden" name="format_id" value="{{ $rule->format_id }}"><input type="hidden" name="day_type" value="{{ $rule->day_type }}"><input type="hidden" name="time_start" value="{{ $rule->time_start ? substr($rule->time_start, 0, 5) : '' }}"><input type="hidden" name="time_end" value="{{ $rule->time_end ? substr($rule->time_end, 0, 5) : '' }}"><input type="hidden" name="amount_type" value="{{ $rule->amount_type }}"><input type="hidden" name="amount" value="{{ $rule->amount }}"><input type="hidden" name="priority" value="{{ $rule->priority }}"><input type="hidden" name="is_active" value="{{ $rule->is_active ? 0 : 1 }}"><button class="btn btn-sm btn-outline-light">{{ $rule->is_active ? 'Tắt' : 'Bật' }}</button></form>
                    <form method="POST" action="{{ route('admin.pricing.destroy', $rule) }}" class="d-inline" onsubmit="return confirm('Xóa quy tắc?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Xóa</button></form></td></tr>
        @empty<tr><td colspan="6" class="text-center text-secondary py-4">Chưa có quy tắc giá.</td></tr>@endforelse</tbody>
    </table></div></div>
    <div class="mt-3">{{ $rules->links() }}</div>
</div>
@endsection
