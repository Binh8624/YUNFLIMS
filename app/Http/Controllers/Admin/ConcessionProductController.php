<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConcessionProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ConcessionProductController extends Controller
{
    public function index(Request $request)
    {
        $query = ConcessionProduct::query();

        if ($request->filled('q')) {
            $keyword = $request->q;

            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('slug', 'like', "%{$keyword}%");
            });
        }

        if ($request->status === 'active') {
            $query->where('is_active', true);
        } elseif ($request->status === 'inactive') {
            $query->where('is_active', false);
        }

        $products = $query
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.concession-products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.concession-products.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:concession_products,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'image' => ['nullable', 'url', 'max:2048'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['slug'] = $this->makeUniqueSlug($data['slug'] ?: $data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        ConcessionProduct::create($data);

        return redirect()
            ->route('admin.concession-products.index')
            ->with('success', 'Thêm sản phẩm thành công.');
    }

    public function edit(ConcessionProduct $concessionProduct)
    {
        return view('admin.concession-products.edit', compact('concessionProduct'));
    }

    public function update(Request $request, ConcessionProduct $concessionProduct)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('concession_products', 'slug')->ignore($concessionProduct->id),
            ],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'image' => ['nullable', 'url', 'max:2048'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['slug'] = $this->makeUniqueSlug(
            $data['slug'] ?: $data['name'],
            $concessionProduct->id
        );

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        $concessionProduct->update($data);

        return redirect()
            ->route('admin.concession-products.index')
            ->with('success', 'Cập nhật sản phẩm thành công.');
    }

    public function destroy(ConcessionProduct $concessionProduct)
    {
        // Giữ lại sản phẩm đã xuất hiện trong đơn hàng để bảo toàn lịch sử.
        if ($concessionProduct->orderItems()->exists()) {
            $concessionProduct->update(['is_active' => false]);

            return redirect()
                ->route('admin.concession-products.index')
                ->with('success', 'Sản phẩm đã có trong đơn hàng nên được ẩn thay vì xóa để giữ lịch sử.');
        }

        $concessionProduct->delete();

        return redirect()
            ->route('admin.concession-products.index')
            ->with('success', 'Xóa sản phẩm thành công.');
    }

    public function toggleStatus(ConcessionProduct $concessionProduct)
    {
        $concessionProduct->update([
            'is_active' => !$concessionProduct->is_active,
        ]);

        return back()->with('success', 'Đã cập nhật trạng thái sản phẩm.');
    }

    private function makeUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value);

        if ($baseSlug === '') {
            $baseSlug = 'san-pham';
        }

        $slug = $baseSlug;
        $number = 1;

        while (
            ConcessionProduct::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $number;
            $number++;
        }

        return $slug;
    }
}
