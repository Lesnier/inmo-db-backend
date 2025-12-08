<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProposalController extends Controller
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

        $query = Proposal::with(['client', 'properties'])
            ->where('agent_id', $agentId);

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $proposals = $query->orderBy('created_at', 'desc')->get();

        return response()->json($proposals);
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
            'property_ids' => 'required|array',
            'property_ids.*' => 'exists:real_estate_properties,id',
            'data' => 'sometimes|array',
        ]);

        $proposal = Proposal::create([
            'agent_id' => $agentId,
            'client_id' => $validated['client_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'data' => $validated['data'] ?? [],
        ]);

        // Attach properties to proposal
        $properties = [];
        foreach ($validated['property_ids'] as $index => $propertyId) {
            $properties[$propertyId] = ['order' => $index];
        }
        $proposal->properties()->attach($properties);

        return response()->json($proposal->load(['client', 'properties']), 201);
    }

    public function show(Proposal $proposal)
    {
        $agentId = $this->getAgentId();
        if ($proposal->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($proposal->load(['client', 'properties']));
    }

    public function showByToken(string $token)
    {
        $proposal = Proposal::with(['client', 'properties', 'agent.user'])
            ->where('share_token', $token)
            ->first();

        if (!$proposal) {
            return response()->json(['message' => 'Proposal not found'], 404);
        }

        // Update status to 'vista' if it was 'enviada'
        if ($proposal->status === 'enviada') {
            $proposal->update(['status' => 'vista']);
        }

        return response()->json($proposal);
    }

    public function update(Request $request, Proposal $proposal)
    {
        $agentId = $this->getAgentId();
        if ($proposal->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'client_id' => 'sometimes|exists:clients,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|string|in:enviada,vista,aceptada,rechazada',
            'property_ids' => 'sometimes|array',
            'property_ids.*' => 'exists:real_estate_properties,id',
            'data' => 'sometimes|array',
        ]);

        $proposal->update($validated);

        // Update properties if provided
        if (isset($validated['property_ids'])) {
            $properties = [];
            foreach ($validated['property_ids'] as $index => $propertyId) {
                $properties[$propertyId] = ['order' => $index];
            }
            $proposal->properties()->sync($properties);
        }

        return response()->json($proposal->load(['client', 'properties']));
    }

    public function destroy(Proposal $proposal)
    {
        $agentId = $this->getAgentId();
        if ($proposal->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $proposal->delete();

        return response()->json(['message' => 'Proposal deleted successfully']);
    }
}
