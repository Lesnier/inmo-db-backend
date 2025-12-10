<?php

namespace App\Observers;

use App\Models\Ticket;
use Illuminate\Support\Facades\Cache;

class TicketObserver
{
    public function saved(Ticket $ticket): void
    {
        $this->clearCache($ticket);
    }

    public function deleted(Ticket $ticket): void
    {
        $this->clearCache($ticket);
    }

    protected function clearCache(Ticket $ticket)
    {
        Cache::tags(["crm_ticket_{$ticket->id}"])->flush();

        if ($ticket->owner_id) {
            Cache::tags(["user_{$ticket->owner_id}_tickets"])->flush();
        }
    }
}
