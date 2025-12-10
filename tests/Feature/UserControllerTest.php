<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Tests\Traits\SchemaAssertionTrait;
use Tests\Traits\RoleSeedingTrait;
use Tests\Helpers\TestSchemas;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, SchemaAssertionTrait, RoleSeedingTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_user_can_get_profile()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200);
        
        // Assert structure matches typical user response (not fully strictly TestSchemas::USER due to extra fields in Profile endpoint)
        $this->assertMatchesSchema($response->json(), [
            'id' => 'integer',
            'name' => 'string',
            'email' => 'string',
            'role' => TestSchemas::ROLE,
            'avatar?' => 'string',
            'settings' => 'array' // allowed to be empty
        ]);
    }

    public function test_user_can_update_profile()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->putJson('/api/user/profile', [
            'name' => 'Updated Name'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.name', 'Updated Name');
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name'
        ]);
    }

    public function test_user_analytics()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/user/analytics');

        $response->assertStatus(200);
        $this->assertMatchesSchema($response->json(), TestSchemas::USER_ANALYTICS);
    }
}
