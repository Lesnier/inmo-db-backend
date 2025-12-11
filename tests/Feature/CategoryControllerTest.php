<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Category;
use Tests\Traits\SchemaAssertionTrait;
use Tests\Helpers\TestSchemas;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase, SchemaAssertionTrait;

    public function test_can_list_categories()
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/real-estate/categories');

        $response->assertStatus(200);
        $this->assertIsArray($response->json('data'));
        // Verify array of categories
        $this->assertMatchesSchema($response->json('data'), [
            TestSchemas::CATEGORY // List check
        ]);
    }
}
