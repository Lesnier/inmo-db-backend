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
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */
     
    /**
     * @OA\Post(
     *      path="/api/crm/contacts",
     *      operationId="storeContact",
     *      tags={"Contacts"},
     *      summary="Store new contact",
     *      description="Returns contact data",
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
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      )
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
        ]);

        $contact = Contact::create(array_merge($validated, [
            'user_id' => Auth::id(), // creator (legacy field, maybe deprecate in favor of owner_id?)
            'owner_id' => $validated['owner_id'] ?? Auth::id(), // Default to me if not specified
        ]));

        return response()->json($contact, 201);
    }
}
