<?php

namespace App\Http\Controllers;

use App\Models\ConcessionOrder;
use App\Models\ConcessionOrderItem;
use App\Models\ConcessionProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ConcessionController extends Controller
{
    public function index()
    {
        $products = ConcessionProduct::where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12);

        $cart = session('concession_cart', []);

        return view('concessions.index', compact('products', 'cart'));
    }

    public function addToCart(Request $request, ConcessionProduct $product)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        if (!$product->is_active || $product->stock_quantity < 1) {
            return back()->withErrors([
                'quantity' => 'Sản phẩm hiện không khả dụng.',
            ]);
        }

        $cart = session('concession_cart', []);
        $id = (string) $product->id;
        $currentQuantity = (int) ($cart[$id]['quantity'] ?? 0);
        $newQuantity = $currentQuantity + $data['quantity'];

        if ($newQuantity > $product->stock_quantity) {
            return back()->withErrors([
                'quantity' => "Chỉ còn {$product->stock_quantity} sản phẩm trong kho.",
            ]);
        }

        $cart[$id] = [
            'quantity' => $newQuantity,
        ];

        session(['concession_cart' => $cart]);

        return back()->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    public function cart()
    {
        $cart = session('concession_cart', []);
        $items = collect();

        if (!empty($cart)) {
            $products = ConcessionProduct::whereIn(
                'id',
                array_keys($cart)
            )->get()->keyBy('id');

            foreach ($cart as $id => $item) {
                $product = $products->get($id);

                if (!$product || !$product->is_active) {
                    continue;
                }

                $quantity = (int) $item['quantity'];

                $items->push([
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product->price * $quantity,
                ]);
            }
        }

        $total = $items->sum('subtotal');

        return view('concessions.cart', compact('items', 'total'));
    }

    public function updateCart(Request $request, ConcessionProduct $product)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        if (!$product->is_active ||
            $data['quantity'] > $product->stock_quantity) {
            return back()->withErrors([
                'quantity' => 'Số lượng không hợp lệ hoặc không đủ tồn kho.',
            ]);
        }

        $cart = session('concession_cart', []);
        $id = (string) $product->id;

        if (!isset($cart[$id])) {
            return back()->withErrors([
                'quantity' => 'Sản phẩm không có trong giỏ hàng.',
            ]);
        }

        $cart[$id]['quantity'] = $data['quantity'];
        session(['concession_cart' => $cart]);

        return back()->with('success', 'Đã cập nhật giỏ hàng.');
    }

    public function removeFromCart(ConcessionProduct $product)
    {
        $cart = session('concession_cart', []);
        unset($cart[(string) $product->id]);

        session(['concession_cart' => $cart]);

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $cart = session('concession_cart', []);

        if (empty($cart)) {
            return redirect()->route('concessions.cart')
                ->withErrors(['cart' => 'Giỏ hàng đang trống.']);
        }

        $order = DB::transaction(function () use ($cart, $data) {
            $orderItems = [];
            $subtotal = 0;

            foreach ($cart as $id => $cartItem) {
                $product = ConcessionProduct::whereKey($id)
                    ->lockForUpdate()
                    ->first();

                if (
                    !$product ||
                    !$product->is_active ||
                    $product->stock_quantity < (int) $cartItem['quantity']
                ) {
                    throw ValidationException::withMessages([
                        'cart' => 'Một số sản phẩm đã hết hàng hoặc không đủ số lượng. Vui lòng kiểm tra lại giỏ hàng.',
                    ]);
                }

                $quantity = (int) $cartItem['quantity'];
                $unitPrice = (float) $product->price;
                $itemSubtotal = $unitPrice * $quantity;

                $subtotal += $itemSubtotal;

                $orderItems[] = [
                    'concession_product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $order = ConcessionOrder::create([
                'user_id' => auth()->id(),
                'order_code' => 'CB-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(5)),
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'total_amount' => $subtotal,
                'status' => 'pending',
                'payment_status' => 'pending',
                'note' => $data['note'] ?? null,
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            return $order;
        });

        session()->forget('concession_cart');

        return redirect()
            ->route('concessions.orders.show', $order)
            ->with('success', 'Đặt bắp nước thành công. Vui lòng chờ nhân viên xác nhận.');
    }

    public function orders()
    {
        $orders = ConcessionOrder::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('concessions.orders', compact('orders'));
    }

    public function showOrder(ConcessionOrder $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $order->load('items');

        return view('concessions.order-detail', compact('order'));
    }
}