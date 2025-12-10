<?php

namespace App\Observers;

use App\Models\Deal;
use Illuminate\Support\Facades\Cache;

class DealObserver
{
    /**
     * Handle the Deal "saved" event (created or updated).
     */
    /**
     * Handle the Deal "created" event.
     */
    public function created(Deal $deal): void
    {
        $this->recordHistory($deal);
    }

    /**
     * Handle the Deal "updated" event.
     */
    public function updated(Deal $deal): void
    {
        if ($deal->wasChanged('stage_id')) {
             $this->closePreviousHistory($deal);
             $this->recordHistory($deal);
        }
        $this->clearCache($deal);
    }

    /**
     * Handle the Deal "deleted" event.
     */
    public function deleted(Deal $deal): void
    {
        $this->clearCache($deal);
    }
    
    protected function recordHistory(Deal $deal)
    {
        \App\Models\DealStageHistory::create([
            'deal_id' => $deal->id,
            'stage_id' => $deal->stage_id,
            'pipeline_id' => $deal->pipeline_id,
            'entered_at' => now(),
        ]);
    }

    protected function closePreviousHistory(Deal $deal)
    {
        // Find the LATEST open history record (exited_at null)
        $history = \App\Models\DealStageHistory::where('deal_id', $deal->id)
             ->whereNull('exited_at')
             ->orderBy('created_at', 'desc')
             ->first();
             
        if ($history) {
            $now = now();
            $duration = $history->entered_at->diffInMinutes($now);
            $history->update([
                'exited_at' => $now,
                'duration_minutes' => $duration
            ]);
        }
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
