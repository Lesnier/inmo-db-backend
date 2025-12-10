<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Agents",
 *     description="API Endpoints for Agent Profile & Stats"
 * )
 */
class AgentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/agent/profile",
     *     summary="Get Agent Profile",
     *     tags={"Agents"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Profile not found")
     * )
     */
    public function show()
    {
        $agent = Agent::with(['user', 'plan'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$agent) {
            return response()->json(['message' => 'Agent profile not found'], 404);
        }

        return response()->json($agent);
    }

    /**
     * @OA\Put(
     *     path="/api/agent/profile",
     *     summary="Update Agent Profile",
     *     tags={"Agents"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"pending","approved","suspended"}),
     *             @OA\Property(property="onboarding_status", type="string", enum={"incomplete","complete"}),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Profile updated")
     * )
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'status' => 'sometimes|string|in:pending,approved,suspended',
            'onboarding_status' => 'sometimes|string|in:incomplete,complete',
            'plan_id' => 'sometimes|nullable|exists:real_estate_plans,id',
            'data' => 'sometimes|array',
        ]);

        $agent = Agent::where('user_id', Auth::id())->first();

        if (!$agent) {
            // Create agent profile if it doesn't exist
            $agent = Agent::create([
                'user_id' => Auth::id(),
                'status' => 'pending',
                'onboarding_status' => 'incomplete',
                'data' => $validated['data'] ?? [],
            ]);
        } else {
            $agent->update($validated);
        }

        return response()->json($agent->load(['user', 'plan']));
    }

    /**
     * @OA\Get(
     *     path="/api/agent/stats",
     *     summary="Get Agent Statistics",
     *     tags={"Agents"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function stats()
    {
        $agent = Agent::where('user_id', Auth::id())->first();

        if (!$agent) {
            return response()->json(['message' => 'Agent profile not found'], 404);
        }

        $stats = [
            'total_properties' => $agent->properties()->count(),
            'published_properties' => $agent->properties()->where('status', 'published')->count(),
            'total_leads' => $agent->leads()->count(),
            'new_leads' => $agent->leads()->where('lead_status', 'new')->count(),
            'total_clients' => $agent->clients()->count(),
            'total_activities' => $agent->activities()->count(),
            'upcoming_activities' => $agent->activities()
                ->whereIn('type', ['meeting', 'task'])
                ->where('scheduled_at', '>', now())
                ->where('status', 'pending')
                ->count(),
            'priority_activities' => $agent->activities()
                ->where('status', 'pending')
                ->whereJsonContains('data->priority', 'high')
                ->count(),
            'active_requirements' => $agent->tickets()->where('status', 'open')->count(),
            'total_opportunities' => $agent->deals()->count(), 
            'active_proposals' => $agent->deals()->whereHas('stage', function($q) {
                $q->where('name', 'like', '%Proposal%');
            })->count(),
            'won_deals' => $agent->deals()->where('status', 'won')->count(),
        ];

        return response()->json($stats);
    }
}
