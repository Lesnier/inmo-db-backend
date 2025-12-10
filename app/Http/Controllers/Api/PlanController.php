<?php

namespace App\Http\Controllers\Api;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class PlanController extends Controller
{
    // GET /api/real-estate/plans (public)
    public function index(): JsonResponse
    {
        $plans = Plan::all();
        return response()->json($plans);
    }

    // GET /api/real-estate/plans/{id} (public)
    public function show(Plan $plan): JsonResponse
    {
        return response()->json($plan);
    }

    // POST /api/real-estate/plans (admin)
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:140',
            'price' => 'required|numeric|min:0',
            'period_days' => 'nullable|integer|min:1',
            'data' => 'nullable|array',
        ]);

        $plan = Plan::create($validated);
        return response()->json($plan, 201);
    }

    // PUT /api/real-estate/plans/{id} (admin)
    public function update(Request $request, Plan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:140',
            'price' => 'sometimes|numeric|min:0',
            'period_days' => 'sometimes|integer|min:1',
            'data' => 'sometimes|array',
        ]);

        $plan->update($validated);
        return response()->json($plan);
    }

    // DELETE /api/real-estate/plans/{id} (admin)
    public function destroy(Plan $plan): JsonResponse
    {
        $plan->delete();
        return response()->json(['message' => 'Plan deleted']);
    }
}
