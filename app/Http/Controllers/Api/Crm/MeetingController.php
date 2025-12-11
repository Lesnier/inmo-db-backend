<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;
use App\Services\CrmTimelineService;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="CRM Meetings",
 *     description="API Endpoints for Meeting Management"
 * )
 */
class MeetingController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/crm/meetings",
     *      tags={"CRM Meetings"},
     *      summary="List meetings",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200, 
     *          description="List of meetings",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="id", type="integer", example=400),
     *                  @OA\Property(property="subject", type="string", example="Zoom Review"),
     *                  @OA\Property(property="scheduled_at", type="string", format="date-time"),
     *                  @OA\Property(property="meeting_type", type="string", example="online")
     *              )),
     *              @OA\Property(property="links", type="object"),
     *              @OA\Property(property="meta", type="object")
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        $query = Meeting::where('created_by', Auth::id())
                    ->orWhere('host_id', Auth::id());

        if ($request->has('upcoming') && $request->upcoming) {
            $query->where('scheduled_at', '>=', now());
        }

        return response()->json($query->paginate(20));
    }

    /**
     * @OA\Post(
     *      path="/api/crm/meetings",
     *      tags={"CRM Meetings"},
     *      summary="Schedule meeting",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"subject","scheduled_at"},
     *              @OA\Property(property="subject", type="string", example="Client viewing"),
     *              @OA\Property(property="description", type="string"),
     *              @OA\Property(property="scheduled_at", type="string", format="date-time"),
     *              @OA\Property(property="duration_minutes", type="integer", example=60),
     *              @OA\Property(property="meeting_type", type="string", example="in_person"),
     *              @OA\Property(property="location", type="string", example="Office Room 302"),
     *              @OA\Property(property="host_id", type="integer", description="ID of the user hosting the meeting"),
     *              @OA\Property(property="data", type="object", description="Additional JSON data"),
     *              @OA\Property(property="associations", type="array", @OA\Items(
     *                  @OA\Property(property="type", type="string", example="contacts"),
     *                  @OA\Property(property="id", type="integer", example=1)
     *              ))
     *          )
     *      ),
     *      @OA\Response(response=201, description="Created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'required|date',
            'duration_minutes' => 'nullable|integer',
            'meeting_type' => 'required|string', // in_person, virtual, etc.
            'location' => 'nullable|string',
            'host_id' => 'nullable|exists:users,id', // User/Agent ID
            'data' => 'nullable|array',
            'associations' => 'nullable|array',
        ]);

        $meeting = Meeting::create(array_merge($validated, [
            'created_by' => Auth::id(),
            'host_id' => $validated['host_id'] ?? Auth::id(),
        ]));

        if (!empty($validated['associations'])) {
            foreach ($validated['associations'] as $assoc) {
                if (isset($assoc['type'], $assoc['id'])) {
                    \App\Models\Association::create([
                        'object_type_a' => 'meeting',
                        'object_id_a' => $meeting->id,
                        'object_type_b' => \Illuminate\Support\Str::singular($assoc['type']),
                        'object_id_b' => $assoc['id'],
                    ]);
                }
            }
        }

        return response()->json(['data' => $meeting], 201);
    }

    /**
     * @OA\Get(
     *      path="/api/crm/meetings/{id}",
     *      tags={"CRM Meetings"},
     *      summary="Get meeting details",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200, 
     *          description="Meeting details",
     *          @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=400),
     *                 @OA\Property(property="attributes", type="object", ref="#/components/schemas/Meeting"),
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
        $cacheKey = "crm_meeting_{$id}_detail";

        return \Illuminate\Support\Facades\Cache::tags(["crm_meeting_{$id}"])->remember($cacheKey, now()->addMinutes(60), function () use ($id) {
            $meeting = Meeting::findOrFail($id);
            
            if ($meeting->created_by !== Auth::id() && $meeting->host_id !== Auth::id()) {
                // return response()->json(['message' => 'Unauthorized'], 403);
            }
            
            $timeline = []; // Future: comments/notes on meeting

            $contacts = $meeting->getAssociated(\App\Models\Contact::class)->get();
            $deals = $meeting->getAssociated(\App\Models\Deal::class)->get();
            $tickets = $meeting->getAssociated(\App\Models\Ticket::class)->get();
            $properties = $meeting->getAssociated(\App\Models\Property::class)->get();

            return [
                'data' => [
                    'id' => $meeting->id,
                    'attributes' => $meeting->load('host'),
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
     *      path="/api/crm/meetings/{id}",
     *      tags={"CRM Meetings"},
     *      summary="Update meeting",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);
        
        if ($meeting->created_by !== Auth::id()) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'subject' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'sometimes|date',
            'duration_minutes' => 'nullable|integer',
            'meeting_type' => 'sometimes|string',
            'location' => 'nullable|string',
            'host_id' => 'nullable|exists:users,id',
            'data' => 'nullable|array',
        ]);

        $meeting->update($validated);

        return response()->json($meeting);
    }

    /**
     * @OA\Delete(
     *      path="/api/crm/meetings/{id}",
     *      tags={"CRM Meetings"},
     *      summary="Cancel/Delete meeting",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Deleted")
     * )
     */
    public function destroy($id)
    {
        $meeting = Meeting::findOrFail($id);
        if ($meeting->created_by !== Auth::id()) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }
        $meeting->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
