
@extends('layouts.app')

@section('title', 'Thêm sản phẩm - YUNFLIMS')

@section('content')
<div class="container py-4" style="max-width: 900px">
    <div class="mb-4">
        <a href="{{ route('admin.concession-products.index') }}"
           class="text-decoration-none text-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách
        </a>
        <h2 class="fw-bold mt-3">THÊM SẢN PHẨM BẮP NƯỚC</h2>
        <p class="text-secondary">Nhập thông tin sản phẩm mới</p>
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

    <form method="POST" action="{{ route('admin.concession-products.store') }}">
        @csrf

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name') }}" required
                           placeholder="Ví dụ: Bắp rang bơ lớn">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Slug</label>
                    <input type="text" name="slug" class="form-control"
                           value="{{ old('slug') }}"
                           placeholder="Để trống để tự tạo từ tên sản phẩm">
                    <small class="text-secondary">Slug dùng trong đường dẫn sản phẩm.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Mô tả</label>
                    <textarea name="description" rows="4" class="form-control"
                              placeholder="Mô tả sản phẩm...">{{ old('description') }}</textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control"
                               min="0" step="1000" value="{{ old('price', 0) }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Số lượng tồn kho <span class="text-danger">*</span></label>
                        <input type="number" name="stock_quantity" class="form-control"
                               min="0" value="{{ old('stock_quantity', 0) }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Đường dẫn hình ảnh</label>
                    <input type="url" name="image" class="form-control"
                           value="{{ old('image') }}"
                           placeholder="https://example.com/image.jpg">
                    <small class="text-secondary">Hiện tại nhập URL ảnh trực tiếp, chưa tải ảnh lên máy chủ.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Thứ tự hiển thị</label>
                    <input type="number" name="sort_order" class="form-control"
                           min="0" value="{{ old('sort_order', 0) }}">
                    <small class="text-secondary">Số nhỏ hơn sẽ được ưu tiên hiển thị trước.</small>
                </div>

                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" role="switch"
                           name="is_active" value="1" id="is_active"
                           @checked(old('is_active', '1') == '1')>
                    <label class="form-check-label fw-semibold" for="is_active">
                        Cho phép bán sản phẩm
                    </label>
                </div>
            </div>

            <div class="card-footer bg-white p-3 d-flex justify-content-end gap-2">
                <a href="{{ route('admin.concession-products.index') }}"
                   class="btn btn-outline-secondary">Hủy</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Lưu sản phẩm
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
