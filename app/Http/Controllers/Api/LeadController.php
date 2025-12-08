<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Lead;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
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

        $query = Lead::with(['property', 'activities'])
            ->where('agent_id', $agentId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $leads = $query->orderBy('created_at', 'desc')->get();

        return response()->json($leads);
    }

    public function store(Request $request)
    {
        $agentId = $this->getAgentId();
        if (!$agentId) {
            return response()->json(['message' => 'Agent profile not found'], 404);
        }

        $validated = $request->validate([
            'property_id' => 'nullable|exists:real_estate_properties,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'message' => 'nullable|string',
            'status' => 'sometimes|string|in:nuevo,contactado,visita_agendada,negociacion,cierre_ganado,cierre_perdido',
            'source' => 'sometimes|string|max:255',
            'data' => 'sometimes|array',
        ]);

        $lead = Lead::create([
            'agent_id' => $agentId,
            ...$validated,
        ]);

        return response()->json($lead->load(['property', 'activities']), 201);
    }

    public function show(Lead $lead)
    {
        $agentId = $this->getAgentId();
        if ($lead->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($lead->load(['property', 'activities']));
    }

    public function update(Request $request, Lead $lead)
    {
        $agentId = $this->getAgentId();
        if ($lead->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'property_id' => 'sometimes|nullable|exists:real_estate_properties,id',
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => 'sometimes|nullable|string|max:50',
            'message' => 'sometimes|nullable|string',
            'status' => 'sometimes|string|in:nuevo,contactado,visita_agendada,negociacion,cierre_ganado,cierre_perdido',
            'source' => 'sometimes|string|max:255',
            'data' => 'sometimes|array',
        ]);

        $lead->update($validated);

        return response()->json($lead->load(['property', 'activities']));
    }

    public function convertToClient(Request $request, Lead $lead)
    {
        $agentId = $this->getAgentId();
        if ($lead->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'data' => 'sometimes|array',
        ]);

        $client = Client::create([
            'agent_id' => $agentId,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'status' => 'active',
            'data' => array_merge($lead->data ?? [], $validated['data'] ?? [], [
                'converted_from_lead_id' => $lead->id,
                'converted_at' => now(),
            ]),
        ]);

        return response()->json([
            'client' => $client,
            'lead' => $lead,
        ], 201);
    }

    public function destroy(Lead $lead)
    {
        $agentId = $this->getAgentId();
        if ($lead->agent_id !== $agentId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $lead->delete();

        return response()->json(['message' => 'Lead deleted successfully']);
    }
}
