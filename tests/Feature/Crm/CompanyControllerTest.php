<?php

namespace Tests\Feature\Crm;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CompanyControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_companies()
    {
        Company::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->getJson('/api/crm/companies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'industry']
                ]
            ]);
    }

    public function test_can_create_company()
    {
        $payload = [
            'name' => 'New Company',
            'industry' => 'Tech',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/crm/companies', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Company');

        $this->assertDatabaseHas('inmo_companies', ['name' => 'New Company']);
    }

    public function test_can_show_company()
    {
        $company = Company::factory()->create();

        $response = $this->actingAs($this->user)->getJson("/api/crm/companies/{$company->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $company->id)
            ->assertJsonStructure(['data' => ['attributes', 'associations']]);
    }

    public function test_can_update_company()
    {
        $company = Company::factory()->create();

        $response = $this->actingAs($this->user)->putJson("/api/crm/companies/{$company->id}", [
            'name' => 'Updated Name'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_can_delete_company()
    {
        $company = Company::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/crm/companies/{$company->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('inmo_companies', ['id' => $company->id]);
    }
}
