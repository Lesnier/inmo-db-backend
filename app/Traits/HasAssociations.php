<?php

namespace App\Traits;

use App\Models\Association;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Ticket;
use App\Models\Property;
use App\Models\Meeting;
use App\Models\Task;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait HasAssociations
{
    /**
     * Get all associations where this model is Object A.
     */
    public function associationsAsA(): MorphMany
    {
        return $this->morphMany(Association::class, 'object_a', 'object_type_a', 'object_id_a');
    }

    /**
     * Get all associations where this model is Object B.
     */
    public function associationsAsB(): MorphMany
    {
        return $this->morphMany(Association::class, 'object_b', 'object_type_b', 'object_id_b');
    }

    /**
     * Helper to get associated models of a specific class.
     * This is a simplified getter that fetches IDs and queries the target model.
     * 
     * @param string $relatedModelClass
     * @param string|null $associationType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getAssociated($relatedModelClass, $associationType = null)
    {
        // Polymorphic Type for this model (e.g. 'contact')
        $thisType = $this->getMorphClass();
        
        // Polymorphic Type for related model (e.g. 'deal')
        $relatedType = (new $relatedModelClass)->getMorphClass();

        // Find IDs where (A=This AND B=Related) OR (B=This AND A=Related)
        $idsA = $this->associationsAsA()
            ->where('object_type_b', $relatedType)
            ->when($associationType, fn($q) => $q->where('type', $associationType))
            ->pluck('object_id_b');
            
        $idsB = $this->associationsAsB()
            ->where('object_type_a', $relatedType)
            ->when($associationType, fn($q) => $q->where('type', $associationType))
            ->pluck('object_id_a');
            
        $allIds = $idsA->merge($idsB)->unique();
        
        return $relatedModelClass::whereIn('id', $allIds);
    }

    // Dynamic Helpers
    public function deals() { return $this->getAssociated(Deal::class); }
    public function contacts() { return $this->getAssociated(Contact::class); }
    public function tickets() { return $this->getAssociated(Ticket::class); }
    public function companies() { return $this->getAssociated(Company::class); }
    public function properties() { return $this->getAssociated(Property::class); }
    public function meetings() { return $this->getAssociated(Meeting::class); }
    public function tasks() { return $this->getAssociated(Task::class); }
    
    /**
     * Create an association with another model.
     */
    public function associate($otherModel, $type = 'related')
    {
        // Sort to ensure consistency (Type A always < Type B, or ID sorting)
        // For simplicity, we just insert. Or better: A=current, B=other.
        
        return Association::firstOrCreate([
            'object_type_a' => $this->getMorphClass(),
            'object_id_a' => $this->id,
            'object_type_b' => $otherModel->getMorphClass(),
            'object_id_b' => $otherModel->id,
            'type' => $type
        ]);
    }
}
