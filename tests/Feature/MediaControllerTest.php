<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MediaControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_media()
    {
        Media::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->getJson('/api/media');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'url', 'type']
                ]
            ]);
    }

    public function test_can_create_media()
    {
        $payload = [
            'url' => 'https://example.com/file.jpg',
            'type' => 'image',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/media', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.url', 'https://example.com/file.jpg');

        $this->assertDatabaseHas('inmo_media', ['url' => 'https://example.com/file.jpg']);
    }

    public function test_can_show_media()
    {
        $media = Media::factory()->create();

        $response = $this->actingAs($this->user)->getJson("/api/media/{$media->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $media->id)
            ->assertJsonStructure(['data' => ['attributes', 'associations']]);
    }

    public function test_can_delete_media()
    {
        $media = Media::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/media/{$media->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('inmo_media', ['id' => $media->id]);
    }
}
