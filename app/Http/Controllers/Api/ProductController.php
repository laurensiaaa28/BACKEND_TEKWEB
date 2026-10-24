<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // PUBLIK: daftar produk aktif (filter: category_id, search)
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->when($request->category_id, fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->search, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->latest()
            ->paginate(10);

        return ProductResource::collection($products);
    }

    // PUBLIK: detail produk
    public function show(string $id)
    {
        $product = Product::with('category')
            ->where('is_active', true)
            ->findOrFail($id);

        return new ProductResource($product);
    }

    // ADMIN: daftar semua produk (termasuk non-aktif)
    public function adminIndex(Request $request)
    {
        $products = Product::with('category')
            ->when($request->category_id, fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->search, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->latest()
            ->paginate(10);

        return ProductResource::collection($products);
    }

    // ADMIN: tambah produk
    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
            'img' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $path = null;

        if ($request->hasFile('img')) {
            $path = $request->file('img')->store('products', 'public');
        }

        $product = Product::create([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $this->makeSlug($data['name']),
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'stock' => $data['stock'],
            'img' => $path,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $product->load('category');

        return (new ProductResource($product))
            ->additional(['message' => 'Produk berhasil ditambahkan'])
            ->response()
            ->setStatusCode(201);
    }

    // ADMIN: ubah produk
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'name' => 'sometimes|required|string|max:150',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'img' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('img')) {
            // hapus gambar lama, simpan yang baru
            if ($product->img) {
                Storage::disk('public')->delete($product->img);
            }
            $data['img'] = $request->file('img')->store('products', 'public');
        } else {
            unset($data['img']);
        }

        if (isset($data['name'])) {
            $data['slug'] = $this->makeSlug($data['name'], $product->id);
        }

        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $product->update($data);
        $product->load('category');

        return (new ProductResource($product))
            ->additional(['message' => 'Produk berhasil diperbarui']);
    }

    // ADMIN: hapus produk
    public function destroy(string $id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        if ($product->img) {
            Storage::disk('public')->delete($product->img);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    // Membuat slug unik (nama sama => ditambah -2, -3, dst)
    private function makeSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $i = 2;

        while (
            Product::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $original . '-' . $i++;
        }

        return $slug;
    }
}