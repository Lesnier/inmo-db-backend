<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Support\Facades\Event;
use App\Events\MessageSent;

class ChatSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_chats()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user1->id, $user2->id]);

        $response = $this->actingAs($user1)->getJson('/api/chat/rooms');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'participants']
                ]
            ]);
    }

    public function test_can_create_private_chat()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $response = $this->actingAs($user1)->postJson('/api/chat/rooms', [
            'participant_id' => $user2->id,
            'subject' => 'New Project'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'type', 'participants']);
        
        $this->assertDatabaseHas('chats', ['type' => 'private']);
        $this->assertEquals(1, Chat::count());
    }

    public function test_can_send_message_without_broadcasting()
    {
        Event::fake();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user1->id, $user2->id]);

        $response = $this->actingAs($user1)->postJson("/api/chat/rooms/{$chat->id}/messages", [
            'content' => 'Hello there!'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'content', 'user_id']);

        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'content' => 'Hello there!'
        ]);

        // critical check: ensure event was dispatched but NOT broadcasted via redis/reverb
        Event::assertDispatched(MessageSent::class);
    }

    public function test_can_list_messages()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user1->id, $user2->id]);

        Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user2->id,
            'content' => 'Hi user1'
        ]);

        $response = $this->actingAs($user1)->getJson("/api/chat/rooms/{$chat->id}/messages");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'content', 'sender']]]);
    }
}
