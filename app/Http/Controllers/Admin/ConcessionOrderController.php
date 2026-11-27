<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConcessionOrder;
use App\Models\ConcessionProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConcessionOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = ConcessionOrder::with('user');

        if ($request->filled('q')) {
            $keyword = $request->q;

            $query->where(function ($q) use ($keyword) {
                $q->where('order_code', 'like', "%{$keyword}%")
                    ->orWhereHas('user', function ($userQuery) use ($keyword) {
                        $userQuery->where('name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.concession-orders.index', compact('orders'));
    }

    public function show(ConcessionOrder $concessionOrder)
    {
        $concessionOrder->load(['user', 'items.product']);

        return view('admin.concession-orders.show', compact('concessionOrder'));
    }

    public function updateStatus(Request $request, ConcessionOrder $concessionOrder)
    {
        $data = $request->validate([
            'status' => [
                'required',
                'in:confirmed,preparing,completed,cancelled',
            ],
        ]);

        try {
            DB::transaction(function () use ($concessionOrder, $data) {
                $order = ConcessionOrder::whereKey($concessionOrder->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $current = $order->status;
                $next = $data['status'];

                if ($current === $next) {
                    throw ValidationException::withMessages([
                        'status' => 'Đơn hàng đang ở trạng thái này.',
                    ]);
                }

                if ($current === 'pending' && $next === 'confirmed') {
                    $this->deductStock($order);
                } elseif (
                    $current === 'confirmed' &&
                    $next === 'preparing'
                ) {
                    // Chuyển sang chuẩn bị, không trừ kho lần nữa.
                } elseif (
                    in_array($current, ['confirmed', 'preparing'], true) &&
                    $next === 'cancelled'
                ) {
                    $this->restoreStock($order);
                } elseif (
                    $current === 'preparing' &&
                    $next === 'completed'
                ) {
                    // Hoàn thành đơn, không thay đổi tồn kho.
                } else {
                    throw ValidationException::withMessages([
                        'status' => 'Không thể chuyển từ trạng thái hiện tại sang trạng thái được chọn.',
                    ]);
                }

                $order->update(['status' => $next]);
            });
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('admin.concession-orders.show', $concessionOrder)
            ->with('success', 'Cập nhật trạng thái đơn hàng thành công.');
    }

    public function updatePaymentStatus(
        Request $request,
        ConcessionOrder $concessionOrder
    ) {
        $data = $request->validate([
            'payment_status' => ['required', 'in:pending,paid,refunded'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($concessionOrder, $data) {
            $order = ConcessionOrder::whereKey($concessionOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'payment_status' => 'Không thể cập nhật thanh toán cho đơn đã hủy.',
                ]);
            }

            if (
                $order->payment_status === 'refunded' &&
                $data['payment_status'] !== 'refunded'
            ) {
                throw ValidationException::withMessages([
                    'payment_status' => 'Đơn đã hoàn tiền, không thể đổi lại trạng thái thanh toán.',
                ]);
            }

            if (
                $data['payment_status'] === 'refunded' &&
                $order->payment_status !== 'paid'
            ) {
                throw ValidationException::withMessages([
                    'payment_status' => 'Chỉ có thể đánh dấu hoàn tiền cho đơn đã thanh toán.',
                ]);
            }

            $order->payment_status = $data['payment_status'];
            $order->payment_reference = $data['payment_reference'] ?? null;

            if ($data['payment_status'] === 'paid' && !$order->paid_at) {
                $order->paid_at = now();
            }

            $order->save();
        });

        return back()->with('success', 'Cập nhật thanh toán thành công.');
    }

    private function deductStock(ConcessionOrder $order): void
    {
        $items = $order->items()
            ->with('product')
            ->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'status' => 'Đơn hàng không có sản phẩm.',
            ]);
        }

        foreach ($items as $item) {
            if (!$item->concession_product_id) {
                throw ValidationException::withMessages([
                    'status' => "Sản phẩm {$item->product_name} không còn liên kết với kho.",
                ]);
            }

            $product = ConcessionProduct::whereKey($item->concession_product_id)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                throw ValidationException::withMessages([
                    'status' => "Không tìm thấy sản phẩm {$item->product_name}.",
                ]);
            }

            if (!$product->is_active) {
                throw ValidationException::withMessages([
                    'status' => "Sản phẩm {$product->name} đã ngừng bán.",
                ]);
            }

            if ($product->stock_quantity < $item->quantity) {
                throw ValidationException::withMessages([
                    'status' => "Sản phẩm {$product->name} không đủ tồn kho. Còn {$product->stock_quantity}.",
                ]);
            }

            $product->decrement('stock_quantity', $item->quantity);
        }
    }

    private function restoreStock(ConcessionOrder $order): void
    {
        $items = $order->items()->get();

        foreach ($items as $item) {
            if (!$item->concession_product_id) {
                continue;
            }

            $product = ConcessionProduct::whereKey($item->concession_product_id)
                ->lockForUpdate()
                ->first();

            if ($product) {
                $product->increment('stock_quantity', $item->quantity);
            }
        }
    }
}
