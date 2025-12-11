<?php

namespace Tests\Feature\Crm;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Deal;
use App\Models\User;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Tests\Traits\SchemaAssertionTrait;
use Tests\Traits\RoleSeedingTrait;
use Tests\Helpers\TestSchemas;

class DealControllerTest extends TestCase
{
    use RefreshDatabase, SchemaAssertionTrait, RoleSeedingTrait;

    protected $pipeline;
    protected $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        $this->pipeline = Pipeline::factory()->create(['entity_type' => 'deal']);
        $this->stage = PipelineStage::factory()->create(['pipeline_id' => $this->pipeline->id]);
    }

    public function test_can_list_deals()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        Deal::factory()->count(3)->create([
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'owner_id' => $user->id
        ]);

        $response = $this->getJson('/api/crm/deals');

        $response->assertStatus(200);
        $this->assertMatchesSchema($response->json('data'), [TestSchemas::DEAL]);
    }

    public function test_can_create_deal()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $payload = [
            'title' => 'Big Sale',
            'amount' => 500000,
            'currency' => 'USD',
            'status' => 'open',
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'owner_id' => $user->id,
            'type' => 'new_business' 
        ];

        $response = $this->postJson('/api/crm/deals', $payload);

        $response->assertStatus(201);
        $this->assertMatchesSchema($response->json('data'), TestSchemas::DEAL);
    }
}
