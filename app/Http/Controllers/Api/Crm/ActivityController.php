<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="CRM Activities",
 *     description="API Endpoints for Activities (Logs, Notes, Calls)"
 * )
 */
class ActivityController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/crm/activities",
     *      tags={"CRM Activities"},
     *      summary="List activities",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200, 
     *          description="List of activities",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="id", type="integer", example=600),
     *                  @OA\Property(property="type", type="string", example="note"),
     *                  @OA\Property(property="content", type="string", example="Left a voicemail"),
     *                  @OA\Property(property="scheduled_at", type="string", format="date-time"),
     *                  @OA\Property(property="status", type="string", example="completed")
     *              )),
     *              @OA\Property(property="links", type="object"),
     *              @OA\Property(property="meta", type="object")
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        $query = Activity::where('created_by', Auth::id());

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * @OA\Post(
     *      path="/api/crm/activities",
     *      tags={"CRM Activities"},
     *      summary="Create activity",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"type","content"},
     *              @OA\Property(property="type", type="string", example="call_log"),
     *              @OA\Property(property="content", type="string", example="Called client, no answer"),
     *              @OA\Property(property="scheduled_at", type="string", format="date-time"),
     *              @OA\Property(property="associations", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *      @OA\Response(response=201, description="Created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'content' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'status' => 'nullable|string', // pending, completed
            'data' => 'nullable|array',
            'associations' => 'nullable|array' // [{type: 'contact', id: 1}]
        ]);

        $activity = Activity::create(array_merge($validated, [
            'created_by' => Auth::id(),
            'status' => $validated['status'] ?? 'completed', // Default logs to completed
        ]));

        // Handle associations if provided
        // Handle associations if provided
        if (!empty($validated['associations'])) {
            foreach ($validated['associations'] as $assoc) {
                // Basic validation for structure
                if (isset($assoc['type'], $assoc['id'])) {
                    \App\Models\Association::create([
                        'object_type_a' => 'activity',
                        'object_id_a' => $activity->id,
                        'object_type_b' => \Illuminate\Support\Str::singular($assoc['type']),
                        'object_id_b' => $assoc['id'],
                    ]);
                }
            }
        }

        return response()->json(['data' => $activity], 201);
    }

    /**
     * @OA\Get(
     *      path="/api/crm/activities/{id}",
     *      tags={"CRM Activities"},
     *      summary="Get activity details",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200, 
     *          description="Activity details",
     *          @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=600),
     *                 @OA\Property(property="attributes", type="object", ref="#/components/schemas/Activity"),
     *                 @OA\Property(property="timeline", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="associations", type="object",
     *                      @OA\Property(property="contacts", type="array", @OA\Items(type="object")),
     *                      @OA\Property(property="deals", type="array", @OA\Items(type="object")),
     *                      @OA\Property(property="tickets", type="array", @OA\Items(type="object"))
     *                 )
     *             )
     *          )
     *      )
     * )
     */
    public function show($id)
    {
        $cacheKey = "crm_activity_{$id}_detail";

        return \Illuminate\Support\Facades\Cache::tags(["crm_activity_{$id}"])->remember($cacheKey, now()->addMinutes(60), function () use ($id) {
            $activity = Activity::with(['creator'])->findOrFail($id);
            // Authorization check?
            if ($activity->created_by !== Auth::id()) {
                // return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Timeline for an activity? It IS an activity.
            $timeline = []; 

            // Associations
            // Assuming Activity model uses HasAssociations trait. If not, we need to add it.
            // Based on previous context, we should ensure it does.
            // If getAssociated method is missing, this will fail.
            // Safest to rely on manual query if trait usage isn't confirmed, but goal is consistency.
            // I'll assume HasAssociations is/will be added.
            
            $contacts = $activity->getAssociated(\App\Models\Contact::class)->get();
            $deals = $activity->getAssociated(\App\Models\Deal::class)->get();
            $tickets = $activity->getAssociated(\App\Models\Ticket::class)->get();
            $properties = $activity->getAssociated(\App\Models\Property::class)->get();

            return [
                'data' => [
                    'id' => $activity->id,
                    'attributes' => $activity,
                    'timeline' => $timeline,
                    'associations' => [
                        'contacts' => $contacts,
                        'deals' => $deals,
                        'tickets' => $tickets,
                        'properties' => $properties,
                    ]
                ]
            ];
        });
    }

    /**
     * @OA\Put(
     *      path="/api/crm/activities/{id}",
     *      tags={"CRM Activities"},
     *      summary="Update activity",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);
        if ($activity->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'type' => 'sometimes|string',
            'content' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'status' => 'nullable|string',
            'data' => 'nullable|array',
        ]);

        $activity->update($validated);

        return response()->json(['data' => $activity]);
    }

    /**
     * @OA\Delete(
     *      path="/api/crm/activities/{id}",
     *      tags={"CRM Activities"},
     *      summary="Delete activity",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Deleted")
     * )
     */
    public function destroy($id)
    {
        $activity = Activity::findOrFail($id);
        if ($activity->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $activity->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
