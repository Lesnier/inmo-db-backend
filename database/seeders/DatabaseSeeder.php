<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(VoyagerDatabaseSeeder::class);
        $this->call(VoyagerDummyDatabaseSeeder::class);
        $this->call(DocumentationMenuSeeder::class);
        $this->call(CrmRoleSeeder::class);
        $this->call(RealEstateSeeder::class);
        $this->call(PipelineSeeder::class);
        $this->call(ExtendedUserSeeder::class); // Adds diverse users and contacts
        $this->call(CrmExtendedSeeder::class);  // Adds Companies, Deals, Tickets, Activities, etc.
        $this->call(ChatSeeder::class);         // Adds chats and messages
    }
}
