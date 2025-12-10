<?php

namespace Tests\Feature\RealEstate;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Building;

class BuildingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_buildings()
    {
        Building::factory()->count(3)->create();
        
        $response = $this->getJson('/api/real-estate/buildings');
        
        $response->assertStatus(200);
    }

    public function test_authorized_user_can_create_building()
    {
        $user = User::factory()->create(); // Normally Admin/Agent
        
        $payload = [
            'name' => 'Sky Tower',
            'address' => 'Downtown',
            'lat' => -0.1,
            'lng' => -78.5
        ];

        $response = $this->actingAs($user)
                         ->postJson('/api/real-estate/buildings', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('inmo_buildings', ['name' => 'Sky Tower']);
    }
}
