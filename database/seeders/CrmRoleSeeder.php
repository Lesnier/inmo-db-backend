<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TCG\Voyager\Models\Role;

class CrmRoleSeeder extends Seeder
{
    public function run()
    {
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
            'sales_manager' => 'Sales Manager', // Internal role
            'support_agent' => 'Support Agent', // Internal role
        ];

        foreach ($roles as $name => $displayName) {
            $role = Role::firstOrNew(['name' => $name]);
            if (!$role->exists) {
                $role->fill([
                    'display_name' => $displayName,
                ])->save();
            }
        }
    }
}
