<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\CrmTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    protected $timelineService;

    public function __construct(CrmTimelineService $timelineService)
    {
        $this->timelineService = $timelineService;
    }

    /**
     * @OA\Get(
     *      path="/api/crm/contacts",
     *      operationId="getContactsList",
     *      tags={"Contacts"},
     *      summary="Get list of contacts",
     *      description="Returns list of contacts",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="first_name", type="string", example="John"),
     *                  @OA\Property(property="last_name", type="string", example="Doe"),
     *                  @OA\Property(property="email", type="string", example="john@example.com"),
     *                  @OA\Property(property="lead_status", type="string", example="new"),
     *                  @OA\Property(property="owner_id", type="integer", example=10)
     *              )),
     *              @OA\Property(property="links", type="object"),
     *              @OA\Property(property="meta", type="object")
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $page = $request->input('page', 1);
        $filters = md5(json_encode($request->all()));
        
        $cacheKey = "user_{$userId}_contacts_list_p{$page}_{$filters}";

        return \Illuminate\Support\Facades\Cache::tags(["user_{$userId}_contacts"])->remember($cacheKey, now()->addMinutes(60), function () use ($request) {
            // Contacts owned by user OR all contacts? Using 'contacts()' from User model (owned)
            $query = Auth::user()->contacts();
    
            if ($request->has('search')) {
                $term = $request->search;
                $query->where(function($q) use ($term) {
                    $q->where('first_name', 'like', "%$term%")
                      ->orWhere('last_name', 'like', "%$term%")
                      ->orWhere('email', 'like', "%$term%");
                });
            }
    
            if ($request->has('lead_status')) {
                $query->where('lead_status', $request->lead_status);
            }
    
            if ($request->has('city')) {
                $query->where('city', $request->city);
            }
    
            return $query->paginate(50);
        });
    }

    /**
     * @OA\Get(
     *      path="/api/crm/contacts/{id}",
     *      tags={"Contacts"},
     *      summary="Get Contact Details",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="attributes", type="object", ref="#/components/schemas/Contact"),
     *                  @OA\Property(property="timeline", type="array", @OA\Items(type="object")),
     *                  @OA\Property(property="associations", type="object",
     *                       @OA\Property(property="deals", type="array", @OA\Items(type="object")),
     *                       @OA\Property(property="tickets", type="array", @OA\Items(type="object")),
     *                       @OA\Property(property="companies", type="array", @OA\Items(type="object")),
     *                       @OA\Property(property="properties", type="array", @OA\Items(type="object"))
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(response=404, description="Contact not found")
     * )
     */
    public function show($id)
    {
        $cacheKey = "crm_contact_{$id}_detail";

        return \Illuminate\Support\Facades\Cache::tags(["crm_contact_{$id}"])->remember($cacheKey, now()->addMinutes(60), function () use ($id) {
            $contact = Contact::findOrFail($id);
            
            $timeline = $this->timelineService->getTimeline($contact);
    
            $deals = $contact->getAssociated(\App\Models\Deal::class)->get();
            $tickets = $contact->getAssociated(\App\Models\Ticket::class)->get();
            $companies = $contact->getAssociated(\App\Models\Company::class)->get();
            $properties = $contact->getAssociated(\App\Models\Property::class)->get();
    
            return [
                'data' => [
                    'id' => $contact->id,
                    'attributes' => $contact->load(['user']), // user = owner
                    'timeline' => $timeline,
                    'associations' => [
                        'deals' => $deals,
                        'tickets' => $tickets,
                        'companies' => $companies,
                        'properties' => $properties,
                    ]
                ]
            ];
        });
    }

    /**
     * @OA\Post(
     *      path="/api/crm/contacts",
     *      operationId="storeContact",
     *      tags={"Contacts"},
     *      summary="Store new contact",
     *      description="Returns contact data",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"first_name"},
     *              @OA\Property(property="first_name", type="string", example="John"),
     *              @OA\Property(property="last_name", type="string", example="Doe"),
     *              @OA\Property(property="email", type="string", format="email", example="john.doe@test.com"),
     *              @OA\Property(property="mobile", type="string", example="123456789"),
     *          )
     *      ),
     *      @OA\Response(response=201, description="Successful operation"),
     *      @OA\Response(response=400, description="Bad Request")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'mobile' => 'nullable|string',
            'lifecycle_stage' => 'nullable|string',
            'lead_status' => 'nullable|string',
            'owner_id' => 'nullable|exists:users,id',
            'country' => 'nullable|string',
            'state' => 'nullable|string',
            'city' => 'nullable|string',
            'address' => 'nullable|string',
            'associations' => 'nullable|array',
        ]);

        $contact = Contact::create(array_merge($validated, [
            'user_id' => Auth::id(), // creator (legacy field, maybe deprecate in favor of owner_id?)
            'owner_id' => $validated['owner_id'] ?? Auth::id(), // Default to me if not specified
        ]));

        if (!empty($validated['associations'])) {
            foreach ($validated['associations'] as $assoc) {
                if (isset($assoc['type'], $assoc['id'])) {
                    \App\Models\Association::create([
                        'object_type_a' => 'contact',
                        'object_id_a' => $contact->id,
                        'object_type_b' => \Illuminate\Support\Str::singular($assoc['type']),
                        'object_id_b' => $assoc['id'],
                    ]);
                }
            }
        }

        $contact->refresh();

        return response()->json(['data' => $contact], 201);
    }
    /**
     * @OA\Post(
     *      path="/api/crm/contacts/{id}/assign",
     *      tags={"Contacts"},
     *      summary="Assign Contact to Agent",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(@OA\Property(property="owner_id", type="integer"))
     *      ),
     *      @OA\Response(response=200, description="Assigned")
     * )
     */
    public function assign(Request $request, $id)
    {
        $request->validate(['owner_id' => 'required|exists:users,id']);
        $contact = Contact::findOrFail($id);
        $contact->update(['owner_id' => $request->owner_id]);
        
        return response()->json(['message' => 'Contact assigned successfully']);
    }

    /**
     * @OA\Get(
     *      path="/api/crm/contacts/{id}/analytics",
     *      tags={"Contacts"},
     *      summary="Get Contact Analytics",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Analytics data")
     * )
     */
    public function analytics($id)
    {
        $contact = Contact::findOrFail($id);
        
        // Calculate engagement score
        $activitiesCount = $contact->getAssociated(\App\Models\Activity::class)->count();
        $dealsCount = $contact->getAssociated(\App\Models\Deal::class)->count();

        // Get real last contacted date from activities
        $lastActivity = $contact->getAssociated(\App\Models\Activity::class)
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at', 'desc')
            ->first();
            
        return response()->json([
            'engagement_score' => min(100, $activitiesCount * 5 + $dealsCount * 10),
            'last_contacted' => $lastActivity ? $lastActivity->scheduled_at : null, 
            'lead_status' => $contact->lead_status,
            'total_activities' => $activitiesCount,
            'total_deals' => $dealsCount
        ]);
    }
}
