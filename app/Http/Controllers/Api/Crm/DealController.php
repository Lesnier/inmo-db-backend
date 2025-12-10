<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Services\CrmTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DealController extends Controller
{
    protected $timelineService;

    public function __construct(CrmTimelineService $timelineService)
    {
        $this->timelineService = $timelineService;
    }

    /**
     * List Deals (Owned by User).
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $page = $request->input('page', 1);
        $filters = md5(json_encode($request->all()));
        
        $cacheKey = "user_{$userId}_deals_list_p{$page}_{$filters}";

        return \Illuminate\Support\Facades\Cache::tags(["user_{$userId}_deals"])->remember($cacheKey, now()->addMinutes(60), function () use ($request) {
            $query = Auth::user()->deals()->with(['pipeline', 'stage']);

            // Optional filters
            if ($request->has('pipeline_id')) {
                $query->where('pipeline_id', $request->pipeline_id);
            }

            return $query->paginate(20);
        });
    }

    /**
     * Show Deal Detail (3-Column Layout).
     */
    public function show($id)
    {
        $cacheKey = "crm_deal_{$id}_detail";

        return \Illuminate\Support\Facades\Cache::tags(["crm_deal_{$id}"])->remember($cacheKey, now()->addMinutes(60), function () use ($id) {
            $deal = Deal::findOrFail($id);
            
            // Authorization check (simple ownership or permission)
            // if ($deal->owner_id !== Auth::id()) { abort(403); }
    
            // 1. Info (Attributes) - Already in $deal
    
            // 2. Timeline (Activities, Tasks, Meetings)
            $timeline = $this->timelineService->getTimeline($deal);
    
            // 3. Associations (Contacts, Companies, Tickets)
            // Using getAssociated helper from trait
            $contacts = $deal->getAssociated(\App\Models\Contact::class)->get();
            $companies = $deal->getAssociated(\App\Models\Company::class)->get();
            $tickets = $deal->getAssociated(\App\Models\Ticket::class)->get();
            $properties = $deal->getAssociated(\App\Models\Property::class)->get();
    
            return [ // Return raw array/object to be JSON encoded by response helper
                'data' => [
                    'id' => $deal->id,
                    'attributes' => $deal->load(['pipeline', 'stage', 'owner']),
                    'timeline' => $timeline,
                    'associations' => [
                        'contacts' => $contacts,
                        'companies' => $companies,
                        'tickets' => $tickets,
                        'properties' => $properties,
                    ]
                ]
            ];
        });
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'amount' => 'nullable|numeric',
            'pipeline_id' => 'required|exists:inmo_pipelines,id',
            'stage_id' => 'required|exists:inmo_pipeline_stages,id',
            'status' => 'nullable|in:open,won,lost,archived',
        ]);

        $deal = Deal::create(array_merge($validated, [
            'owner_id' => Auth::id(),
            'status' => $validated['status'] ?? 'open',
            // 'currency' => default?
        ]));

        return response()->json($deal, 201);
    }
}
