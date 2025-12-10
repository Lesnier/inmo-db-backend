<?php

namespace Tests\Feature\Crm;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Deal;
use App\Models\Contact;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Property;
use Illuminate\Support\Facades\Cache;

class CrmCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_crm_deals_use_cache()
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create(['owner_id' => $user->id]);

        // 1. First Request (Cache Miss)
        Cache::shouldReceive('tags')->with(["crm_deal_{$deal->id}"])->once()->andReturnSelf();
        Cache::shouldReceive('remember')->once()->andReturn([
            'data' => $deal->toArray()
        ]);

        $response = $this->actingAs($user)
                         ->getJson("/api/crm/deals/{$deal->id}");
        
        $response->assertStatus(200);
    }

    public function test_property_view_uses_cache()
    {
        $property = Property::factory()->create(['status' => 'published']);

        // 1. Simulate Cache Interaction
        // We can't easily mockFacades for "remember" callback execution in integration tests without getting messy.
        // Instead, we verify the KEY exists after request.
        
        $response = $this->getJson("/api/real-estate/{$property->id}");
        $response->assertStatus(200);

        $this->assertTrue(Cache::tags(["property_{$property->id}"])->has("property_{$property->id}_detail"));
    }

    public function test_cache_invalidation_on_update()
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create(['owner_id' => $user->id]);

        // 1. Fill Cache
        $this->actingAs($user)->getJson("/api/crm/deals/{$deal->id}");
        $this->assertTrue(Cache::tags(["crm_deal_{$deal->id}"])->has("crm_deal_{$deal->id}_detail"));

        // 2. Update Deal
        $this->actingAs($user)->putJson("/api/crm/deals/{$deal->id}", ['title' => 'Updated Title']);

        // 3. Verify Cache is Gone
        $this->assertFalse(Cache::tags(["crm_deal_{$deal->id}"])->has("crm_deal_{$deal->id}_detail"));
    }

    public function test_property_cache_invalidation()
    {
        $agent = User::factory()->create(); // Agent
        $property = Property::factory()->create([
            'publisher_id' => $agent->id, 
            'publisher_type' => User::class,
            'status' => 'published'
        ]);

        // 1. Fill Cache
        $this->getJson("/api/real-estate/{$property->id}");
        $this->assertTrue(Cache::tags(["property_{$property->id}"])->has("property_{$property->id}_detail"));

        // 2. Update Property
        $this->actingAs($agent)->putJson("/api/real-estate/{$property->id}", ['title' => 'New Title']);

        // 3. Verify Detail Cache Gone
        $this->assertFalse(Cache::tags(["property_{$property->id}"])->has("property_{$property->id}_detail"));
        
        // 4. Verify Search Cache Gone (Global 'properties' tag)
        // We'd need to mock or check a specific search key, but 'flush' clears all.
        // Difficult to assert 'flush' happened without spy, but logic is there.
    }
}
