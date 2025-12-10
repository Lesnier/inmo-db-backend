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
                 ->assertJsonPath('data.id', $user->id)
                 ->assertJsonPath('data.email', $user->email);
    }

    public function test_user_can_update_profile()
    {
        $user = User::factory()->create();

        $payload = [
            'first_name' => 'Updated Name',
            'last_name' => 'Updated Last',
            'phone' => '0999999999'
        ];

        $response = $this->actingAs($user)
                         ->putJson('/api/user/profile', $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('data.first_name', 'Updated Name');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated Name'
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
