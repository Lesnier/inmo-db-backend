<?php

namespace App\Http\Controllers\Api;

use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

class BuildingController extends Controller
{
    /**
     * GET /api/buildings
     * List all buildings with optional filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Building::query()->with(['agent', 'user']);

        // Filter by agent
        if ($request->has('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by city
        if ($request->has('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        // Filter by state
        if ($request->has('state')) {
            $query->where('state', 'like', '%' . $request->state . '%');
        }

        // Filter by country
        if ($request->has('country')) {
            $query->where('country', 'like', '%' . $request->country . '%');
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Order by
        $orderBy = $request->input('order_by', 'created_at');
        $orderDir = $request->input('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $buildings = $query->paginate($perPage);

        return response()->json($buildings);
    }

    /**
     * GET /api/buildings/{id}
     * Get a single building with relationships
     */
    public function show($id): JsonResponse
    {
        $building = Building::with(['agent', 'user', 'properties'])->findOrFail($id);

        return response()->json($building);
    }

    /**
     * POST /api/buildings
     * Create a new building
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'nullable|exists:inmo_agents,id',
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:120',
            'state' => 'nullable|string|max:120',
            'city' => 'nullable|string|max:120',
            'district' => 'nullable|string|max:120',
            'zip_code' => 'nullable|string|max:20',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'year_built' => 'nullable|integer|min:1800|max:' . (date('Y') + 5),
            'floors' => 'nullable|integer|min:1',
            'data' => 'nullable|array',
        ]);

        // Generate slug from name
        $validated['slug'] = Str::slug($validated['name']);

        // Ensure unique slug
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Building::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Determine Publisher (User or Agent)
        $user = auth()->user();
        if ($user->role && $user->role->name === 'agent') {
             // Logic to find agent record? Or just use user->id as publisher if polymorphic
             // For now, assume User is publisher.
        }

        $building = Building::create(array_merge($validated, [
            'publisher_id' => $user->id,
            'publisher_type' => \App\Models\User::class, // Or check if agent
        ]));

        return response()->json([
            'success' => true,
            'building' => $building->load(['agent', 'user']),
        ], 201);
    }

    /**
     * PUT /api/buildings/{id}
     * Update an existing building
     */
    public function update(Request $request, $id): JsonResponse
    {
        $building = Building::findOrFail($id);

        $validated = $request->validate([
            'agent_id' => 'nullable|exists:inmo_agents,id',
            'user_id' => 'nullable|exists:users,id',
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:120',
            'state' => 'nullable|string|max:120',
            'city' => 'nullable|string|max:120',
            'district' => 'nullable|string|max:120',
            'zip_code' => 'nullable|string|max:20',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'year_built' => 'nullable|integer|min:1800|max:' . (date('Y') + 5),
            'floors' => 'nullable|integer|min:1',
            'data' => 'nullable|array',
        ]);

        // Update slug if name changed
        if (isset($validated['name']) && $validated['name'] !== $building->name) {
            $validated['slug'] = Str::slug($validated['name']);

            // Ensure unique slug
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Building::where('slug', $validated['slug'])->where('id', '!=', $id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $building->update($validated);

        return response()->json([
            'success' => true,
            'building' => $building->load(['agent', 'user']),
        ]);
    }

    /**
     * DELETE /api/buildings/{id}
     * Delete a building
     */
    public function destroy($id): JsonResponse
    {
        $building = Building::findOrFail($id);
        $building->delete();

        return response()->json([
            'success' => true,
            'message' => 'Building deleted successfully',
        ]);
    }
}
