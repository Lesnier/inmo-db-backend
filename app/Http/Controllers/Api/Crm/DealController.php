<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Services\CrmTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="CRM - Deals",
 *     description="API Endpoints for managing Deals/Opportunities"
 * )
 */
class DealController extends Controller
{
    protected $timelineService;

    public function __construct(CrmTimelineService $timelineService)
    {
        $this->timelineService = $timelineService;
    }

    /**
     * List Deals (Owned by User).
     */
    /**
     * @OA\Get(
     *     path="/api/crm/deals",
     *     summary="List User's Deals",
     *     tags={"CRM - Deals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="page", in="query", description="Page number", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="pipeline_id", in="query", description="Filter by Pipeline ID", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200, 
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=500),
     *                 @OA\Property(property="title", type="string", example="Big Deal 2024"),
     *                 @OA\Property(property="amount", type="number", example=50000),
     *                 @OA\Property(property="stage_id", type="integer", example=2),
     *                 @OA\Property(property="status", type="string", example="open")
     *             )),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $page = $request->input('page', 1);
        $filters = md5(json_encode($request->all()));
        
        $cacheKey = "user_{$userId}_deals_list_p{$page}_{$filters}";

        return \Illuminate\Support\Facades\Cache::tags(["user_{$userId}_deals"])->remember($cacheKey, now()->addMinutes(60), function () use ($request) {
            $query = Auth::user()->deals()->with(['pipeline', 'stage']);

            // Optional filters
            if ($request->has('pipeline_id')) {
                $query->where('pipeline_id', $request->input('pipeline_id', 1));
            }

            return $query->paginate(20);
        });
    }

    /**
     * Show Deal Detail (3-Column Layout).
     */
    /**
     * @OA\Get(
     *     path="/api/crm/deals/{id}",
     *     summary="Get Deal Details",
     *     tags={"CRM - Deals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Deal ID", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200, 
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=500),
     *                 @OA\Property(property="attributes", type="object", ref="#/components/schemas/Deal"),
     *                 @OA\Property(property="timeline", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="associations", type="object",
     *                      @OA\Property(property="contacts", type="array", @OA\Items(type="object")),
     *                      @OA\Property(property="companies", type="array", @OA\Items(type="object")),
     *                      @OA\Property(property="tickets", type="array", @OA\Items(type="object")),
     *                      @OA\Property(property="properties", type="array", @OA\Items(type="object"))
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Deal not found")
     * )
     */
    public function show($id)
    {
        $cacheKey = "crm_deal_{$id}_detail";

        return \Illuminate\Support\Facades\Cache::tags(["crm_deal_{$id}"])->remember($cacheKey, now()->addMinutes(60), function () use ($id) {
            $deal = Deal::findOrFail($id);
            
            // Authorization check (simple ownership or permission)
            // if ($deal->owner_id !== Auth::id()) { abort(403); }
    
            // 1. Info (Attributes) - Already in $deal
    
            // 2. Timeline (Activities, Tasks, Meetings)
            $timeline = $this->timelineService->getTimeline($deal);
    
            // 3. Associations (Contacts, Companies, Tickets)
            // Using getAssociated helper from trait
            $contacts = $deal->getAssociated(\App\Models\Contact::class)->get();
            $companies = $deal->getAssociated(\App\Models\Company::class)->get();
            $tickets = $deal->getAssociated(\App\Models\Ticket::class)->get();
            $properties = $deal->getAssociated(\App\Models\Property::class)->get();

            return [ // Return raw array/object to be JSON encoded by response helper
                'data' => [
                    'id' => $deal->id,
                    'attributes' => $deal->load(['pipeline', 'stage', 'owner']),
                    'timeline' => $timeline,
                    'associations' => [
                        'contacts' => $contacts,
                        'companies' => $companies,
                        'tickets' => $tickets,
                        'properties' => $properties,
                    ]
                ]
            ];
        });
    }

    /**
     * @OA\Post(
     *     path="/api/crm/deals",
     *     summary="Create New Deal",
     *     tags={"CRM - Deals"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","pipeline_id","stage_id"},
     *             @OA\Property(property="title", type="string", example="New Opportunity"),
     *             @OA\Property(property="amount", type="number", format="float", example=150000),
     *             @OA\Property(property="pipeline_id", type="integer", example=1),
     *             @OA\Property(property="stage_id", type="integer", example=2),
     *             @OA\Property(property="status", type="string", enum={"open","won","lost"}, example="open")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Deal created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'amount' => 'nullable|numeric',
            'pipeline_id' => 'required|exists:inmo_pipelines,id',
            'stage_id' => 'required|exists:inmo_pipeline_stages,id',
            'status' => 'nullable|in:open,won,lost,archived',
            'associations' => 'nullable|array',
        ]);

        $deal = Deal::create(array_merge($validated, [
            'owner_id' => Auth::id(),
            'status' => $validated['status'] ?? 'open',
            // 'currency' => default?
        ]));

        if (!empty($validated['associations'])) {
            foreach ($validated['associations'] as $assoc) {
                if (isset($assoc['type'], $assoc['id'])) {
                    \App\Models\Association::create([
                        'object_type_a' => 'deal',
                        'object_id_a' => $deal->id,
                        'object_type_b' => \Illuminate\Support\Str::singular($assoc['type']),
                        'object_id_b' => $assoc['id'],
                    ]);
                }
            }
        }

        return response()->json(['data' => $deal], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/crm/deals/{id}",
     *     summary="Update Deal",
     *     tags={"CRM - Deals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="status", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);
        
        // Policy check usually handled by middleware or manually
        if ($deal->owner_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
             // abort(403); 
        }
        // Actually, let's useauthorize
        // $this->authorize('update', $deal); // Need Policy

        $validated = $request->validate([
            'title' => 'sometimes|string',
            'amount' => 'nullable|numeric',
            'pipeline_id' => 'sometimes|exists:inmo_pipelines,id',
            'stage_id' => 'sometimes|exists:inmo_pipeline_stages,id',
            'status' => 'sometimes|in:open,won,lost,archived',
        ]);

        $deal->update($validated);

        return response()->json(['data' => $deal]);
    }

    /**
     * @OA\Post(
     *     path="/api/crm/deals/{id}/stage",
     *     summary="Move Deal Stage",
     *     tags={"CRM - Deals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(@OA\Property(property="stage_id", type="integer"))
     *     ),
     *     @OA\Response(response=200, description="Stage updated")
     * )
     */
    public function moveStage(Request $request, Deal $deal)
    {
        $request->validate(['stage_id' => 'required|exists:inmo_pipeline_stages,id']);
        $deal->update(['stage_id' => $request->stage_id]);
        return response()->json(['message' => 'Deal moved to new stage', 'data' => $deal]);
    }

    /**
     * @OA\Post(
     *     path="/api/crm/deals/{id}/win",
     *     summary="Mark Deal as Won",
     *     tags={"CRM - Deals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deal Won")
     * )
     */
    public function markWon(Deal $deal)
    {
        // Find 'Closed Won' stage (probability = 100)
        $wonStage = \App\Models\PipelineStage::where('pipeline_id', $deal->pipeline_id)
            ->where('probability', 100)
            ->first();
            
        $updateData = ['status' => 'won'];
        if ($wonStage) {
            $updateData['stage_id'] = $wonStage->id;
        }
        
        $deal->update($updateData);
        return response()->json(['message' => 'Deal marked as Won', 'data' => $deal]);
    }

    /**
     * @OA\Post(
     *     path="/api/crm/deals/{id}/lose",
     *     summary="Mark Deal as Lost",
     *     tags={"CRM - Deals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(@OA\Property(property="lost_reason", type="string"))
     *     ),
     *     @OA\Response(response=200, description="Deal Lost")
     * )
     */
    public function markLost(Request $request, Deal $deal)
    {
        // Find 'Closed Lost' stage (probability = 0)
        $lostStage = \App\Models\PipelineStage::where('pipeline_id', $deal->pipeline_id)
            ->where('probability', 0)
            ->first();
            
        $updateData = ['status' => 'lost'];
        if ($lostStage) {
            $updateData['stage_id'] = $lostStage->id;
        }
        // Save reason in data
        if ($request->has('lost_reason')) {
            $data = $deal->data ?? [];
            $data['lost_reason'] = $request->lost_reason;
            $updateData['data'] = $data;
        }
        
        $deal->update($updateData);
        return response()->json(['message' => 'Deal marked as Lost', 'data' => $deal]);
    }

    /**
     * @OA\Get(
     *     path="/api/crm/deals/{id}/analytics",
     *     summary="Get Deal Analytics",
     *     tags={"CRM - Deals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Analytics data")
     * )
     */
    public function analytics(Deal $deal)
    {
        return response()->json(['data' => [
            'days_open' => $deal->created_at->diffInDays(now()),
            'stage_history' => [], // Stub: implementation would require auditing/history table
            'activities_count' => $deal->getAssociated(\App\Models\Activity::class)->count()
        ]]);
    }

    /**
     * @OA\Delete(
     *     path="/api/crm/deals/{id}",
     *     summary="Delete Deal",
     *     tags={"CRM - Deals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted")
     * )
     */
    public function destroy(Deal $deal)
    {
        // $this->authorize('delete', $deal);
        $deal->delete();
        return response()->json(['message' => 'Deal deleted']);
    }
}
