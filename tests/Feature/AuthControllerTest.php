<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\SchemaAssertionTrait;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Traits\RoleSeedingTrait;
use Tests\Helpers\TestSchemas;

class AuthControllerTest extends TestCase
{
    use DatabaseTransactions, SchemaAssertionTrait, RoleSeedingTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_register_creates_user_and_returns_token()
    {
        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => 2 // Agent
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(201);
        
        // Check structure
        $this->assertMatchesSchema($response->json(), [
            'user' => TestSchemas::USER,
            'token' => 'string'
        ]);

        $this->assertEquals('Test User', $response->json('user.name'));
    }

    public function test_login_returns_token_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);

        // Check strict schema matching
        $this->assertMatchesSchema($response->json(), [
            'user' => TestSchemas::USER,
            'token' => 'string'
        ]);
        
        // Assert data
        $this->assertEquals($user->id, $response->json('user.id'));
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'wrong@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid login details']);
    }

    public function test_logout_revokes_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                         ->postJson('/api/auth/logout');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Logged out successfully']);

        // Assert token is deleted/revoked
        $this->assertCount(0, $user->tokens);
    }
}
