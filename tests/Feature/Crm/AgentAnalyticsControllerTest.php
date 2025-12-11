<?php

namespace Tests\Feature\Crm;

use App\Models\User;
use App\Models\Deal;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AgentAnalyticsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_get_pipeline_analytics()
    {
        Deal::factory()->count(5)->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson('/api/crm/analytics/pipeline');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'pipeline_volume',
                    'pipeline_value',
                    'avg_deal_size',
                    'conversion_rate_percentage'
                ]
            ]);
    }

    public function test_can_get_performance_analytics()
    {
        $response = $this->actingAs($this->user)->getJson('/api/crm/analytics/performance');

        $response->assertStatus(200);
    }

    public function test_can_get_forecast_analytics()
    {
        $pipeline = \App\Models\Pipeline::factory()->create();
        $response = $this->actingAs($this->user)->getJson("/api/crm/analytics/forecast?pipeline_id={$pipeline->id}");

        $response->assertStatus(200);
    }
}
