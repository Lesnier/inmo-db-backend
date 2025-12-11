<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

use App\Services\PropertySearchService;

/**
 * @OA\Tag(
 *     name="Real Estate Properties",
 *     description="API Endpoints for Property Search & Management"
 * )
 */
class PropertyController extends Controller
{
    public function __construct(protected PropertySearchService $searchService) {}
    
    /**
     * Search properties via Bounding Box with Redis Cache.
     */
    /**
     * @OA\Get(
     *     path="/api/real-estate/search",
     *     summary="Search Properties (Geo + Filters)",
     *     tags={"Real Estate Properties"},
     *     @OA\Parameter(name="sw_lat", in="query", @OA\Schema(type="number", format="float")),
     *     @OA\Parameter(name="sw_lng", in="query", @OA\Schema(type="number", format="float")),
     *     @OA\Parameter(name="ne_lat", in="query", @OA\Schema(type="number", format="float")),
     *     @OA\Parameter(name="ne_lng", in="query", @OA\Schema(type="number", format="float")),
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="min_price", in="query", @OA\Schema(type="number")),
     *     @OA\Parameter(name="max_price", in="query", @OA\Schema(type="number")),
     *     @OA\Response(response=200, description="Search results")
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sw_lat' => 'nullable|numeric',
            'sw_lng' => 'nullable|numeric',
            'ne_lat' => 'nullable|numeric',
            'ne_lng' => 'nullable|numeric',
            'zoom' => 'integer|min:1|max:22',
            // Filters
            'category_id' => 'nullable|integer',
            'operation_type' => 'nullable|string',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'bedrooms' => 'nullable|integer',
            'bathrooms' => 'nullable|integer',
        ]);

        $bbox = null;
        if ($request->filled(['sw_lat', 'sw_lng', 'ne_lat', 'ne_lng'])) {
             $bbox = [
                'sw_lat' => $validated['sw_lat'],
                'sw_lng' => $validated['sw_lng'],
                'ne_lat' => $validated['ne_lat'],
                'ne_lng' => $validated['ne_lng'],
            ];
        }

        $filters = $request->only(['category_id', 'operation_type', 'min_price', 'max_price', 'bedrooms', 'bathrooms']);
        
        $results = $this->searchService->search($filters, $bbox, $request->input('zoom', 12));

        // Use same transformation
        $data = $results->map(function ($property) {
            return $this->transformProperty($property);
        });

        return response()->json(['data' => $data]);
    }

    // GET /api/real-estate (public)
    /**
     * @OA\Get(
     *     path="/api/real-estate",
     *     summary="List Properties (Public)",
     *     tags={"Real Estate Properties"},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="sort", in="query", @OA\Schema(type="string", enum={"price_asc","price_desc","updated"})),
     *     @OA\Response(response=200, description="List of properties")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Property::published()->with(['category', 'media', 'building']);

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

        if ($request->has('bedrooms')) {
            $query->where('data->attributes->bedrooms', $request->bedrooms);
        }

        if ($request->has('bathrooms')) {
            $query->where('data->attributes->bathrooms', $request->bathrooms);
        }

        if ($request->has('area_min')) {
            $query->where('data->attributes->area', '>=', $request->area_min);
        }

        if ($request->has('area_max')) {
            $query->where('data->attributes->area', '<=', $request->area_max);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('categories')) {
            $categories = is_array($request->categories) ? $request->categories : explode(',', $request->categories);
            $query->whereIn('category_id', $categories);
        }

        // Filter by agent (for agent's own properties)
        if ($request->has('agent_id')) {
            $query->where('agent_id', $request->agent_id);
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

        // Transform data to match frontend interface
        $properties->getCollection()->transform(function ($property) {
            return $this->transformProperty($property);
        });

        return response()->json($properties);
    }

    private function transformProperty(Property $property): array
    {
        $data = is_array($property->data) ? $property->data : (
            $property->data instanceof \App\DTOs\PropertyData
                ? $property->data->toArray()
                : []
        );

        $coordinates = null;
        if (isset($data['coordinates']) && is_array($data['coordinates'])) {
            $coordinates = [
                'lat' => (float) ($data['coordinates']['lat'] ?? 0),
                'lng' => (float) ($data['coordinates']['lng'] ?? 0),
            ];
        }

        return [
            'id' => $property->id,
            'title' => $data['address'] ?? $property->title,
            'price' => (float) $property->price,
            'address' => $data['address'] ?? $property->title,
            'area' => $data['attributes']['area'] ?? null,
            'bedrooms' => $data['attributes']['bedrooms'] ?? null,
            'bathrooms' => $data['attributes']['bathrooms'] ?? null,
            'parking' => $data['attributes']['parking'] ?? null,
            'badges' => $data['badges'] ?? [],
            'images' => $data['images'] ?? [],
            'verified' => $data['verified'] ?? false,
            'coordinates' => $coordinates,
            'status' => $property->status,
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/real-estate/mine",
     *     summary="Get My Properties",
     *     tags={"Real Estate Properties"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of my properties")
     * )
     */
    public function myProperties(Request $request): JsonResponse
    {
        // "My" properties = Properties where I am the publisher
        $userId = auth()->id();
        $page = $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 10);
        $status = $request->get('status', 'all');

        // Generate a unique cache key for this specific request
        $cacheKey = "user_{$userId}_properties_p{$page}_s{$status}_pp{$perPage}";

        $properties = \Illuminate\Support\Facades\Cache::tags(["user_{$userId}_properties"])->remember($cacheKey, now()->addMinutes(10), function () use ($userId, $request, $perPage) {
            $query = Property::where('publisher_id', $userId)
                             ->with(['category', 'media', 'building']);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $currentProperties = $query->latest('updated_at')->paginate($perPage);

             $currentProperties->getCollection()->transform(function ($property) {
                return $this->transformProperty($property);
            });
            
            return $currentProperties;
        });

        return response()->json($properties);
    }

    // GET /api/real-estate/{id} (public)
    // GET /api/real-estate/{id} (public)
    /**
     * @OA\Get(
     *     path="/api/real-estate/{id}",
     *     summary="Get Property Details",
     *     tags={"Real Estate Properties"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Property details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $cacheKey = "property_{$id}_detail";

        $data = \Illuminate\Support\Facades\Cache::tags(["property_{$id}"])->remember($cacheKey, now()->addMinutes(60), function () use ($id) {
            // Find manually to ensure 404 if not found (Laravel model binding does this, but inside closure we need ID)
            $property = Property::findOrFail($id);
            
            // Load base relations
            $property->load(['publisher', 'category', 'media']);

            // "Agent" Logic: Check if Publisher is a User and has 'real_estate_agent' role
            if ($property->publisher_type === \App\Models\User::class && $property->publisher) {
                $user = $property->publisher;
                // Ensure role is loaded. Voyager Users usually have 'role' relation.
                // We check if relationship exists or load it.
                if (!$user->relationLoaded('role')) {
                    $user->load('role');
                }
                
                if ($user->role && $user->role->name === 'real_estate_agent') {
                    // It is an agent, load the Agent profile
                    $user->load('agent');
                    // Attach as 'agent' relation to Property for backward compatibility if frontend expects check
                    $property->setRelation('agent', $user->agent);
                }
            }

            return $property;
        });

        return response()->json(['data' => $data]);
    }



    // POST /api/real-estate (auth: agent or admin)
    /**
     * @OA\Post(
     *     path="/api/real-estate",
     *     summary="Create Property (Auth)",
     *     tags={"Real Estate Properties"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","price"},
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="price", type="number"),
     *             @OA\Property(property="category_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:inmo_categories,id',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'data' => 'nullable|array',
            'associations' => 'nullable|array',
        ]);

        $data = $validated['data'] ?? [];
        
        // Map extra fields to data
        if (!empty($validated['description'])) $data['about'] = $validated['description'];
        if (!empty($validated['address'])) $data['address'] = $validated['address'];
        if (isset($validated['lat']) || isset($validated['lng'])) {
            $data['coordinates'] = [
                'lat' => $validated['lat'] ?? 0,
                'lng' => $validated['lng'] ?? 0,
            ];
        }

        $property = Property::create([
            'publisher_id' => auth()->id(),
            'publisher_type' => \App\Models\User::class,
            'title' => $validated['title'],
            'price' => $validated['price'],
            'category_id' => $validated['category_id'],
            'data' => $data,
            'status' => 'draft',
        ]);

        if (!empty($validated['associations'])) {
            foreach ($validated['associations'] as $assoc) {
                if (isset($assoc['type'], $assoc['id'])) {
                    \App\Models\Association::create([
                        'object_type_a' => 'property',
                        'object_id_a' => $property->id,
                        'object_type_b' => \Illuminate\Support\Str::singular($assoc['type']),
                        'object_id_b' => $assoc['id'],
                    ]);
                }
            }
        }

        return response()->json(['data' => $property], 201);
    }

    /**
     * Helper to clear user property cache
     */
    protected function clearUserPropertyCache($userId)
    {
        \Illuminate\Support\Facades\Cache::tags(["user_{$userId}_properties"])->flush();
    }

    // PUT /api/real-estate/{id} (auth: owner or admin)
    /**
     * @OA\Put(
     *     path="/api/real-estate/{id}",
     *     summary="Update Property (Auth)",
     *     tags={"Real Estate Properties"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="price", type="number"),
     *             @OA\Property(property="status", type="string", enum={"draft","published","archived"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated")
     * )
     */
    // PUT /api/real-estate/{id} (auth: owner or admin)

    public function update(Request $request, Property $property): JsonResponse
    {
        $this->authorize('update', $property);

        // ... validation ...

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

        // Clear cache for the publisher
        if ($property->publisher_type === \App\Models\User::class) {
            $this->clearUserPropertyCache($property->publisher_id);
        }

        return response()->json(['data' => $property]);
    }

    // ... (destroy method)

    public function destroy(Property $property): JsonResponse
    {
        $this->authorize('delete', $property);
        $property->delete();

        // Clear cache
        if ($property->publisher_type === \App\Models\User::class) {
            $this->clearUserPropertyCache($property->publisher_id);
        }

        return response()->json(['message' => 'Property deleted']);
    }

    // POST /api/real-estate/{id}/contact (public)
    // POST /api/real-estate/{id}/contact (public)
    /**
     * @OA\Post(
     *     path="/api/real-estate/{property}/contact",
     *     summary="Contact Agent (Send Inquiry)",
     *     tags={"Real Estate Properties"},
     *     @OA\Parameter(name="property", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","message"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Inquiry sent")
     * )
     */
    public function sendContact(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string|max:2000',
        ]);

        $agentId = $property->publisher_id; // Using publisher as agent/owner
        if (!$agentId) {
            // fallback to admin if no agent assigned, or error?
            // For now, fail if no agent to assign deal to.
             return response()->json(['error' => 'Property has no agent/publisher assigned.'], 400);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $property, $agentId) {
            
            // 1. Find or Create CRM Contact
            $contact = \App\Models\Contact::where('email', $validated['email'])->first();
            if (!$contact) {
                $contact = \App\Models\Contact::create([
                    'first_name' => $validated['name'],
                    'last_name' => '', // Split name if needed or leave empty
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'owner_id' => $agentId,
                    'lifecycle_stage' => 'lead',
                    'lead_status' => 'new',
                    // 'source' => 'web_inquiry', // Add if column exists in migration, otherwise put in data
                    'data' => ['source' => 'web_inquiry']
                ]);
            }

            // 2. Create Deal (Inquiry)
            $pipeline = \App\Models\Pipeline::where('entity_type', 'deal')->where('name', 'Sales')->first() 
                ?? \App\Models\Pipeline::where('entity_type', 'deal')->first();
            
            $stageId = $pipeline ? $pipeline->stages()->orderBy('position', 'asc')->value('id') : null;

            $deal = \App\Models\Deal::create([
                'title' => 'Inquiry: ' . $property->title,
                'pipeline_id' => $pipeline?->id,
                'stage_id' => $stageId,
                'owner_id' => $agentId,
                'status' => 'open',
                'amount' => $property->price,
                'currency' => $property->currency,
                'expected_close_date' => now()->addDays(30),
                'data' => []
            ]);

            // 3. Associations
            // Deal <-> Contact (Using Helper or Repo would be cleaner but direct for now)
            \App\Models\Association::create([
                'object_type_a' => 'deal',
                'object_id_a' => $deal->id,
                'object_type_b' => 'contact',
                'object_id_b' => $contact->id,
                'type' => 'primary_contact' // Enum: AssociationType::PRIMARY_CONTACT
            ]);

            // Deal <-> Property
            \App\Models\Association::create([
                'object_type_a' => 'deal',
                'object_id_a' => $deal->id,
                'object_type_b' => 'property',
                'object_id_b' => $property->id,
                'type' => 'candidate_property' 
            ]);

            // 4. Activity (The Message/Note)
            $activity = \App\Models\Activity::create([
                'type' => 'note',
                'content' => "Inquiry received:\n" . $validated['message'],
                'created_by' => $agentId, // System (or Agent who owns it now)
                'data' => []
            ]);

            // Activity <-> Deal
            \App\Models\Association::create([
                'object_type_a' => 'activity',
                'object_id_a' => $activity->id,
                'object_type_b' => 'deal',
                'object_id_b' => $deal->id,
                'type' => 'timeline_event'
            ]);
            
            // 5. Chat (If Sender is Auth User)
            $senderId = \Illuminate\Support\Facades\Auth::id();
            if ($senderId) {
                // Check if chat exists or create new
                $chat = \App\Models\Chat::create(['type' => 'private']);
                $chat->participants()->attach([$agentId, $senderId]);
                
                \App\Models\Message::create([
                    'chat_id' => $chat->id,
                    'user_id' => $senderId,
                    'content' => $validated['message'],
                    'type' => 'text'
                ]);
            }
        });

        return response()->json(['message' => 'Inquiry sent successfully'], 201);
    }

    // POST /api/real-estate/{id}/favorite (auth)
    /**
     * @OA\Post(
     *     path="/api/real-estate/{property}/favorite",
     *     summary="Toggle Favorite",
     *     tags={"Real Estate Properties"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="property", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Toggled")
     * )
     */
    public function toggleFavorite(Property $property): JsonResponse
    {
        $user = auth()->user();
        $isFavorited = $user->favorite_properties()->toggle($property->id);

        return response()->json(['data' => [
            'favorited' => isset($isFavorited['attached']) && count($isFavorited['attached']) > 0,
        ]]);
    }

    // POST /api/real-estate/{id}/duplicate (auth: owner or admin)
    /**
     * @OA\Post(
     *     path="/api/real-estate/{property}/duplicate",
     *     summary="Duplicate Property",
     *     tags={"Real Estate Properties"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="property", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=201, description="Duplicated")
     * )
     */
    public function duplicate(Property $property): JsonResponse
    {
        $this->authorize('update', $property); // Assuming same permissions

        $newProperty = $property->replicate(['slug', 'status', 'published_at', 'created_at', 'updated_at']);
        $newProperty->title = $newProperty->title . ' (Copy)';
        $newProperty->status = 'draft';
        $newProperty->slug = Str::slug($newProperty->title) . '-' . time();
        $newProperty->save();

        // Duplicate relationships if needed (here just simple cloning)
        
        return response()->json(['data' => $newProperty], 201);
    }

    // PUT /api/real-estate/{id}/archive (auth: owner or admin)
    /**
     * @OA\Put(
     *     path="/api/real-estate/{property}/archive",
     *     summary="Archive Property",
     *     tags={"Real Estate Properties"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="property", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Archived")
     * )
     */
    public function archive(Property $property): JsonResponse
    {
        $this->authorize('update', $property);

        $property->update(['status' => 'archived']);

        return response()->json(['message' => 'Property archived successfully']);
    }

    // GET /api/real-estate/{id}/analytics (auth: owner or admin)
    /**
     * @OA\Get(
     *     path="/api/real-estate/{property}/analytics",
     *     summary="Get Property Analytics",
     *     tags={"Real Estate Properties"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="property", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Analytics data")
     * )
     */
    public function analytics(Property $property): JsonResponse
    {
        $this->authorize('update', $property);

        // Stubbed Analytics
        // In real system, query views_count table, or leads count from Deals
        $leadsCount = \App\Models\Deal::whereHas('associations', function ($q) use ($property) {
            $q->where('object_type_b', 'property')->where('object_id_b', $property->id);
        })->count();

        return response()->json(['data' => [
            'views' => rand(10, 500), // Stub
            'leads' => $leadsCount,
            'favorites' => $property->favorites()->count(),
            'days_on_market' => $property->published_at ? now()->diffInDays($property->published_at) : 0,
        ]]);
    }
}
