<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use TCG\Voyager\Models\Role;

class ExtendedUserSeeder extends Seeder
{
    public function run()
    {
        // 1. Ensure Roles Exist (Managed by CrmRoleSeeder usually, but double check)
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Administrator']);
        $agentRole = Role::firstOrCreate(['name' => 'agent'], ['display_name' => 'Agent']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['display_name' => 'Private User']);
        
        // 2. Create Agent Users
        User::factory(3)->create([
            'role_id' => $agentRole->id,
            'password' => Hash::make('password'),
        ]);

        // 3. Create Private Users
        User::factory(5)->create([
            'role_id' => $userRole->id,
            'password' => Hash::make('password'),
        ]);
        
        // 4. Create some Contacts for the CRM
        // Assuming Contacts are potential clients created by Agents
        \App\Models\Contact::factory(20)->create([
            'owner_id' => 1, // Assign to admin or first user
        ]);
    }
}
