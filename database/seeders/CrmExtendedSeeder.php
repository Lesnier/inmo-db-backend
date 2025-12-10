<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Agent;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Ticket;
use App\Models\Activity;
use App\Models\Task;
use App\Models\Meeting;
use App\Models\Building;
use App\Models\Property;
use App\Models\Association;
use TCG\Voyager\Models\Role;

class CrmExtendedSeeder extends Seeder
{
    public function run()
    {
        // 1. Inmo Categories (Enum values)
        $categories = [
            'apartment', 'flat', 'penthouse', 'duplex', 'house', 
            'independent_house', 'semi_detached', 'townhouse', 'rustic_house', 
            'room', 'commercial', 'warehouse', 'office', 'traspaso', 
            'garage', 'storage', 'land', 'building'
        ];
        
        foreach ($categories as $cat) {
            DB::table('inmo_categories')->insertOrIgnore([
                'name' => ucfirst(str_replace('_', ' ', $cat)),
                'slug' => $cat,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Agents (Ensure we have some agents)
        // Get 'agent' role
        $agentRole = Role::where('name', 'agent')->first();
        if ($agentRole) {
            $agents = User::where('role_id', $agentRole->id)->get();
            if ($agents->count() < 3) {
                $agents = User::factory(3)->create(['role_id' => $agentRole->id]);
            }
            
            foreach ($agents as $agentUser) {
                if (!Agent::where('user_id', $agentUser->id)->exists()) {
                    Agent::create([
                        'user_id' => $agentUser->id,
                        'status' => 'approved',
                        'data' => [
                            'phone' => '555-000-' . $agentUser->id,
                            'license_number' => 'LIC-' . rand(1000, 9999)
                        ]
                    ]);
                }
            }
        }

        // 3. Buildings
        Building::factory(5)->create();

        // 4. Companies
        Company::factory(10)->create();

        // 5. Properties (Need them for Deals and Favorites)
        // Ensure we have properties
        if (Property::count() < 10) {
            Property::factory(20)->create();
        }
        $properties = Property::all();

        // 6. Favorites (Assign random properties to Admin)
        $admin = User::first(); 
        if ($admin) {
            $randomProps = $properties->random(min(5, $properties->count()));
            foreach ($randomProps as $p) {
                DB::table('inmo_favorites')->insertOrIgnore([
                    'user_id' => $admin->id,
                    'property_id' => $p->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 7. Core CRM Data (Deals, Tickets, etc)
        // Ensure contacts exist
        if (Contact::count() < 5) {
            Contact::factory(10)->create(['owner_id' => $admin ? $admin->id : 1]);
        }
        $contacts = Contact::all();
        $companies = Company::all();

        // Deals
        $deals = Deal::factory(15)->create([
            'owner_id' => $admin ? $admin->id : 1
        ]);

        // Integrate Deals with Contacts/Companies via Associations
        foreach ($deals as $deal) {
            // Associate with a random Contact
            if ($contacts->isNotEmpty()) {
                $contact = $contacts->random();
                Association::create([
                    'object_type_a' => 'deal',
                    'object_id_a' => $deal->id,
                    'object_type_b' => 'contact',
                    'object_id_b' => $contact->id,
                    'type' => 'deal_primary_contact'
                ]);
            }

            // Associate with a random Company
            if ($companies->isNotEmpty()) {
                $company = $companies->random();
                Association::create([
                    'object_type_a' => 'deal',
                    'object_id_a' => $deal->id,
                    'object_type_b' => 'company',
                    'object_id_b' => $company->id,
                    'type' => 'deal_customer'
                ]);
            }

            // Generate Stage History for the Deal
            // Assume the deal is currently in its assigned stage.
            // We'll create a history record for entering this stage.
            \App\Models\DealStageHistory::create([
                'deal_id' => $deal->id,
                'stage_id' => $deal->stage_id,
                'pipeline_id' => $deal->pipeline_id,
                'entered_at' => now()->subDays(rand(1, 10)),
                'exited_at' => null,
            ]);
            
            // Optionally add a previous stage history
            $previousStage = \App\Models\PipelineStage::where('pipeline_id', $deal->pipeline_id)
                ->where('position', '<', $deal->stage ? $deal->stage->position : 999)
                ->orderBy('position', 'desc')
                ->first();

            if ($previousStage) {
                \App\Models\DealStageHistory::create([
                    'deal_id' => $deal->id,
                    'stage_id' => $previousStage->id,
                    'pipeline_id' => $deal->pipeline_id,
                    'entered_at' => now()->subDays(rand(15, 20)),
                    'exited_at' => now()->subDays(rand(1, 10)),
                    'duration_minutes' => rand(1000, 5000),
                ]);
            }
        }

        // Tickets
        Ticket::factory(10)->create([
             'owner_id' => $admin ? $admin->id : 1
        ]);

        // Tasks
        Task::factory(10)->create([
            'assigned_to' => $admin ? $admin->id : 1,
            'created_by' => $admin ? $admin->id : 1
        ]);

        // Meetings
        Meeting::factory(5)->create([
            'host_id' => $admin ? $admin->id : 1,
            'created_by' => $admin ? $admin->id : 1
        ]);

        // Activities
        Activity::factory(20)->create([
            'created_by' => $admin ? $admin->id : 1
        ]);
        
        // Associations (Randomly link contacts to companies)
        if ($contacts->isNotEmpty() && $companies->isNotEmpty()) {
            foreach ($contacts as $contact) {
                if (rand(0, 1)) {
                    $company = $companies->random();
                    try {
                        Association::create([
                            'object_type_a' => 'contact',
                            'object_id_a' => $contact->id,
                            'object_type_b' => 'company',
                            'object_id_b' => $company->id,
                            'type' => 'works_at'
                        ]);
                    } catch (\Exception $e) {
                        // ignore duplicates
                    }
                }
            }
        }
    }
}
