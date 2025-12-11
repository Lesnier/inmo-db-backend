<?php

namespace Tests\Feature\Crm;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Activity;
use App\Models\User;
use App\Models\Contact;
use Tests\Traits\SchemaAssertionTrait;

class ActivityControllerTest extends TestCase
{
    use RefreshDatabase, SchemaAssertionTrait;

    public function test_can_list_activities()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        Activity::factory()->count(3)->create(['created_by' => $user->id]);

        $response = $this->getJson('/api/crm/activities');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    public function test_can_create_activity()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $payload = [
            'type' => 'call_log',
            'content' => 'Called client',
            'status' => 'completed',
            'scheduled_at' => now()->toIso8601String()
        ];

        $response = $this->postJson('/api/crm/activities', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('data.type', 'call_log')
                 ->assertJsonPath('data.content', 'Called client');
    }

    public function test_can_update_activity()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $activity = Activity::factory()->create(['created_by' => $user->id]);

        $response = $this->putJson("/api/crm/activities/{$activity->id}", [
            'content' => 'Updated content'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.content', 'Updated content');
    }

    public function test_can_show_activity()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $activity = Activity::factory()->create(['created_by' => $user->id]);

        $response = $this->getJson("/api/crm/activities/{$activity->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $activity->id);
    }
}
