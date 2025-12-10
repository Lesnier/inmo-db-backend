<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Association;
use Illuminate\Support\Facades\DB;

class AssociationController extends Controller
{
    protected $mapping = [
        'deals' => \App\Models\Deal::class,
        'contacts' => \App\Models\Contact::class,
        'tickets' => \App\Models\Ticket::class,
        'properties' => \App\Models\Property::class,
        'activities' => \App\Models\Activity::class,
        'users' => \App\Models\User::class,
    ];

    /**
     * Create an association between two objects.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_type' => 'required|string|in:' . implode(',', array_keys($this->mapping)),
            'source_id' => 'required|integer',
            'target_type' => 'required|string|in:' . implode(',', array_keys($this->mapping)),
            'target_id' => 'required|integer',
        ]);

        $sourceClass = $this->mapping[$validated['source_type']];
        $targetClass = $this->mapping[$validated['target_type']];

        // Verify existence
        if (!$sourceClass::find($validated['source_id'])) {
            return response()->json(['message' => 'Source not found'], 404);
        }
        if (!$targetClass::find($validated['target_id'])) {
             return response()->json(['message' => 'Target not found'], 404);
        }

        // Logic check: Avoid duplicates?
        // Basic implementation: Create direct record.
        // Map type string to internal 'table name' or 'short type'?
        // The table `inmo_associations` uses `object_type_a`, `object_id_a`, etc.
        // Usually, `object_type` matches MorphMap or Class Name or simplified string (e.g. 'deal').
        
        // Let's assume simplified singular string: 'deals' -> 'deal'
        $typeA = \Illuminate\Support\Str::singular($validated['source_type']);
        $typeB = \Illuminate\Support\Str::singular($validated['target_type']);

        $association = Association::create([
            'object_type_a' => $typeA,
            'object_id_a' => $validated['source_id'],
            'object_type_b' => $typeB,
            'object_id_b' => $validated['target_id'],
        ]);

        return response()->json($association, 201);
    }
}
