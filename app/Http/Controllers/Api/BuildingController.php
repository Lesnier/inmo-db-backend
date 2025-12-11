<?php

namespace App\Http\Controllers\Api;

use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

/**
 * @OA\Tag(
 *     name="Real Estate Buildings",
 *     description="API Endpoints for Building Management"
 * )
 */
class BuildingController extends Controller
{
    /**
     * GET /api/buildings
     * List all buildings with optional filters
     */
    /**
     * @OA\Get(
     *     path="/api/real-estate/buildings",
     *     summary="List Buildings",
     *     tags={"Real Estate Buildings"},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="List of buildings")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Building::query()->with(['publisher']);

        // Filter by publisher (user_id)
        if ($request->has('publisher_id')) {
            $query->where('publisher_id', $request->publisher_id);
        }
        
        // Map agent_id filter to publisher_id lookup
        if ($request->has('agent_id')) {
             $agent = \App\Models\Agent::find($request->agent_id);
             if ($agent) {
                 $query->where('publisher_id', $agent->user_id);
             }
        }

        // Map user_id filter to publisher_id
        if ($request->has('user_id')) {
            $query->where('publisher_id', $request->user_id);
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
     * @OA\Get(
     *     path="/api/real-estate/buildings/mine",
     *     summary="List My Buildings",
     *     tags={"Real Estate Buildings"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="List of my buildings")
     * )
     */
    public function myBuildings(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $query = Building::query()->with(['publisher']);

        // Filter by publisher (user_id)
        $query->where('publisher_id', $user->id);
        
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
    /**
     * @OA\Get(
     *     path="/api/real-estate/buildings/{id}",
     *     summary="Get Building Details",
     *     tags={"Real Estate Buildings"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Building details")
     * )
     */
    public function show($id): JsonResponse
    {
        $building = Building::with(['publisher', 'properties'])->findOrFail($id);

        return response()->json(['data' => $building]);
    }

    /**
     * @OA\Post(
     *     path="/api/real-estate/buildings",
     *     summary="Create Building",
     *     tags={"Real Estate Buildings"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="lat", type="number"),
     *             @OA\Property(property="lng", type="number")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created")
     * )
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
            'data' => $building->load(['publisher']),
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/real-estate/buildings/{id}",
     *     summary="Update Building",
     *     tags={"Real Estate Buildings"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Updated")
     * )
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
            'data' => $building->load(['publisher']),
        ]);
    }

    /**
     * DELETE /api/buildings/{id}
     * Delete a building
     */
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

    // GET /api/real-estate/buildings/{id}/units
    /**
     * @OA\Get(
     *     path="/api/real-estate/buildings/{building}/units",
     *     summary="Get Building Units (Properties)",
     *     tags={"Real Estate Buildings"},
     *     @OA\Parameter(name="building", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of units")
     * )
     */
    public function units($id): JsonResponse
    {
        $building = Building::findOrFail($id);
        
        // Assuming Property model has 'building_id'
        $units = \App\Models\Property::where('building_id', $id)
                    ->with(['category', 'media'])
                    ->paginate(15);
                    
        return response()->json($units);
    }

    // GET /api/real-estate/buildings/{id}/analytics
    /**
     * @OA\Get(
     *     path="/api/real-estate/buildings/{building}/analytics",
     *     summary="Get Building Analytics",
     *     tags={"Real Estate Buildings"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="building", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Analytics data")
     * )
     */
    public function analytics($id): JsonResponse
    {
        $building = Building::findOrFail($id);
        
        $totalUnits = \App\Models\Property::where('building_id', $id)->count();
        $availableUnits = \App\Models\Property::where('building_id', $id)->where('status', 'published')->count();
        $soldUnits = \App\Models\Property::where('building_id', $id)->where('status', 'sold')->count();
        
        $occupancyRate = $totalUnits > 0 ? (($totalUnits - $availableUnits) / $totalUnits) * 100 : 0;
        
        // Calculate estimated value
        $totalValue = \App\Models\Property::where('building_id', $id)->sum('price');
        
        return response()->json(['data' => [
            'total_units' => $totalUnits,
            'available_units' => $availableUnits,
            'sold_units' => $soldUnits,
            'occupancy_rate' => round($occupancyRate, 2),
            'total_valuation' => $totalValue,
            'average_price' => $totalUnits > 0 ? round($totalValue / $totalUnits, 2) : 0
        ]]);
    }
}
