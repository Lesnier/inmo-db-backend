<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
                         ->getJson('/api/user/profile');

        $response->assertStatus(200)
                 ->assertJsonPath('id', $user->id)
                 ->assertJsonPath('email', $user->email);
    }

    public function test_user_can_update_profile()
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Updated Name',
            // 'settings' => ['theme' => 'dark'] // Optional
        ];

        $response = $this->actingAs($user)
                         ->putJson('/api/user/profile', $payload);
        
        $response->assertStatus(200)
                 ->assertJsonPath('user.name', 'Updated Name');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name'
        ]);
    }

    public function test_profile_update_validation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
                         ->putJson('/api/user/profile', ['email' => 'invalid-email']);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }
}
