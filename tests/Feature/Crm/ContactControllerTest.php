<?php

namespace Tests\Feature\Crm;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Contact;
use App\Models\User;
use Tests\Traits\SchemaAssertionTrait;
use Tests\Traits\RoleSeedingTrait;
use Tests\Helpers\TestSchemas;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase, SchemaAssertionTrait, RoleSeedingTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_can_list_contacts()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        Contact::factory()->count(3)->create(['owner_id' => $user->id]);

        $response = $this->getJson('/api/crm/contacts');

        $response->assertStatus(200);
        $this->assertMatchesSchema($response->json('data'), [TestSchemas::CONTACT]);
    }

    public function test_can_create_contact()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $this->actingAs($user);

        $payload = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone' => '1234567890',
            'lead_status' => 'New'
        ];

        $response = $this->postJson('/api/crm/contacts', $payload);

        $response->assertStatus(201);
        $this->assertMatchesSchema($response->json('data'), TestSchemas::CONTACT);
    }
}
