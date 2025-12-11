<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Plan;
use Tests\Traits\SchemaAssertionTrait;
use Tests\Helpers\TestSchemas;

class PlanControllerTest extends TestCase
{
    use RefreshDatabase, SchemaAssertionTrait;

    public function test_can_list_plans()
    {
        Plan::factory()->count(2)->create();

        $response = $this->getJson('/api/real-estate/plans');

        $response->assertStatus(200);
        $this->assertMatchesSchema($response->json('data'), [
            TestSchemas::PLAN
        ]);
    }
}
