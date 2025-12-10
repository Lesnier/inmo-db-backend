<?php

namespace Tests\Feature\RealEstate;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Property;
use App\Models\Category;

class PropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_properties()
    {
        Property::factory()->count(5)->create(['status' => 'published']);
        
        $response = $this->getJson('/api/real-estate');

        $response->assertStatus(200)
                 ->assertJsonCount(5, 'data'); // Assuming Resource wrapper
    }

    public function test_can_show_property()
    {
        $property = Property::factory()->create(['status' => 'published']);

        $response = $this->getJson("/api/real-estate/properties/{$property->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $property->id);
    }

    public function test_agent_can_create_property()
    {
        $agent = User::factory()->create(); // Add role agent if needed using seeders
        $category = Category::factory()->create();

        $payload = [
            'title' => 'New Luxury Villa',
            'description' => 'Great house',
            'price' => 500000,
            'category_id' => $category->id,
            'operation_type' => 'sale',
            'lat' => -0.18,
            'lng' => -78.48,
            'address' => 'Av. Test 123'
        ];

        $response = $this->actingAs($agent)
                         ->postJson('/api/real-estate/properties', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('inmo_properties', ['title' => 'New Luxury Villa']);
    }

    public function test_owner_can_update_property()
    {
        $agent = User::factory()->create();
        $property = Property::factory()->create([
            'publisher_id' => $agent->id,
            'publisher_type' => 'user' // or agent depending on polymorphic logic
        ]);

        // Fix polymorphic issue if factory defaults wrong
        $property->publisher_id = $agent->id;
        $property->publisher_type = User::class;
        $property->save();

        $payload = ['title' => 'Updated Title'];

        $response = $this->actingAs($agent)
                         ->putJson("/api/real-estate/properties/{$property->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inmo_properties', ['id' => $property->id, 'title' => 'Updated Title']);
    }

    public function test_non_owner_cannot_update_property()
    {
        $owner = User::factory()->create();
        $thief = User::factory()->create();
        
        $property = Property::factory()->create([
            'publisher_id' => $owner->id,
            'publisher_type' => User::class
        ]);

        $response = $this->actingAs($thief)
                         ->putJson("/api/real-estate/properties/{$property->id}", ['title' => 'Hacked']);

        $response->assertStatus(403);
    }
}
