<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Services\PropertySearchService;

class PropertyController extends Controller
{
    public function __construct(protected PropertySearchService $searchService) {}
    
    /**
     * Search properties via Bounding Box with Redis Cache.
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

        return response()->json($data);
    }

    // GET /api/real-estate (public)
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
        ];
    }

    // GET /api/real-estate/{id} (public)
    // GET /api/real-estate/{id} (public)
    public function show($id): JsonResponse
    {
        $cacheKey = "property_{$id}_detail";

        $data = \Illuminate\Support\Facades\Cache::tags(["property_{$id}"])->remember($cacheKey, now()->addMinutes(60), function () use ($id) {
            // Find manually to ensure 404 if not found (Laravel model binding does this, but inside closure we need ID)
            $property = Property::findOrFail($id);
            return $property->load('agent', 'category', 'media');
        });

        return response()->json($data);
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

        $agent = \App\Models\Agent::where('user_id', auth()->id())->first();

        $property = Property::create([
            'agent_id' => $agent ? $agent->id : null,
            'publisher_id' => auth()->id(),
            'publisher_type' => \App\Models\User::class,
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
    // POST /api/real-estate/{id}/contact (public)
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
                    'sender_id' => $senderId,
                    'content' => $validated['message'],
                    'type' => 'text'
                ]);
            }
        });

        return response()->json(['message' => 'Inquiry sent successfully'], 201);
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
