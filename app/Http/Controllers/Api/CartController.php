<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartItemResource;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // Lihat isi keranjang + ringkasan total
    public function index(Request $request)
    {
        $items = CartItem::with('product.category')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return CartItemResource::collection($items)->additional([
            'summary' => [
                'total_items' => (int) $items->sum('quantity'),
                'total_price' => (float) $items->sum(
                    fn ($item) => $item->product->price * $item->quantity
                ),
            ],
        ]);
    }

    // Tambah produk ke keranjang
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::where('is_active', true)->findOrFail($data['product_id']);

        $item = CartItem::firstOrNew([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        $newQuantity = ($item->exists ? $item->quantity : 0) + $data['quantity'];

        if ($newQuantity > $product->stock) {
            return response()->json([
                'message' => "Stok tidak mencukupi. Sisa stok: {$product->stock}",
            ], 422);
        }

        $item->quantity = $newQuantity;
        $item->save();
        $item->load('product.category');

        return (new CartItemResource($item))
            ->additional(['message' => 'Produk ditambahkan ke keranjang'])
            ->response()
            ->setStatusCode($item->wasRecentlyCreated ? 201 : 200);
    }

    // Ubah jumlah item di keranjang
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $item = CartItem::with('product')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        if ($data['quantity'] > $item->product->stock) {
            return response()->json([
                'message' => "Stok tidak mencukupi. Sisa stok: {$item->product->stock}",
            ], 422);
        }

        $item->update(['quantity' => $data['quantity']]);
        $item->load('product.category');

        return (new CartItemResource($item))
            ->additional(['message' => 'Jumlah item diperbarui']);
    }

    // Hapus satu item dari keranjang
    public function destroy(Request $request, string $id)
    {
        $item = CartItem::where('user_id', $request->user()->id)->findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Item dihapus dari keranjang']);
    }

    // Kosongkan keranjang
    public function clear(Request $request)
    {
        CartItem::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Keranjang dikosongkan']);
    }
}