<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    // Checkout: ubah isi keranjang menjadi pesanan
    public function checkout(Request $request)
    {
        $data = $request->validate([
            'recipient_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'payment_method' => 'required|in:cod,transfer',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();

        $cartItems = CartItem::with('product')
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Keranjang masih kosong.'], 422);
        }

        $order = DB::transaction(function () use ($data, $user, $cartItems) {
            $order = Order::create([
                'order_number' => 'GLN-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
                'user_id' => $user->id,
                'status' => 'pending',
                'total_price' => 0,
                'payment_method' => $data['payment_method'],
                'recipient_name' => $data['recipient_name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'notes' => $data['notes'] ?? null,
            ]);

            $total = 0;

            foreach ($cartItems as $item) {
                // kunci baris produk agar stok tidak bentrok
                $product = Product::lockForUpdate()->find($item->product_id);

                if (! $product || ! $product->is_active || $product->stock < $item->quantity) {
                    $name = $product->name ?? 'Produk';

                    throw ValidationException::withMessages([
                        'cart' => ["Stok {$name} tidak mencukupi."],
                    ]);
                }

                $subtotal = $product->price * $item->quantity;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $subtotal,
                ]);

                $product->decrement('stock', $item->quantity);

                $total += $subtotal;
            }

            $order->update(['total_price' => $total]);

            CartItem::where('user_id', $user->id)->delete();

            return $order;
        });

        $order->load('items');

        return (new OrderResource($order))
            ->additional(['message' => 'Pesanan berhasil dibuat'])
            ->response()
            ->setStatusCode(201);
    }

    // Riwayat pesanan milik sendiri (filter: status)
    public function index(Request $request)
    {
        $orders = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    // Detail pesanan milik sendiri
    public function show(Request $request, string $id)
    {
        $order = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return new OrderResource($order);
    }

    // Batalkan pesanan (hanya jika masih pending)
    public function cancel(Request $request, string $id)
    {
        $order = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Hanya pesanan berstatus pending yang bisa dibatalkan.',
            ], 422);
        }

        $order->cancelAndRestock();

        return (new OrderResource($order->fresh('items')))
            ->additional(['message' => 'Pesanan dibatalkan']);
    }
}