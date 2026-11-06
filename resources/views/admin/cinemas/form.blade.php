@extends('layouts.app')

@section('title', ($cinema->exists ? 'Sửa rạp' : 'Thêm rạp') . ' - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="card bg-dark border-secondary mx-auto" style="max-width: 760px;">
        <div class="card-body p-4">
            <h1 class="h3 fw-bold mb-4">{{ $cinema->exists ? 'Sửa rạp chiếu' : 'Thêm rạp chiếu' }}</h1>
            @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
            <form method="POST" action="{{ $cinema->exists ? route('admin.cinemas.update', $cinema) : route('admin.cinemas.store') }}">
                @csrf
                @if ($cinema->exists) @method('PUT') @endif
                <div class="mb-3"><label class="form-label">Tên rạp *</label><input name="name" class="form-control" value="{{ old('name', $cinema->name) }}" required maxlength="150"></div>
                <div class="mb-3"><label class="form-label">Địa chỉ *</label><input name="address" class="form-control" value="{{ old('address', $cinema->address) }}" required maxlength="255"></div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6"><label class="form-label">Thành phố *</label><input name="city" class="form-control" value="{{ old('city', $cinema->city ?? 'Hà Nội') }}" required maxlength="100"></div>
                    <div class="col-md-6"><label class="form-label">Điện thoại</label><input name="phone" class="form-control" value="{{ old('phone', $cinema->phone) }}" maxlength="30"></div>
                </div>
                <div class="mb-4"><label class="form-label">Trạng thái</label><select name="is_active" class="form-select"><option value="1" @selected((string) old('is_active', (int) ($cinema->is_active ?? true)) === '1')>Hoạt động</option><option value="0" @selected((string) old('is_active', (int) ($cinema->is_active ?? true)) === '0')>Tạm ngưng</option></select></div>
                <button class="btn btn-red">Lưu rạp</button><a href="{{ route('admin.cinemas.index') }}" class="btn btn-outline-light ms-2">Quay lại</a>
            </form>
        </div>
    </div>
</div>
@endsection
