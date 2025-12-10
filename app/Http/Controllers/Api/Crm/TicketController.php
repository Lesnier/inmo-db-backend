<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\CrmTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    protected $timelineService;

    public function __construct(CrmTimelineService $timelineService)
    {
        $this->timelineService = $timelineService;
    }

    public function index(Request $request)
    {
        $userId = Auth::id();
        $page = $request->input('page', 1);
        $filters = md5(json_encode($request->all()));
        
        $cacheKey = "user_{$userId}_tickets_list_p{$page}_{$filters}";

        return \Illuminate\Support\Facades\Cache::tags(["user_{$userId}_tickets"])->remember($cacheKey, now()->addMinutes(60), function () use ($request) {
            $query = Auth::user()->tickets()->with(['pipeline', 'stage']);
    
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
    
            return $query->paginate(20);
        });
    }

    public function show($id)
    {
        $cacheKey = "crm_ticket_{$id}_detail";

        return \Illuminate\Support\Facades\Cache::tags(["crm_ticket_{$id}"])->remember($cacheKey, now()->addMinutes(60), function () use ($id) {
            $ticket = Ticket::findOrFail($id);
            
            // Authorization check...
    
            $timeline = $this->timelineService->getTimeline($ticket);
    
            $contacts = $ticket->getAssociated(\App\Models\Contact::class)->get();
            $companies = $ticket->getAssociated(\App\Models\Company::class)->get();
            $deals = $ticket->getAssociated(\App\Models\Deal::class)->get();
            
            return [
                'data' => [
                    'id' => $ticket->id,
                    'attributes' => $ticket->load(['pipeline', 'stage', 'owner']),
                    'timeline' => $timeline,
                    'associations' => [
                        'contacts' => $contacts,
                        'companies' => $companies,
                        'deals' => $deals,
                    ]
                ]
            ];
        });
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'pipeline_id' => 'required|exists:inmo_pipelines,id',
            'stage_id' => 'required|exists:inmo_pipeline_stages,id',
            'priority' => 'nullable|string'
        ]);

        $ticket = Ticket::create(array_merge($validated, [
            'owner_id' => Auth::id(),
        ]));

        return response()->json($ticket, 201);
    }
}
