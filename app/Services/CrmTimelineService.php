<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CrmTimelineService
{
    /**
     * Get the aggregated timeline for a given entity (Contact, Deal, Ticket).
     * The timeline includes Activities, Tasks, Meetings, and potentially Notes/Emails if stored separately.
     *
     * @param Model $entity
     * @return Collection
     */
    public function getTimeline(Model $entity): Collection
    {
        // 1. Get Direct Associations (items linked to this entity)
        // usage: $entity->tasks, $entity->meetings if relations exist directly
        // OR using the polymorphic associations table if everything is linked there.

        // Assuming we rely on the `HasAssociations` trait logic where:
        // $entity->associations() morphsMany Association.
        
        // HOWEVER, for timeline, usually we want:
        // - Activities (logs)
        // - Tasks (todo)
        // - Meetings (calendar)
        
        // If these models use `HasAssociations`, we can query them via the Association table,
        // OR if we added specific methods like `tasks()` to the entities.
        
        // Let's assume we want to query related items dynamically.
        
        $timeline = collect();

        // A. Activities (Logs, Notes, Calls)
        // If the entity interacts with the 'Activity' model via HasAssociations
        if (method_exists($entity, 'associations')) {
             $activityIds = $entity->associationsAsA()
                ->where('object_type_b', 'activity') // map from morph map or full class
                ->pluck('object_id_b');
             
             // Also check inverse if needed? Usually Timeline host is 'A'.
             
             // Simplification: In HubSpot, Activities are usually 'associated' to the Deal/Contact.
             // We configured `HasAssociations`.
             
             // Let's grab all associated "Activity", "Task", "Meeting".
             
             $associations = $entity->associationsAsA()->get(); 
             // We might need to optimistically load.
             
             // For performance, let's query specific types if we can, but polymorphic is tricky.
             // Let's use the helper `getAssociated($modelClass)` if available or manual query.
        }

        // Alternative: If your models have direct relationships defined (e.g. Deal hasMany Activity)
        // use those. But we moved to Universal Associations.
        
        // So we iterate valid timeline types:
        $types = [
            \App\Models\Activity::class => 'activity',
            \App\Models\Task::class => 'task',
            \App\Models\Meeting::class => 'meeting',
        ];

        foreach ($types as $class => $typeKey) {
            // Using HasAssociations trait method: getAssociated($class)
            // But wait, the trait `getAssociated` returns a Builder or Collection?
            // Let's assume it returns a Builder or we use the raw association query.
            
            // Ref: HasAssociations trait
            // public function getAssociated($relatedClass, $type = null)
            
            if (method_exists($entity, 'getAssociated')) {
                $items = $entity->getAssociated($class)->get();
                
                // Map to uniform timeline structure
                $mapped = $items->map(function ($item) use ($typeKey) {
                    return [
                        'id' => $item->id,
                        'object_type' => $typeKey, // 'activity', 'task', etc
                        'data' => $item, // full object
                        // Sortable date
                        'timeline_date' => $this->getDate($item, $typeKey),
                    ];
                });

                $timeline = $timeline->merge($mapped);
            }
        }

        return $timeline->sortByDesc('timeline_date')->values();
    }

    protected function getDate($item, $type)
    {
        switch ($type) {
            case 'activity':
                return $item->completed_at ?? $item->created_at;
            case 'task':
                return $item->due_date ?? $item->created_at;
            case 'meeting':
                return $item->scheduled_at ?? $item->created_at;
            default:
                return $item->created_at;
        }
    }
}
