<?php

namespace Database\Seeders;

use App\Models\Pipeline;
use Illuminate\Database\Seeder;

class PipelineSeeder extends Seeder
{
    public function run()
    {
        // Sales Pipeline
        $sales = Pipeline::create(['name' => 'Sales Pipeline', 'entity_type' => 'deal', 'user_id' => null]);
        $sales->stages()->createMany([
            ['name' => 'New Lead', 'position' => 0, 'probability' => 0],
            ['name' => 'Contacted', 'position' => 1, 'probability' => 10],
            ['name' => 'Qualified', 'position' => 2, 'probability' => 30],
            ['name' => 'Proposal Sent', 'position' => 3, 'probability' => 60],
            ['name' => 'Negotiation', 'position' => 4, 'probability' => 80],
            ['name' => 'Closed Won', 'position' => 5, 'probability' => 100],
            ['name' => 'Closed Lost', 'position' => 6, 'probability' => 0],
        ]);

        // Support Pipeline (Tickets)
        $support = Pipeline::create(['name' => 'Support Pipeline', 'entity_type' => 'ticket', 'user_id' => null]);
        $support->stages()->createMany([
            ['name' => 'New Ticket', 'position' => 0],
            ['name' => 'In Progress', 'position' => 1],
            ['name' => 'Waiting for Customer', 'position' => 2],
            ['name' => 'Resolved', 'position' => 3],
            ['name' => 'Closed', 'position' => 4],
        ]);
        
        // Requirement Pipeline (Tickets)
        $req = Pipeline::create(['name' => 'Requirements Pipeline', 'entity_type' => 'ticket', 'user_id' => null]);
        $req->stages()->createMany([
            ['name' => 'New Request', 'position' => 0],
            ['name' => 'Searching', 'position' => 1],
            ['name' => 'Options Sent', 'position' => 2],
            ['name' => 'Viewing Scheduled', 'position' => 3],
            ['name' => 'Negotiating', 'position' => 4],
            ['name' => 'Fulfilled', 'position' => 5],
        ]);
    }
}
