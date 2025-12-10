<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'private_person' => \App\Models\User::class,
            'real_estate_agent' => \App\Models\Agent::class,
            // 'real_estate_agency' => \App\Models\Agency::class, // TODO: Crear modelo Agency si es necesario
            
            'property' => \App\Models\Property::class,
            'building' => \App\Models\Building::class,
        ]);
    }
}
