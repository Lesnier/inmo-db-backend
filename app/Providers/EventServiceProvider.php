<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Events\PropertyContactCreated::class => [
            \App\Listeners\StartCrmFlowFromInquiry::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        \App\Models\Deal::observe(\App\Observers\DealObserver::class);
        \App\Models\Contact::observe(\App\Observers\ContactObserver::class);
        \App\Models\Ticket::observe(\App\Observers\TicketObserver::class);
        \App\Models\Association::observe(\App\Observers\AssociationObserver::class);
        \App\Models\Property::observe(\App\Observers\PropertyObserver::class);
        \App\Models\Building::observe(\App\Observers\BuildingObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
