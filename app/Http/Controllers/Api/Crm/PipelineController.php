<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="CRM - Pipelines",
 *     description="Manage Deal/Ticket Pipelines and Stages"
 * )
 */
class PipelineController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/crm/pipelines",
     *     summary="List Pipelines",
     *     tags={"CRM - Pipelines"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of pipelines with stages",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Pipeline"))
     *     )
     * )
     */
    public function index()
    {
        $userId = auth()->id();
        $pipelines = Pipeline::where(function($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhereNull('user_id');
            })
            ->with(['stages' => function($q) {
                $q->orderBy('position', 'asc');
            }])
            ->get();
            
        return response()->json(['data' => $pipelines]);
    }

    /**
     * @OA\Post(
     *     path="/api/crm/pipelines",
     *     summary="Create Pipeline",
     *     tags={"CRM - Pipelines"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "entity_type"},
     *             @OA\Property(property="name", type="string", example="Sales Pipeline"),
     *             @OA\Property(property="entity_type", type="string", enum={"deal","ticket"}, example="deal"),
     *             @OA\Property(property="stages", type="array", @OA\Items(
     *                  @OA\Property(property="name", type="string", example="New Lead"),
     *                  @OA\Property(property="probability", type="integer", example=10),
     *                  @OA\Property(property="position", type="integer", example=0)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Pipeline created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entity_type' => 'required|in:deal,ticket',
            'stages' => 'nullable|array',
            'stages.*.name' => 'required|string',
            'stages.*.probability' => 'nullable|integer|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            $pipeline = Pipeline::create([
                'name' => $validated['name'],
                'entity_type' => $validated['entity_type'],
                'user_id' => auth()->id() // Assigned to current user
            ]);

            if (!empty($validated['stages'])) {
                foreach ($validated['stages'] as $index => $stage) {
                    $pipeline->stages()->create([
                        'name' => $stage['name'],
                        'probability' => $stage['probability'] ?? 0,
                        'position' => $index
                    ]);
                }
            }
            DB::commit();
            return response()->json(['data' => $pipeline->load('stages')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating pipeline', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/crm/pipelines/{id}",
     *     summary="Get Pipeline Details",
     *     tags={"CRM - Pipelines"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Pipeline details")
     * )
     */
    public function show($id)
    {
        $pipeline = Pipeline::with(['stages' => function($q) {
            $q->orderBy('position', 'asc');
        }])->findOrFail($id);
        return response()->json(['data' => $pipeline]);
    }

    /**
     * @OA\Put(
     *     path="/api/crm/pipelines/{id}",
     *     summary="Update Pipeline",
     *     tags={"CRM - Pipelines"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="stages", type="array", @OA\Items(
     *                  @OA\Property(property="id", type="integer", nullable=true, description="If present updates existing stage, else creates new"),
     *                  @OA\Property(property="name", type="string"),
     *                  @OA\Property(property="probability", type="integer"),
     *                  @OA\Property(property="position", type="integer")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(Request $request, $id)
    {
        $pipeline = Pipeline::findOrFail($id);

        // Security Check: Only Owner or Global Admin (if we had roles) can edit
        // For now: Global pipelines (user_id=null) can only be edited by...? Let's say anyone for now or restrict?
        // Logic: If I am the owner, I can edit. If it's global, maybe simple protection?
        // Refined Logic (User Request): "Mis pipelines O los globales". Usually global are admin only.
        // Assuming strict ownership: only if user_id == auth()->id()
        
        if ($pipeline->user_id && $pipeline->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'stages' => 'nullable|array',
            'stages.*.id' => 'nullable|integer|exists:inmo_pipeline_stages,id',
            'stages.*.name' => 'required_with:stages|string',
            'stages.*.probability' => 'nullable|integer',
            'stages.*.position' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['name'])) {
                $pipeline->update(['name' => $validated['name']]);
            }

            if (isset($validated['stages'])) {
                foreach ($validated['stages'] as $stageData) {
                    if (isset($stageData['id'])) {
                        $stage = PipelineStage::find($stageData['id']);
                        if ($stage && $stage->pipeline_id == $pipeline->id) {
                            $stage->update($stageData);
                        }
                    } else {
                        $pipeline->stages()->create($stageData);
                    }
                }
            }
            DB::commit();
            return response()->json(['data' => $pipeline->load('stages')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating pipeline'], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/crm/pipelines/{id}",
     *     summary="Delete Pipeline",
     *     tags={"CRM - Pipelines"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted")
     * )
     */
    public function destroy($id)
    {
        $pipeline = Pipeline::findOrFail($id);
        
        if ($pipeline->user_id && $pipeline->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Constraint: Can't delete if Deals exist?
        $hasDeals = \App\Models\Deal::where('pipeline_id', $pipeline->id)->exists();
        $hasTickets = \App\Models\Ticket::where('pipeline_id', $pipeline->id)->exists();
        
        if ($hasDeals || $hasTickets) {
            return response()->json(['message' => 'Cannot delete pipeline with existing deals or tickets'], 400);
        }

        $pipeline->delete(); // Stages cascade delete if migration is set properly
        return response()->json(['message' => 'Pipeline deleted']);
    }
}
