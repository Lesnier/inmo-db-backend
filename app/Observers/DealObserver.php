<?php

namespace App\Observers;

use App\Models\Deal;
use Illuminate\Support\Facades\Cache;

class DealObserver
{
    /**
     * Handle the Deal "saved" event (created or updated).
     */
    public function saved(Deal $deal): void
    {
        $this->clearCache($deal);
    }

    /**
     * Handle the Deal "deleted" event.
     */
    public function deleted(Deal $deal): void
    {
        $this->clearCache($deal);
    }

    protected function clearCache(Deal $deal)
    {
        // 1. Clear Detail Cache
        Cache::tags(["crm_deal_{$deal->id}"])->flush();

        // 2. Clear List Cache for Owner
        if ($deal->owner_id) {
            Cache::tags(["user_{$deal->owner_id}_deals"])->flush();
        }
    }
}
