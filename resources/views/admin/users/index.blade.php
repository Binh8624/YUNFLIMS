@extends('layouts.app')

@section('title', 'Quản lý người dùng - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="mb-4"><h1 class="h2 fw-bold mb-1">Người dùng</h1><p class="text-secondary mb-0">Quản lý quyền truy cập và trạng thái tài khoản.</p></div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Tên hoặc email"></div>
        <div class="col-md-2"><select name="role" class="form-select"><option value="">Mọi quyền</option><option value="customer" @selected(request('role') === 'customer')>Khách hàng</option><option value="admin" @selected(request('role') === 'admin')>Admin</option></select></div>
        <div class="col-md-2"><select name="is_active" class="form-select"><option value="">Mọi trạng thái</option><option value="1" @selected(request('is_active') === '1')>Hoạt động</option><option value="0" @selected(request('is_active') === '0')>Đã khóa</option></select></div>
        <div class="col-auto"><button class="btn btn-outline-light">Lọc</button></div>
    </form>
    <div class="card bg-dark border-secondary"><div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0"><thead><tr><th>Người dùng</th><th>Ngày tham gia</th><th>Đơn</th><th>Quyền</th><th>Trạng thái</th><th>Cập nhật quyền</th><th>Tài khoản</th></tr></thead>
            <tbody>@forelse ($users as $user)
                <tr><td>{{ $user->name }}<div class="small text-secondary">{{ $user->email }}</div></td><td>{{ $user->created_at->format('d/m/Y') }}</td><td>{{ $user->bookings_count }}</td><td>{{ $user->role }}</td><td><span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">{{ $user->is_active ? 'Hoạt động' : 'Đã khóa' }}</span></td>
                    <td><form method="POST" action="{{ route('admin.users.role', $user) }}" class="d-flex gap-2">@csrf @method('PATCH')<select name="role" class="form-select form-select-sm"><option value="customer" @selected($user->role === 'customer')>Khách hàng</option><option value="admin" @selected($user->role === 'admin')>Admin</option></select><button class="btn btn-sm btn-primary">Lưu</button></form></td>
                    <td><form method="POST" action="{{ route('admin.users.active', $user) }}" onsubmit="return confirm('{{ $user->is_active ? 'Khóa' : 'Mở khóa' }} tài khoản này?')">@csrf @method('PATCH')<button class="btn btn-sm {{ $user->is_active ? 'btn-danger' : 'btn-success' }}" {{ auth()->id() === $user->id ? 'disabled' : '' }}>{{ $user->is_active ? 'Khóa' : 'Mở khóa' }}</button></form></td></tr>
            @empty<tr><td colspan="7" class="text-center text-secondary py-4">Không có người dùng phù hợp.</td></tr>@endforelse</tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $users->links() }}</div>
</div>
@endsection
