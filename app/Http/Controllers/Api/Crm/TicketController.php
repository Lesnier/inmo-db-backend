<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\CrmTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="CRM - Tickets",
 *     description="API Endpoints for managing Support Tickets/Requirements"
 * )
 */
class TicketController extends Controller
{
    protected $timelineService;

    public function __construct(CrmTimelineService $timelineService)
    {
        $this->timelineService = $timelineService;
    }

    /**
     * @OA\Get(
     *     path="/api/crm/tickets",
     *     summary="List User's Tickets",
     *     tags={"CRM - Tickets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="page", in="query", description="Page number", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", description="Filter by Status", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200, 
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=200),
     *                 @OA\Property(property="title", type="string", example="Urgent Support"),
     *                 @OA\Property(property="status", type="string", example="new"),
     *                 @OA\Property(property="priority", type="string", example="high")
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
        
        $cacheKey = "user_{$userId}_tickets_list_p{$page}_{$filters}";

        return \Illuminate\Support\Facades\Cache::tags(["user_{$userId}_tickets"])->remember($cacheKey, now()->addMinutes(60), function () use ($request) {
            $query = Auth::user()->tickets()->with(['pipeline', 'stage']);
    
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
    
            return $query->paginate(20);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/crm/tickets/{id}",
     *     summary="Get Ticket Details",
     *     tags={"CRM - Tickets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Ticket ID", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200, 
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=200),
     *                 @OA\Property(property="attributes", type="object", ref="#/components/schemas/Ticket"),
     *                 @OA\Property(property="timeline", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="associations", type="object",
     *                      @OA\Property(property="contacts", type="array", @OA\Items(type="object")),
     *                      @OA\Property(property="companies", type="array", @OA\Items(type="object")),
     *                      @OA\Property(property="deals", type="array", @OA\Items(type="object"))
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Ticket not found")
     * )
     */
    public function show($id)
    {
        $cacheKey = "crm_ticket_{$id}_detail";

        return \Illuminate\Support\Facades\Cache::tags(["crm_ticket_{$id}"])->remember($cacheKey, now()->addMinutes(60), function () use ($id) {
            $ticket = Ticket::findOrFail($id);
            
            // Authorization check...
    
            $timeline = $this->timelineService->getTimeline($ticket);
    
            $contacts = $ticket->getAssociated(\App\Models\Contact::class)->get();
            $companies = $ticket->getAssociated(\App\Models\Company::class)->get();
            $deals = $ticket->getAssociated(\App\Models\Deal::class)->get();
            
            return [
                'data' => [
                    'id' => $ticket->id,
                    'attributes' => $ticket->load(['pipeline', 'stage', 'owner']),
                    'timeline' => $timeline,
                    'associations' => [
                        'contacts' => $contacts,
                        'companies' => $companies,
                        'deals' => $deals,
                    ]
                ]
            ];
        });
    }
    
    /**
     * @OA\Post(
     *     path="/api/crm/tickets",
     *     summary="Create New Ticket",
     *     tags={"CRM - Tickets"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","pipeline_id","stage_id"},
     *             @OA\Property(property="title", type="string", example="New Requirement"),
     *             @OA\Property(property="description", type="string", example="Client needs..."),
     *             @OA\Property(property="pipeline_id", type="integer", example=1),
     *             @OA\Property(property="stage_id", type="integer", example=2),
     *             @OA\Property(property="priority", type="string", enum={"low","medium","high"}, example="medium"),
     *             @OA\Property(property="associations", type="array", @OA\Items(
     *                  @OA\Property(property="type", type="string", example="contacts"),
     *                  @OA\Property(property="id", type="integer", example=1)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Ticket created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'pipeline_id' => 'required|exists:inmo_pipelines,id',
            'stage_id' => 'required|exists:inmo_pipeline_stages,id',
            'priority' => 'nullable|string',
            'associations' => 'nullable|array',
        ]);

        $ticket = Ticket::create(array_merge($validated, [
            'owner_id' => Auth::id(),
        ]));

        if (!empty($validated['associations'])) {
            foreach ($validated['associations'] as $assoc) {
                if (isset($assoc['type'], $assoc['id'])) {
                    \App\Models\Association::create([
                        'object_type_a' => 'ticket',
                        'object_id_a' => $ticket->id,
                        'object_type_b' => \Illuminate\Support\Str::singular($assoc['type']),
                        'object_id_b' => $assoc['id'],
                    ]);
                }
            }
        }

        return response()->json(['data' => $ticket], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/crm/tickets/{id}",
     *     summary="Update Ticket",
     *     tags={"CRM - Tickets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="priority", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'sometimes|string',
            'description' => 'nullable|string',
            'priority' => 'sometimes|string',
            'status' => 'sometimes|string',
            'pipeline_id' => 'sometimes|exists:inmo_pipelines,id',
            'stage_id' => 'sometimes|exists:inmo_pipeline_stages,id',
        ]);

        $ticket->update($validated);
        return response()->json(['data' => $ticket]);
    }

    /**
     * @OA\Delete(
     *     path="/api/crm/tickets/{id}",
     *     summary="Delete Ticket",
     *     tags={"CRM - Tickets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted")
     * )
     */
    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();
        return response()->json(['message' => 'Ticket deleted']);
    }

    /**
     * @OA\Post(
     *     path="/api/crm/tickets/{id}/assign",
     *     summary="Assign Ticket",
     *     tags={"CRM - Tickets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(@OA\Property(property="owner_id", type="integer"))
     *     ),
     *     @OA\Response(response=200, description="Assigned")
     * )
     */
    public function assign(Request $request, $id)
    {
        $request->validate(['owner_id' => 'required|exists:users,id']);
        $ticket = Ticket::findOrFail($id);
        $ticket->update(['owner_id' => $request->owner_id]);
        return response()->json(['message' => 'Ticket assigned successfully', 'data' => $ticket]);
    }

    /**
     * @OA\Post(
     *     path="/api/crm/tickets/{id}/resolve",
     *     summary="Resolve Ticket",
     *     tags={"CRM - Tickets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Resolved")
     * )
     */
    public function resolve(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        // Find 'Resolved' or 'Closed' stage
        // Check Ticket Pipeline Type
        $resolvedStage = \App\Models\PipelineStage::where('pipeline_id', $ticket->pipeline_id)
             ->where('name', 'like', '%Resolved%')
             ->first();

        $updateData = ['status' => 'resolved'];
        if ($resolvedStage) {
            $updateData['stage_id'] = $resolvedStage->id;
        } else {
             // Fallback to max position (closed)
             $lastStage = \App\Models\PipelineStage::where('pipeline_id', $ticket->pipeline_id)
                ->orderBy('position', 'desc')
                ->first();
             if ($lastStage) $updateData['stage_id'] = $lastStage->id;
        }

        $ticket->update($updateData);
        return response()->json(['message' => 'Ticket resolved', 'data' => $ticket]);
    }

    /**
     * @OA\Get(
     *     path="/api/crm/tickets/{id}/analytics",
     *     summary="Get Ticket Analytics",
     *     tags={"CRM - Tickets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Analytics data")
     * )
     */
    public function analytics($id)
    {
        $ticket = Ticket::findOrFail($id);
        return response()->json(['data' => [
             'days_open' => $ticket->created_at->diffInDays(now()),
             'activities_count' => $ticket->getAssociated(\App\Models\Activity::class)->count(),
        ]]);
    }
}
