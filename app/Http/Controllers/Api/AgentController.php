<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentController extends Controller
{
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

    public function stats()
    {
        $agent = Agent::where('user_id', Auth::id())->first();

        if (!$agent) {
            return response()->json(['message' => 'Agent profile not found'], 404);
        }

        $stats = [
            'total_properties' => $agent->properties()->count(),
            'published_properties' => $agent->properties()->published()->count(),
            'total_leads' => $agent->leads()->count(),
            'new_leads' => $agent->leads()->new()->count(),
            'total_clients' => $agent->clients()->active()->count(),
            'total_activities' => $agent->activities()->count(),
            'pending_activities' => $agent->activities()->pending()->count(),
            'upcoming_activities' => $agent->activities()->upcoming()->count(),
            'active_requirements' => $agent->requirements()->active()->count(),
            'total_proposals' => $agent->proposals()->count(),
        ];

        return response()->json($stats);
    }
}
