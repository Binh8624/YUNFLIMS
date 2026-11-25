
@extends('layouts.app')

@section('title', 'Sửa sản phẩm - YUNFLIMS')

@section('content')
<div class="container py-4" style="max-width: 900px">
    <div class="mb-4">
        <a href="{{ route('admin.concession-products.index') }}"
           class="text-decoration-none text-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách
        </a>
        <h2 class="fw-bold mt-3">CHỈNH SỬA SẢN PHẨM</h2>
        <p class="text-secondary">Cập nhật thông tin: {{ $concessionProduct->name }}</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('admin.concession-products.update', $concessionProduct) }}">
        @csrf
        @method('PUT')

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name', $concessionProduct->name) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Slug</label>
                    <input type="text" name="slug" class="form-control"
                           value="{{ old('slug', $concessionProduct->slug) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Mô tả</label>
                    <textarea name="description" rows="4" class="form-control">{{ old('description', $concessionProduct->description) }}</textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control"
                               min="0" step="1000"
                               value="{{ old('price', $concessionProduct->price) }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Số lượng tồn kho <span class="text-danger">*</span></label>
                        <input type="number" name="stock_quantity" class="form-control"
                               min="0"
                               value="{{ old('stock_quantity', $concessionProduct->stock_quantity) }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Đường dẫn hình ảnh</label>
                    <input type="url" name="image" class="form-control"
                           value="{{ old('image', $concessionProduct->image) }}"
                           placeholder="https://example.com/image.jpg">

                    @if($concessionProduct->image)
                        <div class="mt-2">
                            <img src="{{ $concessionProduct->image }}"
                                 alt="{{ $concessionProduct->name }}"
                                 style="width:120px;height:90px;object-fit:cover;border-radius:8px;">
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Thứ tự hiển thị</label>
                    <input type="number" name="sort_order" class="form-control"
                           min="0"
                           value="{{ old('sort_order', $concessionProduct->sort_order) }}">
                </div>

                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" role="switch"
                           name="is_active" value="1" id="is_active"
                           @checked(old('is_active', $concessionProduct->is_active ? '1' : '0') == '1')>
                    <label class="form-check-label fw-semibold" for="is_active">
                        Cho phép bán sản phẩm
                    </label>
                </div>
            </div>

            <div class="card-footer bg-white p-3 d-flex justify-content-end gap-2">
                <a href="{{ route('admin.concession-products.index') }}"
                   class="btn btn-outline-secondary">Hủy</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Cập nhật
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
