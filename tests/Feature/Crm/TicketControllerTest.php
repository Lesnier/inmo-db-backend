<?php

namespace Tests\Feature\Crm;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Tests\Traits\SchemaAssertionTrait;
use Tests\Traits\RoleSeedingTrait;
use Tests\Helpers\TestSchemas;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase, SchemaAssertionTrait, RoleSeedingTrait;

    protected $pipeline;
    protected $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        $this->pipeline = Pipeline::factory()->create(['entity_type' => 'ticket']);
        $this->stage = PipelineStage::factory()->create(['pipeline_id' => $this->pipeline->id]);
    }

    public function test_can_list_tickets()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        Ticket::factory()->count(2)->create([
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'owner_id' => $user->id
        ]);

        $response = $this->getJson('/api/crm/tickets');

        $response->assertStatus(200);
        $this->assertMatchesSchema($response->json('data'), [TestSchemas::TICKET]);
    }

    public function test_can_create_ticket()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $payload = [
            'title' => 'System Down',
            'description' => 'Unable to login',
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'priority' => 'high'
        ];

        $response = $this->postJson('/api/crm/tickets', $payload);

        $response->assertStatus(201);
        $this->assertMatchesSchema($response->json('data'), TestSchemas::TICKET);
    }

    public function test_can_resolve_ticket()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create Resolved stage
        $resolvedStage = PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Resolved'
        ]);

        $ticket = Ticket::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'owner_id' => $user->id
        ]);

        $response = $this->postJson("/api/crm/tickets/{$ticket->id}/resolve");

        $response->assertStatus(200);
        $this->assertEquals($resolvedStage->id, $ticket->refresh()->stage_id);
    }
}
