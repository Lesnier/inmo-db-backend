<?php

namespace Tests\Traits;

use TCG\Voyager\Models\Role;

trait RoleSeedingTrait
{
    protected function seedRoles()
    {
        // Standard Voyager Roles
        Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Administrator']);
        Role::firstOrCreate(['name' => 'user'], ['display_name' => 'Normal User']);

        // CRM Roles (from CrmRoleSeeder)
        $roles = [
            'private_person' => 'Private Person',
            'real_estate_agent' => 'Real Estate Agent',
            'real_estate_agency' => 'Real Estate Agency',
            'developer' => 'Developer',
            'property_manager' => 'Property Manager',
            'external_portal' => 'External Portal',
            'bank' => 'Bank',
            'government' => 'Government',
            'broker' => 'Broker',
        ];

        foreach ($roles as $name => $displayName) {
            Role::firstOrCreate(['name' => $name], ['display_name' => $displayName]);
        }
    }
}
