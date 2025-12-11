<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Building;
use App\Models\User;
use Tests\Traits\SchemaAssertionTrait;
use Tests\Traits\RoleSeedingTrait;
use Tests\Helpers\TestSchemas;
use Illuminate\Support\Str;

class BuildingControllerTest extends TestCase
{
    use RefreshDatabase, SchemaAssertionTrait, RoleSeedingTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_can_list_buildings()
    {
        Building::factory()->count(3)->create();

        $response = $this->getJson('/api/real-estate/buildings');

        $response->assertStatus(200);
        // Pagination check might be needed if paginated
        // Assuming controller returns pagination or collection
        // Let's assume pagination wrapper "data"
        $json = $response->json();
        $list = isset($json['data']) ? $json['data'] : $json;
        
        $this->assertMatchesSchema($list, [
            TestSchemas::BUILDING
        ]);
    }

    public function test_can_create_building()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $payload = [
            'name' => 'Sky Tower',
            'address' => '123 Main St',
            'city' => 'Metropolis',
            'publisher_id' => $user->id,
            'publisher_type' => 'user' // or mapped internally
        ];

        $response = $this->postJson('/api/real-estate/buildings', $payload);

        $response->assertStatus(201);
        $this->assertMatchesSchema($response->json('data'), TestSchemas::BUILDING);
    }

    public function test_can_show_building()
    {
        $building = Building::factory()->create();

        $response = $this->getJson("/api/real-estate/buildings/{$building->id}");

        $response->assertStatus(200);
        $this->assertMatchesSchema($response->json('data'), TestSchemas::BUILDING);
    }
}
