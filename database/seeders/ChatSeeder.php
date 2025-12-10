<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Chat;
use App\Models\User;
use App\Models\Message;
use App\Models\Contact;

class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure we have users and contacts to chat with
        $agent = User::where('email', 'admin@admin.com')->first() ?? User::factory()->create(['email' => 'admin@admin.com']);
        $randomUsers = User::inRandomOrder()->take(3)->get();
        if ($randomUsers->isEmpty()) {
            $randomUsers = User::factory(3)->create();
        }

        // 2. Create Chats
        foreach ($randomUsers as $user) {
            $chat = Chat::factory()->create([
                'type' => 'private',
            ]);

            // Sync participants: Agent + Random User
            $chat->participants()->attach([
                $agent->id,
                $user->id
            ]);

            // 3. Create Messages for this chat
            Message::factory(10)->create([
                'chat_id' => $chat->id,
                'user_id' => $agent->id, // Half from agent
            ]);
            
            Message::factory(10)->create([
                'chat_id' => $chat->id,
                'user_id' => $user->id, // Half from other user
            ]);
        }
    }
}
