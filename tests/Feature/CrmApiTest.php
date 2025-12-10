<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CrmApiTest extends TestCase
{
    use DatabaseTransactions;
    // protected $seed = true; // Optimization: Seed only what's needed

    // Use RefreshDatabase to reset DB after tests? 
    // Since we just seeded, maybe better to use Transaction trait or just create data on the fly.
    // Given the environment, I'll allow it to touch the DB but rely on factories.

    public function test_can_list_contacts()
    {
        $user = User::factory()->create();
        Contact::factory()->count(3)->create(['owner_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/crm/contacts');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    public function test_can_create_contact_with_new_fields()
    {
        $user = User::factory()->create();
        
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'lead_status' => 'new',
            'country' => 'USA',
            'city' => 'New York',
            'owner_id' => $user->id,
        ];

        $response = $this->actingAs($user)->postJson('/api/crm/contacts', $data);

        $response->assertStatus(201)
                 ->assertJsonPath('data.first_name', 'John')
                 ->assertJsonPath('data.lead_status', 'new')
                 ->assertJsonPath('data.city', 'New York');
                 
        $this->assertDatabaseHas('inmo_contacts', ['email' => 'john.doe@example.com']);
    }

    public function test_can_filter_contacts_by_city()
    {
        $user = User::factory()->create();
        Contact::factory()->create(['owner_id' => $user->id, 'city' => 'Madrid']);
        Contact::factory()->create(['owner_id' => $user->id, 'city' => 'Barcelona']);

        $response = $this->actingAs($user)->getJson('/api/crm/contacts?city=Madrid');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.city', 'Madrid');
    }

    public function test_can_create_deal_with_status()
    {
        $user = User::factory()->create();
        $pipeline = Pipeline::factory()->create(['entity_type' => 'deal']);
        $stage = PipelineStage::factory()->create(['pipeline_id' => $pipeline->id]);

        $data = [
            'title' => 'Big Deal',
            'amount' => 50000,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'status' => 'open',
        ];

        $response = $this->actingAs($user)->postJson('/api/crm/deals', $data);

        $response->assertStatus(201)
                 ->assertJsonPath('title', 'Big Deal')
                 ->assertJsonPath('status', 'open');
    }
}
