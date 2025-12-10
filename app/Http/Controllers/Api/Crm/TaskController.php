<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Traits\HasAssociations;
use App\Services\CrmTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="CRM Tasks",
 *     description="API Endpoints for Task Management"
 * )
 */
class TaskController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/crm/tasks",
     *      tags={"CRM Tasks"},
     *      summary="List tasks",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200, 
     *          description="List of tasks",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="id", type="integer", example=300),
     *                  @OA\Property(property="title", type="string", example="Follow up Call"),
     *                  @OA\Property(property="status", type="string", example="pending"),
     *                  @OA\Property(property="priority", type="string", example="medium"),
     *                  @OA\Property(property="due_date", type="string", format="date-time")
     *              )),
     *              @OA\Property(property="links", type="object"),
     *              @OA\Property(property="meta", type="object")
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        // Tasks created by user OR assigned to user
        $query = Task::where(function($q) {
            $q->where('created_by', Auth::id())
              ->orWhere('assigned_to', Auth::id());
        });

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * @OA\Post(
     *      path="/api/crm/tasks",
     *      tags={"CRM Tasks"},
     *      summary="Create task",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"title"},
     *              @OA\Property(property="title", type="string", example="Follow up call"),
     *              @OA\Property(property="description", type="string"),
     *              @OA\Property(property="due_date", type="string", format="date-time"),
     *              @OA\Property(property="priority", type="string", enum={"low","medium","high"}, example="medium"),
     *              @OA\Property(property="assigned_to", type="integer", description="User ID to assign")
     *          )
     *      ),
     *      @OA\Response(response=201, description="Created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'nullable|string|in:open,in_progress,completed,canceled',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'data' => 'nullable|array',
        ]);

        $task = Task::create(array_merge($validated, [
            'created_by' => Auth::id(),
            'status' => $validated['status'] ?? 'open',
            'priority' => $validated['priority'] ?? 'medium',
        ]));

        return response()->json($task, 201);
    }

    /**
     * @OA\Get(
     *      path="/api/crm/tasks/{id}",
     *      tags={"CRM Tasks"},
     *      summary="Get task details",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200, 
     *          description="Task details",
     *          @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=300),
     *                 @OA\Property(property="attributes", type="object", ref="#/components/schemas/Task"),
     *                 @OA\Property(property="timeline", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="associations", type="object",
     *                      @OA\Property(property="contacts", type="array", @OA\Items(type="object")),
     *                      @OA\Property(property="deals", type="array", @OA\Items(type="object")),
     *                      @OA\Property(property="tickets", type="array", @OA\Items(type="object")),
     *                      @OA\Property(property="properties", type="array", @OA\Items(type="object"))
     *                 )
     *             )
     *          )
     *      )
     * )
     */
    public function show($id)
    {
        $cacheKey = "crm_task_{$id}_detail";

        return \Illuminate\Support\Facades\Cache::tags(["crm_task_{$id}"])->remember($cacheKey, now()->addMinutes(60), function () use ($id) {
            $task = Task::with(['creator', 'assignedUser'])->findOrFail($id);
            
            // Authorization
            if ($task->created_by !== Auth::id() && $task->assigned_to !== Auth::id()) {
                // return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Timeline (Comments, Sub-tasks?) - Task itself is usually an activity, but might have comments
            // For now, empty timeline or comments if we strictly follow the schema
            $timeline = []; 

            // Associations
            $contacts = $task->getAssociated(\App\Models\Contact::class)->get();
            $deals = $task->getAssociated(\App\Models\Deal::class)->get();
            $tickets = $task->getAssociated(\App\Models\Ticket::class)->get();
            $properties = $task->getAssociated(\App\Models\Property::class)->get();

            return [
                'data' => [
                    'id' => $task->id,
                    'attributes' => $task,
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
     *      path="/api/crm/tasks/{id}",
     *      tags={"CRM Tasks"},
     *      summary="Update task",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        if ($task->created_by !== Auth::id() && $task->assigned_to !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'sometimes|string|in:open,in_progress,completed,canceled',
            'priority' => 'sometimes|string|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'data' => 'nullable|array',
        ]);

        $task->update($validated);

        return response()->json($task);
    }

    /**
     * @OA\Delete(
     *      path="/api/crm/tasks/{id}",
     *      tags={"CRM Tasks"},
     *      summary="Delete task",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Deleted")
     * )
     */
    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        if ($task->created_by !== Auth::id()) {
             // Only creator can delete? Or admin.
             return response()->json(['message' => 'Unauthorized'], 403);
        }
        $task->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
