
@extends('layouts.app')

@section('title', 'Quản lý sản phẩm bắp nước - YUNFLIMS')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">QUẢN LÝ SẢN PHẨM BẮP NƯỚC</h2>
            <p class="text-secondary mb-0">Quản lý đồ ăn, thức uống và tồn kho</p>
        </div>
        <a href="{{ route('admin.concession-products.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Thêm sản phẩm
        </a>
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

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.concession-products.index') }}"
                  class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Tìm kiếm sản phẩm</label>
                    <input type="text" name="q" class="form-control"
                           value="{{ request('q') }}"
                           placeholder="Nhập tên hoặc slug sản phẩm...">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="active" @selected(request('status') === 'active')>Đang bán</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Ngừng bán</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-dark flex-grow-1" type="submit">
                        Tìm kiếm
                    </button>
                    <a href="{{ route('admin.concession-products.index') }}"
                       class="btn btn-outline-secondary">Đặt lại</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Sản phẩm</th>
                            <th>Giá bán</th>
                            <th>Tồn kho</th>
                            <th>Thứ tự</th>
                            <th>Trạng thái</th>
                            <th class="text-end pe-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-3">
                                        @if($product->image)
                                            <img src="{{ $product->image }}" alt="{{ $product->name }}"
                                                 style="width:64px;height:54px;object-fit:cover;border-radius:8px;">
                                        @else
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                                 style="width:64px;height:54px;">
                                                <i class="bi bi-image text-secondary fs-4"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $product->name }}</div>
                                            <small class="text-secondary">{{ $product->slug }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-semibold text-nowrap">
                                    {{ number_format($product->price, 0, ',', '.') }}đ
                                </td>
                                <td>
                                    @if($product->stock_quantity <= 5)
                                        <span class="text-danger fw-semibold">{{ $product->stock_quantity }}</span>
                                    @else
                                        {{ $product->stock_quantity }}
                                    @endif
                                </td>
                                <td>{{ $product->sort_order }}</td>
                                <td>
                                    @if($product->is_active)
                                        <span class="badge bg-success">Đang bán</span>
                                    @else
                                        <span class="badge bg-secondary">Ngừng bán</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.concession-products.edit', $product) }}"
                                           class="btn btn-sm btn-outline-primary" title="Sửa">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <form method="POST"
                                              action="{{ route('admin.concession-products.toggle-status', $product) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-warning"
                                                    title="{{ $product->is_active ? 'Ngừng bán' : 'Mở bán' }}">
                                                <i class="bi bi-power"></i>
                                            </button>
                                        </form>

                                        <form method="POST"
                                              action="{{ route('admin.concession-products.destroy', $product) }}"
                                              onsubmit="return confirm('Bạn có chắc muốn xóa hoặc ẩn sản phẩm này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-5">
                                    Chưa có sản phẩm nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($products->hasPages())
            <div class="card-footer bg-white">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
