<?php

namespace App\Providers;
use App\Models\Property;
use App\Policies\PropertyPolicy;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
         Property::class => PropertyPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Allow Admin users to view Pulse
        \Illuminate\Support\Facades\Gate::define('viewPulse', function ($user) {
            // Assuming admin user has role_id 1 or role->name 'admin'
            // Voyager usually sets role_id 1 for admin.
            return $user->role_id === 1 || $user->email === 'admin@admin.com';
        });
    }
}
