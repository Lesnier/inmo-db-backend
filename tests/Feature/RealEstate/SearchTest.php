<?php

namespace Tests\Feature\RealEstate;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Property;
use Illuminate\Support\Facades\Cache;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Force Redis if available, or handle array driver if it supports tags
        // For this test, if Cache::tags fails with array, we might need to change config
        // But Laravel Array cache supports tags.
    }

    public function test_search_list_view_filters_only()
    {
        // 1. Setup Properties
        $cheap = Property::factory()->create(['price' => 50000, 'status' => 'published']);
        $expensive = Property::factory()->create(['price' => 500000, 'status' => 'published']);
        $draft = Property::factory()->create(['price' => 50000, 'status' => 'draft']);

        // 2. Search for cheap properties (List View behavior)
        $response = $this->getJson("/api/real-estate/search?max_price=100000");

        // 3. Assertions
        $response->assertStatus(200);
        $ids = collect($response->json())->pluck('id');

        $this->assertTrue($ids->contains($cheap->id), 'Should contain cheap property');
        $this->assertFalse($ids->contains($expensive->id), 'Should NOT contain expensive property');
        $this->assertFalse($ids->contains($draft->id), 'Should NOT contain draft property');
    }

    public function test_search_map_view_bbox_only()
    {
        $inside = Property::factory()->create([
            'lat' => -0.1807, 'lng' => -78.4678, // Quito
            'status' => 'published'
        ]);
        $outside = Property::factory()->create([
            'lat' => -2.1894, 'lng' => -79.8891, // Guayaquil
            'status' => 'published'
        ]);

        $swLat = -0.25; $swLng = -78.55;
        $neLat = -0.10; $neLng = -78.40;

        $response = $this->getJson("/api/real-estate/search?sw_lat=$swLat&sw_lng=$swLng&ne_lat=$neLat&ne_lng=$neLng&zoom=12");

        $response->assertStatus(200);
        $ids = collect($response->json())->pluck('id');

        $this->assertTrue($ids->contains($inside->id));
        $this->assertFalse($ids->contains($outside->id));
    }

    public function test_search_hybrid_filters_and_bbox()
    {
        // Inside BBox + Matches Filter
        $valid = Property::factory()->create([
            'lat' => -0.1807, 'lng' => -78.4678,
            'price' => 50000,
            'status' => 'published'
        ]);
        
        // Inside BBox + Fails Filter
        $expensiveInside = Property::factory()->create([
            'lat' => -0.1807, 'lng' => -78.4678,
            'price' => 500000,
            'status' => 'published'
        ]);

        // Outside BBox + Matches Filter
        $cheapOutside = Property::factory()->create([
            'lat' => -2.1894, 'lng' => -79.8891,
            'price' => 50000,
            'status' => 'published'
        ]);

        $swLat = -0.25; $swLng = -78.55;
        $neLat = -0.10; $neLng = -78.40;

        // Search in Quito BBox for max_price 100k
        $response = $this->getJson("/api/real-estate/search?sw_lat=$swLat&sw_lng=$swLng&ne_lat=$neLat&ne_lng=$neLng&zoom=12&max_price=100000");

        $response->assertStatus(200);
        $ids = collect($response->json())->pluck('id');

        $this->assertTrue($ids->contains($valid->id), 'Valid property must be returned');
        $this->assertFalse($ids->contains($expensiveInside->id), 'Expensive property must be filtered out');
        $this->assertFalse($ids->contains($cheapOutside->id), 'Outside property must be filtered out');
    }

    public function test_search_uses_cache_benchmark()
    {
        // 1. Seed Data (enough to make DB work a little)
        Property::factory()->count(50)->create([
            'lat' => -0.1807,
            'lng' => -78.4678,
            'status' => 'published',
        ]);
        
        $swLat = -0.25; $swLng = -78.55;
        $neLat = -0.10; $neLng = -78.40;
        $url = "/api/real-estate/search?sw_lat=$swLat&sw_lng=$swLng&ne_lat=$neLat&ne_lng=$neLng&zoom=12";

        // 2. First Hit (DB)
        $startDb = microtime(true);
        $response1 = $this->getJson($url);
        $endDb = microtime(true);
        $durationDb = ($endDb - $startDb) * 1000; // ms

        $response1->assertStatus(200);
        $sizeDb = strlen($response1->content());
        $countDb = count($response1->json());

        // 3. Second Hit (Cache)
        $startCache = microtime(true);
        $response2 = $this->getJson($url);
        $endCache = microtime(true);
        $durationCache = ($endCache - $startCache) * 1000; // ms

        $response2->assertStatus(200);
        $sizeCache = strlen($response2->content());

        // 4. Output Stats
        dump("--- REDIS BENCHMARK ---");
        dump("Items: $countDb properties");
        dump("Payload Size: " . number_format($sizeDb / 1024, 2) . " KB");
        dump("DB Hit Duration: " . number_format($durationDb, 2) . " ms");
        dump("Cache Hit Duration: " . number_format($durationCache, 2) . " ms");
        dump("Speed Improvement: " . number_format($durationDb / ($durationCache ?: 0.01), 1) . "x faster");
        dump("-----------------------");
    }
}
