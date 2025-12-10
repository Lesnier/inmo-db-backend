<?php

namespace App\Observers;

use App\Models\Association;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AssociationObserver
{
    public function created(Association $association): void
    {
        $this->clearBothSides($association);
    }

    public function deleted(Association $association): void
    {
        $this->clearBothSides($association);
    }

    protected function clearBothSides(Association $association)
    {
        // Flush Type A
        // mapping 'deal' -> 'crm_deal_{id}'
        
        $tagA = $this->getTag($association->object_type_a, $association->object_id_a);
        if ($tagA) Cache::tags([$tagA])->flush();

        $tagB = $this->getTag($association->object_type_b, $association->object_id_b);
        if ($tagB) Cache::tags([$tagB])->flush();
    }

    protected function getTag($type, $id)
    {
        // Normalized type names from AssociationController logic:
        // 'deal', 'contact', 'ticket', 'property'
        // 'property' isn't fully cached with this tag pattern yet (except Search which uses 'properties' tag),
        // but if we add property detail caching later, this covers it.
        
        // Ensure singular
        $type = Str::singular($type);
        
        return "crm_{$type}_{$id}";
    }
}
