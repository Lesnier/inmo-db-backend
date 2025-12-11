<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="CRM - Analytics",
 *     description="Statistical endpoints for Agent/Pipeline performance"
 * )
 */
class AgentAnalyticsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/crm/analytics/pipeline",
     *     summary="General Pipeline Metrics",
     *     description="Returns general pipeline stats. Suggested Chart: Scorecard/Key Metrics.",
     *     tags={"CRM - Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="pipeline_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="agent_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200, 
     *         description="Pipeline stats",
     *         @OA\JsonContent(
     *             @OA\Property(property="pipeline_volume", type="integer", example=15),
     *             @OA\Property(property="pipeline_value", type="number", example=2500000),
     *             @OA\Property(property="avg_deal_size", type="number", example=166666),
     *             @OA\Property(property="conversion_rate_percentage", type="number", example=35.5),
     *             @OA\Property(property="sales_cycle_length_days", type="number", example=45.2)
     *         )
     *     )
     * )
     */
    public function pipeline(Request $request)
    {
        $pipelineId = $request->input('pipeline_id'); // If null, all pipelines? Usually aggregation matches.
        $agentId = $request->input('agent_id') ?? Auth::id(); // Default to me
        
        $query = Deal::where('owner_id', $agentId);
        if ($pipelineId) {
            $query->where('pipeline_id', $pipelineId);
        }

        // Active Deals (Open)
        $activeQuery = clone $query;
        $activeDeals = $activeQuery->where('status', 'open')->get();
        
        $volume = $activeDeals->count();
        $value = $activeDeals->sum('amount');
        $avgDealSize = $volume > 0 ? $value / $volume : 0;

        // Conversion Rate (Won / (Won + Lost))
        $closedQuery = clone $query;
        $closedStats = $closedQuery->whereIn('status', ['won', 'lost'])
            ->selectRaw("
                count(*) as total,
                sum(case when status = 'won' then 1 else 0 end) as won
            ")->first();
            
        $conversionRate = $closedStats->total > 0 ? ($closedStats->won / $closedStats->total) * 100 : 0;

        // Sales Cycle Length (Avg days from created to won/lost)
        // For accurate "Speed of Sale", usually utilize Won deals only, or both.
        $cycleQuery = clone $query;
        $closedDeals = $cycleQuery->whereIn('status', ['won', 'lost'])
            ->get(['created_at', 'updated_at']);

        $avgCycle = $closedDeals->isNotEmpty() 
            ? $closedDeals->avg(fn($deal) => $deal->updated_at->diffInDays($deal->created_at)) 
            : 0;

        return response()->json(['data' => [
            'pipeline_volume' => $volume,
            'pipeline_value' => $value,
            'avg_deal_size' => $avgDealSize,
            'conversion_rate_percentage' => round($conversionRate, 2),
            'sales_cycle_length_days' => round($avgCycle ?? 0, 1)
        ]]);
    }

    /**
     * @OA\Get(
     *     path="/api/crm/analytics/stages",
     *     summary="Stage-specific Metrics (Funnel)",
     *     description="Returns stage distribution. Suggested Chart: Funnel Chart or Bar Chart.",
     *     tags={"CRM - Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="pipeline_id", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="agent_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200, 
     *         description="Stage distribution",
     *         @OA\JsonContent(
     *             @OA\Property(property="funnel", type="array", @OA\Items(
     *                 @OA\Property(property="stage_name", type="string"),
     *                 @OA\Property(property="deal_count", type="integer"),
     *                 @OA\Property(property="deal_value", type="number"),
     *                 @OA\Property(property="probability", type="integer"),
     *                 @OA\Property(property="avg_days_in_stage", type="number", description="Avg time in stage calculated from history"),
     *                 @OA\Property(property="chart_type_suggestion", type="string", example="funnel")
     *             )),
     *             @OA\Property(property="total_active_value", type="number")
     *         )
     *     )
     * )
     */
    public function stages(Request $request)
    {
        $request->validate(['pipeline_id' => 'required']);
        $pipelineId = $request->input('pipeline_id');
        $agentId = $request->input('agent_id') ?? Auth::id();

        // Get all stages for this pipeline ordered by position
        $stages = PipelineStage::where('pipeline_id', $pipelineId)->orderBy('position')->get();
        
        // Count deals in each stage for this agent
        $dealCounts = Deal::where('owner_id', $agentId)
            ->where('pipeline_id', $pipelineId)
            ->where('status', 'open') // usually funnel view shows active? Or all? Usually Active.
            ->select('stage_id', DB::raw('count(*) as count'), DB::raw('sum(amount) as value'))
            ->groupBy('stage_id')
            ->pluck('value', 'stage_id'); // Value? Or Count? let's get whole object.
            
        // Re-query nicely
        $dealStats = Deal::where('owner_id', $agentId)
            ->where('pipeline_id', $pipelineId)
            ->where('status', 'open')
            ->selectRaw('stage_id, count(*) as count, sum(amount) as value')
            ->groupBy('stage_id')
            ->get()
            ->keyBy('stage_id');

        // Stage-by-stage conversion? Requires history.
        // Calculate Avg Time in Stage (Days) using DealStageHistory
        $stageDurations = \App\Models\DealStageHistory::where('pipeline_id', $pipelineId)
             ->whereNotNull('duration_minutes')
             ->select('stage_id', DB::raw('AVG(duration_minutes) as avg_minutes'))
             ->groupBy('stage_id')
             ->pluck('avg_minutes', 'stage_id');

        $funnel = $stages->map(function($stage) use ($dealStats, $stageDurations) {
            $stats = $dealStats->get($stage->id);
            $avgMinutes = $stageDurations->get($stage->id) ?? 0;
            
            return [
                'stage_id' => $stage->id,
                'stage_name' => $stage->name,
                'position' => $stage->position,
                'probability' => $stage->probability,
                'deal_count' => $stats ? $stats->count : 0,
                'deal_value' => $stats ? $stats->value : 0,
                'avg_days_in_stage' => round($avgMinutes / 60 / 24, 1),
                'chart_type_suggestion' => 'funnel' // or 'bar'
            ];
        });
        
        return response()->json(['data' => [
            'funnel' => $funnel,
            'total_active_value' => $dealStats->sum('value')
        ]]);
    }

    /**
     * @OA\Get(
     *     path="/api/crm/analytics/forecast",
     *     summary="Revenue Forecast",
     *     description="Returns revenue forecast based on deal probabilities. Suggested Chart: Gauge Chart or Progress Bar.",
     *     tags={"CRM - Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="pipeline_id", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="agent_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200, 
     *         description="Forecast data",
     *         @OA\JsonContent(
     *             @OA\Property(property="forecasted_revenue", type="number"),
     *             @OA\Property(property="total_pipeline_value", type="number"),
     *             @OA\Property(property="target_revenue", type="number"),
     *             @OA\Property(property="pipeline_coverage_ratio", type="number", description="Ratio of pipeline value to target")
     *         )
     *     )
     * )
     */
    public function forecast(Request $request)
    {
        $request->validate(['pipeline_id' => 'required']);
        $pipelineId = $request->input('pipeline_id');
        $agentId = $request->input('agent_id') ?? Auth::id();
        $targetRevenue = $request->input('target', 100000); // Or fetch from Agent goals

        // Calculate Forecasted Revenue: Sum(Deal Amount * Stage Probability)
        $deals = Deal::where('owner_id', $agentId)
            ->where('pipeline_id', $pipelineId)
            ->where('status', 'open')
            ->with('stage') // Need probability
            ->get();

        $forecastedRevenue = $deals->sum(function($deal) {
            $prob = $deal->stage ? $deal->stage->probability : 0;
            return $deal->amount * ($prob / 100);
        });

        $totalPipelineValue = $deals->sum('amount');
        
        $coverageRatio = $targetRevenue > 0 ? ($totalPipelineValue / $targetRevenue) : 0;

        return response()->json(['data' => [
            'forecasted_revenue' => $forecastedRevenue,
            'total_pipeline_value' => $totalPipelineValue,
            'target_revenue' => $targetRevenue,
            'pipeline_coverage_ratio' => round($coverageRatio, 2),
            'deal_count' => $deals->count()
        ]]);
    }

    /**
     * @OA\Get(
     *     path="/api/crm/analytics/performance",
     *     summary="Agent Performance KPIs",
     *     description="Returns agent-specific performance stats. Suggested Chart: Bar Chart or KPI Cards.",
     *     tags={"CRM - Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="agent_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200, 
     *         description="Performance data",
     *         @OA\JsonContent(
     *             @OA\Property(property="won_deals_count", type="integer"),
     *             @OA\Property(property="total_revenue", type="number"),
     *             @OA\Property(property="activities_completed", type="integer")
     *         )
     *     )
     * )
     */
    public function performance(Request $request)
    {
        $agentId = $request->input('agent_id') ?? Auth::id();

        // 1. Closed Deals Count (Won)
        $wonDeals = Deal::where('owner_id', $agentId)->where('status', 'won')->count();

        // 2. Revenue Generated
        $revenue = Deal::where('owner_id', $agentId)->where('status', 'won')->sum('amount');

        // 3. Activities Count (This month?)
        $activities = \App\Models\Activity::where('created_by', $agentId)->count();
        
        // 4. Referral Rate (Stub? Need Contact Source field)
        // Assuming Contact has 'source' field.
        // $referrals = Contact::where('owner_id', $agentId)->where('source', 'referral')->count();
        
        return response()->json(['data' => [
            'won_deals_count' => $wonDeals,
            'total_revenue' => $revenue,
            'activities_completed' => $activities,
            // 'referral_count' => $referrals
        ]]);
    }
}
