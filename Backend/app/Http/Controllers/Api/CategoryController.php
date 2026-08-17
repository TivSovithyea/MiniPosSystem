<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(Category::withCount('products')
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')->paginate(min(max($request->integer('per_page', 20), 1), 100)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:categories'], 'slug' => ['required', 'string', 'max:100', 'unique:categories'], 'description' => ['nullable', 'string'], 'is_active' => ['sometimes', 'boolean']]);

        return response()->json(Category::create($data), 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json($category->load('products'));
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:100', Rule::unique('categories')->ignore($category)], 'slug' => ['sometimes', 'string', 'max:100', Rule::unique('categories')->ignore($category)], 'description' => ['nullable', 'string'], 'is_active' => ['sometimes', 'boolean']]);
        $category->update($data);

        return response()->json($category);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json(['message' => 'A category containing products cannot be deleted.'], 409);
        }
        $category->delete();

        return response()->json(null, 204);
    }
}
