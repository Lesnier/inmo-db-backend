<?php

namespace Tests\Feature\RealEstate;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Property;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_manage_favorites()
    {
        $property = Property::factory()->create();
        
        $response = $this->postJson("/api/real-estate/{$property->id}/favorite");
        $response->assertStatus(401); // Unauthorized

        $responseList = $this->getJson("/api/user/favorites");
        $responseList->assertStatus(401);
    }

    public function test_user_can_toggle_favorite()
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();

        // 1. Toggle On
        $response = $this->actingAs($user)
                         ->postJson("/api/real-estate/{$property->id}/favorite");

        $response->assertStatus(200)
                 ->assertJson(['data' => ['favorited' => true]]);

        $this->assertDatabaseHas('inmo_favorites', [
            'user_id' => $user->id,
            'property_id' => $property->id
        ]);

        // 2. Toggle Off
        $response = $this->actingAs($user)
                         ->postJson("/api/real-estate/{$property->id}/favorite");

        $response->assertStatus(200)
                 ->assertJson(['data' => ['favorited' => false]]);

        $this->assertDatabaseMissing('inmo_favorites', [
            'user_id' => $user->id,
            'property_id' => $property->id
        ]);
    }

    public function test_user_can_list_favorites()
    {
        $user = User::factory()->create();
        $properties = Property::factory()->count(3)->create();

        // Attach 2 properties
        $user->favorite_properties()->attach($properties[0]->id);
        $user->favorite_properties()->attach($properties[1]->id);

        $response = $this->actingAs($user)
                         ->getJson("/api/user/favorites");

        $response->assertStatus(200);
        $data = $response->json('data'); // Assuming paginated or wrapped in data
        
        // If wrapper "data" exists
        if (!$data) $data = $response->json(); // Fallback if direct array (unlikely for paginated resource)

        $this->assertCount(2, $data);
        
        $ids = collect($data)->pluck('id');
        $this->assertTrue($ids->contains($properties[0]->id));
        $this->assertTrue($ids->contains($properties[1]->id));
        $this->assertFalse($ids->contains($properties[2]->id));
    }
}
