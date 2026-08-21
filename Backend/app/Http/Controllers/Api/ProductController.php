<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::with('category:id,name,slug')
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%')->orWhere('sku', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->boolean('active_only'), fn ($query) => $query->where('is_active', true))
            ->orderBy('name')->paginate(min($request->integer('per_page', 20), 100));

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }
        $product = Product::create($data);

        return response()->json($product->load('category:id,name,slug'), 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product->load('category:id,name,slug'));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $this->validated($request, $product);
        if ($request->hasFile('image')) {
            $this->deleteImage($product);
            $data['image'] = $request->file('image')->store('products', 'public');
        }
        $product->update($data);

        return response()->json($product->load('category:id,name,slug'));
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->orderItems()->exists()) {
            return response()->json(['message' => 'A sold product cannot be deleted; set is_active to false instead.'], 409);
        }
        $this->deleteImage($product);
        $product->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $presence = $product ? 'sometimes' : 'required';

        return $request->validate([
            'category_id' => [$presence, 'integer', 'exists:categories,id'], 'name' => [$presence, 'string', 'max:150'],
            'sku' => [$presence, 'string', 'max:80', Rule::unique('products')->ignore($product)], 'description' => ['nullable', 'string'],
            'price' => [$presence, 'numeric', 'min:0'], 'cost' => ['sometimes', 'numeric', 'min:0'], 'stock' => ['sometimes', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], 'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function deleteImage(Product $product): void
    {
        $path = $product->getRawOriginal('image');
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }
}
