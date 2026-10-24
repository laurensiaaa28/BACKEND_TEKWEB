<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    // PUBLIK: daftar kategori
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('name')->get();

        return CategoryResource::collection($categories);
    }

    // PUBLIK: detail kategori
    public function show(string $id)
    {
        $category = Category::withCount('products')->findOrFail($id);

        return new CategoryResource($category);
    }

    // ADMIN: tambah kategori
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
        ]);

        $category = Category::create($data);

        return (new CategoryResource($category))
            ->additional(['message' => 'Kategori berhasil ditambahkan'])
            ->response()
            ->setStatusCode(201);
    }

    // ADMIN: ubah kategori
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
        ]);

        $category->update($data);

        return (new CategoryResource($category))
            ->additional(['message' => 'Kategori berhasil diperbarui']);
    }

    // ADMIN: hapus kategori
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Kategori tidak bisa dihapus karena masih memiliki produk.',
            ], 409);
        }

        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus']);
    }
}