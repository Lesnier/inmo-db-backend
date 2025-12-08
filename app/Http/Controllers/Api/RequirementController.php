<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Requirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequirementController extends Controller
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

        $query = Requirement::with(['client'])
            ->where('agent_id', $agentId);

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requirements = $query->orderBy('created_at', 'desc')->get();

        return response()->json($requirements);
    }

    public function store(Request $request)
    {
        $agentId = $this->getAgentId();
        if (!$agentId) {
            return response()->json(['message' => 'Agent profile not found'], 404);
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|string|in:active,fulfilled,cancelled',
            'data' => 'sometimes|array',
        ]);

        $requirement = Requirement::create([
            'agent_id' => $agentId,
            ...$validated,
        ]);

        return response()->json($requirement->load('client'), 201);
    }

    public function show(Requirement $requirement)
    {
        $agentId = $this->getAgentId();
        if ($requirement->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($requirement->load('client'));
    }

    public function update(Request $request, Requirement $requirement)
    {
        $agentId = $this->getAgentId();
        if ($requirement->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'client_id' => 'sometimes|exists:clients,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|string|in:active,fulfilled,cancelled',
            'data' => 'sometimes|array',
        ]);

        $requirement->update($validated);

        return response()->json($requirement->load('client'));
    }

    public function destroy(Requirement $requirement)
    {
        $agentId = $this->getAgentId();
        if ($requirement->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $requirement->delete();

        return response()->json(['message' => 'Requirement deleted successfully']);
    }
}
