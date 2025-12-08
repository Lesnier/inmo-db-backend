<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    private function getAgentId()
    {
        $agent = Agent::where('user_id', Auth::id())->first();
        return $agent?->id;
    }

    public function index()
    {
        $agentId = $this->getAgentId();
        if (!$agentId) {
            return response()->json(['message' => 'Agent profile not found'], 404);
        }

        $clients = Client::with(['requirements', 'proposals', 'activities'])
            ->where('agent_id', $agentId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($clients);
    }

    public function store(Request $request)
    {
        $agentId = $this->getAgentId();
        if (!$agentId) {
            return response()->json(['message' => 'Agent profile not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'status' => 'sometimes|string|in:active,inactive',
            'data' => 'sometimes|array',
        ]);

        $client = Client::create([
            'agent_id' => $agentId,
            ...$validated,
        ]);

        return response()->json($client->load(['requirements', 'proposals', 'activities']), 201);
    }

    public function show(Client $client)
    {
        $agentId = $this->getAgentId();
        if ($client->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($client->load(['requirements', 'proposals', 'activities']));
    }

    public function update(Request $request, Client $client)
    {
        $agentId = $this->getAgentId();
        if ($client->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => 'sometimes|nullable|string|max:50',
            'status' => 'sometimes|string|in:active,inactive',
            'data' => 'sometimes|array',
        ]);

        $client->update($validated);

        return response()->json($client->load(['requirements', 'proposals', 'activities']));
    }

    public function destroy(Client $client)
    {
        $agentId = $this->getAgentId();
        if ($client->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $client->delete();

        return response()->json(['message' => 'Client deleted successfully']);
    }
}
