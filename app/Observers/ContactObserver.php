<?php

namespace App\Observers;

use App\Models\Contact;
use Illuminate\Support\Facades\Cache;

class ContactObserver
{
    public function saved(Contact $contact): void
    {
        $this->clearCache($contact);
    }

    public function deleted(Contact $contact): void
    {
        $this->clearCache($contact);
    }

    protected function clearCache(Contact $contact)
    {
        // 1. Detail
        Cache::tags(["crm_contact_{$contact->id}"])->flush();

        // 2. List (Owner)
        if ($contact->owner_id) {
            Cache::tags(["user_{$contact->owner_id}_contacts"])->flush();
        }
        // Also clear creator's list if different? Usually owner is key.
        if ($contact->user_id && $contact->user_id !== $contact->owner_id) {
             Cache::tags(["user_{$contact->user_id}_contacts"])->flush();
        }
    }
}
