<?php

namespace App\Http\Controllers\Api;

use App\Models\Property;
use App\Models\PropertyContact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PropertyController extends Controller
{
    // GET /api/real-estate (public)
    public function index(Request $request): JsonResponse
    {
        $query = Property::published();

        // Filtros
        if ($request->has('q')) {
            $q = $request->q;
            $query->where('title', 'like', "%$q%")
                  ->orWhere('data->about', 'like', "%$q%");
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Sorting
        $sort = $request->get('sort', 'updated');
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'updated' => $query->latest('published_at'),
            default => $query->latest('published_at'),
        };

        $perPage = (int) $request->get('per_page', 10);
        $properties = $query->paginate($perPage);

        return response()->json($properties);
    }

    // GET /api/real-estate/{id} (public)
    public function show(Property $property): JsonResponse
    {
        return response()->json($property->load('agent', 'category', 'media'));
    }

    // POST /api/real-estate (auth: agent or admin)
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:real_estate_categories,id',
            'data' => 'nullable|array',
        ]);

        $property = Property::create([
            'agent_id' => auth()->id(),
            'title' => $validated['title'],
            'price' => $validated['price'],
            'category_id' => $validated['category_id'],
            'data' => $validated['data'] ?? [],
            'status' => 'draft',
        ]);

        return response()->json($property, 201);
    }

    // PUT /api/real-estate/{id} (auth: owner or admin)
    public function update(Request $request, Property $property): JsonResponse
    {
        $this->authorize('update', $property);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'category_id' => 'nullable|exists:real_estate_categories,id',
            'status' => 'sometimes|in:draft,published,archived',
            'data' => 'sometimes|array',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'published' && !$property->published_at) {
            $validated['published_at'] = now();
        }

        $property->update($validated);

        return response()->json($property);
    }

    // DELETE /api/real-estate/{id} (auth: owner or admin)
    public function destroy(Property $property): JsonResponse
    {
        $this->authorize('delete', $property);
        $property->delete();

        return response()->json(['message' => 'Property deleted']);
    }

    // POST /api/real-estate/{id}/contact (public)
    public function sendContact(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string|max:2000',
        ]);

        PropertyContact::create([
            'property_id' => $property->id,
            'user_id' => auth()->id(),
            'agent_id' => $property->agent_id,
            'data' => $validated,
        ]);

        return response()->json(['message' => 'Contact message sent'], 201);
    }

    // POST /api/real-estate/{id}/favorite (auth)
    public function toggleFavorite(Property $property): JsonResponse
    {
        $user = auth()->user();
        $isFavorited = $user->favorite_properties()->toggle($property->id);

        return response()->json([
            'favorited' => isset($isFavorited['attached']) && count($isFavorited['attached']) > 0,
        ]);
    }
}
