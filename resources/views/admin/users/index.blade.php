@extends('layouts.app')

@section('title', 'Quản lý người dùng - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="mb-4"><h1 class="h2 fw-bold mb-1">Người dùng</h1><p class="text-secondary mb-0">Quản lý quyền truy cập và trạng thái tài khoản.</p></div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    @can('manage-users')
    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">
            <h2 class="h5">Tạo tài khoản nhân viên</h2>
            <form method="POST" action="{{ route('admin.users.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><input name="name" class="form-control" placeholder="Họ tên" required></div>
                <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                <div class="col-md-2"><input type="password" name="password" class="form-control" placeholder="Mật khẩu" required></div>
                <div class="col-md-2"><input type="password" name="password_confirmation" class="form-control" placeholder="Nhập lại mật khẩu" required></div>
                <div class="col-md-2">
                    <select name="role" class="form-select" required>
                        @foreach (App\Models\User::roleOptions() as $roleValue => $roleLabel)
                            @if ($roleValue !== App\Models\User::ROLE_CUSTOMER)
                                <option value="{{ $roleValue }}">{{ $roleLabel }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="cinema_id" class="form-select">
                        <option value="">Rạp (bắt buộc với vai trò chi nhánh)</option>
                        @foreach (\App\Models\Cinema::orderBy('name')->get() as $cinema)
                            <option value="{{ $cinema->id }}">{{ $cinema->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto"><button class="btn btn-primary">Tạo tài khoản</button></div>
            </form>
        </div>
    </div>
    @endcan
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Tên hoặc email"></div>
        <div class="col-md-2"><select name="role" class="form-select"><option value="">Mọi quyền</option>@foreach (App\Models\User::roleOptions() as $roleValue => $roleLabel)<option value="{{ $roleValue }}" @selected(request('role') === $roleValue)>{{ $roleLabel }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="is_active" class="form-select"><option value="">Mọi trạng thái</option><option value="1" @selected(request('is_active') === '1')>Hoạt động</option><option value="0" @selected(request('is_active') === '0')>Đã khóa</option></select></div>
        <div class="col-auto"><button class="btn btn-outline-light">Lọc</button></div>
    </form>
    <div class="card bg-dark border-secondary"><div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0"><thead><tr><th>Người dùng</th><th>Ngày tham gia</th><th>Đơn / đã chi</th><th>Quyền</th><th>Trạng thái</th><th>Cập nhật quyền</th><th>Tài khoản</th></tr></thead>
            <tbody>@forelse ($users as $user)
                <tr><td><a href="{{ route('admin.users.show', $user) }}" class="link-light text-decoration-none">{{ $user->name }}</a><div class="small text-secondary">{{ $user->email }}</div></td><td>{{ $user->created_at->format('d/m/Y') }}</td><td>{{ $user->bookings_count }}<div class="small text-secondary">{{ number_format((float) ($user->paid_total ?? 0), 0, ',', '.') }} đ</div></td><td>{{ $user->role_label }}</td><td><span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">{{ $user->is_active ? 'Hoạt động' : 'Đã khóa' }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('admin.users.role', $user) }}" class="d-flex gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="role" class="form-select form-select-sm">
                                @foreach (collect(App\Models\User::roleOptions())->except(App\Models\User::ROLE_CUSTOMER) as $roleValue => $roleLabel)
                                    <option value="{{ $roleValue }}" @selected(App\Models\User::normalizeRole($user->role) === $roleValue)>
                                        {{ $roleLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="cinema_id" class="form-select form-select-sm">
                                <option value="">Không gán rạp</option>
                                @foreach (\App\Models\Cinema::orderBy('name')->get() as $cinema)
                                    <option value="{{ $cinema->id }}" @selected((int) $user->cinema_id === $cinema->id)>
                                        {{ $cinema->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-primary">Lưu</button>
                        </form>
                    </td>
                    <td><form method="POST" action="{{ route('admin.users.active', $user) }}" onsubmit="return confirm('{{ $user->is_active ? 'Khóa' : 'Mở khóa' }} tài khoản này?')">@csrf @method('PATCH')<button class="btn btn-sm {{ $user->is_active ? 'btn-danger' : 'btn-success' }}" {{ auth()->id() === $user->id ? 'disabled' : '' }}>{{ $user->is_active ? 'Khóa' : 'Mở khóa' }}</button></form></td></tr>
            @empty<tr><td colspan="7" class="text-center text-secondary py-4">Không có người dùng phù hợp.</td></tr>@endforelse</tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $users->links() }}</div>
</div>
@endsection
