<?php

namespace Tests\Feature\Crm;

use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PipelineControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite_testing']);
        $this->user = User::factory()->create();
    }

    public function test_can_list_pipelines_visibility()
    {
        // Global
        Pipeline::factory()->create(['user_id' => null, 'name' => 'Global Pipeline']);
        // Owned
        Pipeline::factory()->create(['user_id' => $this->user->id, 'name' => 'My Pipeline']);
        // Other's
        $otherUser = User::factory()->create();
        Pipeline::factory()->create(['user_id' => $otherUser->id, 'name' => 'Other Pipeline']);

        $response = $this->actingAs($this->user)->getJson('/api/crm/pipelines');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data') // 3 Seeded + 1 Global Test + 1 Mine
            ->assertJsonFragment(['name' => 'Global Pipeline'])
            ->assertJsonFragment(['name' => 'My Pipeline'])
            ->assertJsonFragment(['name' => 'Sales Pipeline']) // Seeded
            ->assertJsonFragment(['name' => 'Support Pipeline']) // Seeded
            ->assertJsonMissing(['name' => 'Other Pipeline']);
    }

    public function test_can_create_pipeline_owned_by_default()
    {
        $payload = [
            'name' => 'Sales Pipeline',
            'entity_type' => 'deal',
            'stages' => [['name' => 'Lead']]
        ];

        $response = $this->actingAs($this->user)->postJson('/api/crm/pipelines', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Sales Pipeline');

        $this->assertDatabaseHas('inmo_pipelines', [
            'name' => 'Sales Pipeline',
            'user_id' => $this->user->id
        ]);
    }

    public function test_cannot_update_others_pipeline()
    {
        $otherUser = User::factory()->create();
        $pipeline = Pipeline::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->putJson("/api/crm/pipelines/{$pipeline->id}", [
            'name' => 'Hacked Pipeline'
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_delete_others_pipeline()
    {
        $otherUser = User::factory()->create();
        $pipeline = Pipeline::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/crm/pipelines/{$pipeline->id}");

        $response->assertStatus(403);
    }
}
