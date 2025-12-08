<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    private function getAgentId()
    {
        $agent = Agent::where('user_id', Auth::id())->first();
        return $agent?->id;
    }

    public function index(Request $request)
    {
        $agentId = $this->getAgentId();
        if (!$agentId) {
            return response()->json(['message' => 'Agent profile not found'], 404);
        }

        $query = Activity::with(['client', 'lead'])
            ->where('agent_id', $agentId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('upcoming') && $request->upcoming) {
            $query->upcoming();
        }

        $activities = $query->orderBy('scheduled_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($activities);
    }

    public function store(Request $request)
    {
        $agentId = $this->getAgentId();
        if (!$agentId) {
            return response()->json(['message' => 'Agent profile not found'], 404);
        }

        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'lead_id' => 'nullable|exists:leads,id',
            'type' => 'required|string|in:visita,reunion,llamada,whatsapp,mensaje_app,agendado',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'status' => 'sometimes|string|in:pending,completed,cancelled',
            'data' => 'sometimes|array',
        ]);

        $activity = Activity::create([
            'agent_id' => $agentId,
            ...$validated,
        ]);

        return response()->json($activity->load(['client', 'lead']), 201);
    }

    public function show(Activity $activity)
    {
        $agentId = $this->getAgentId();
        if ($activity->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($activity->load(['client', 'lead']));
    }

    public function update(Request $request, Activity $activity)
    {
        $agentId = $this->getAgentId();
        if ($activity->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'client_id' => 'sometimes|nullable|exists:clients,id',
            'lead_id' => 'sometimes|nullable|exists:leads,id',
            'type' => 'sometimes|string|in:visita,reunion,llamada,whatsapp,mensaje_app,agendado',
            'title' => 'sometimes|string|max:255',
            'notes' => 'sometimes|nullable|string',
            'scheduled_at' => 'sometimes|nullable|date',
            'completed_at' => 'sometimes|nullable|date',
            'status' => 'sometimes|string|in:pending,completed,cancelled',
            'data' => 'sometimes|array',
        ]);

        $activity->update($validated);

        return response()->json($activity->load(['client', 'lead']));
    }

    public function complete(Activity $activity)
    {
        $agentId = $this->getAgentId();
        if ($activity->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $activity->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json($activity->load(['client', 'lead']));
    }

    public function destroy(Activity $activity)
    {
        $agentId = $this->getAgentId();
        if ($activity->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $activity->delete();

        return response()->json(['message' => 'Activity deleted successfully']);
    }
}
