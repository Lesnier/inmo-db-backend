<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    // GET /api/real-estate/categories (public)
    public function index(): JsonResponse
    {
        $categories = Category::all();
        return response()->json(['data' => $categories]);
    }

    // GET /api/real-estate/categories/{id} (public)
    public function show(Category $category): JsonResponse
    {
        return response()->json(['data' => $category]);
    }

    // POST /api/real-estate/categories (admin)
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => 'nullable|string|max:140|unique:real_estate_categories',
            'data' => 'nullable|array',
        ]);

        $category = Category::create($validated);
        return response()->json(['data' => $category], 201);
    }

    // PUT /api/real-estate/categories/{id} (admin)
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:120',
            'slug' => 'sometimes|string|max:140|unique:real_estate_categories,slug,' . $category->id,
            'data' => 'sometimes|array',
        ]);

        $category->update($validated);
        return response()->json(['data' => $category]);
    }

    // DELETE /api/real-estate/categories/{id} (admin)
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();
        return response()->json(['message' => 'Category deleted']);
    }
}
