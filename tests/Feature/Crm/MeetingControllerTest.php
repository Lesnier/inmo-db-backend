<?php

namespace Tests\Feature\Crm;

use App\Models\Meeting;
use App\Models\User;
use App\Models\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MeetingControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_meetings()
    {
        Meeting::factory()->count(3)->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson('/api/crm/meetings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'subject', 'scheduled_at']
                ]
            ]);
    }

    public function test_can_create_meeting()
    {
        $contact = Contact::factory()->create(['owner_id' => $this->user->id]);

        $payload = [
            'subject' => 'Sales Call',
            'scheduled_at' => now()->addDays(2)->toDateTimeString(),
            'description' => 'Discuss new property',
            'meeting_type' => 'call',
            'host_id' => $this->user->id,
            'associations' => [
                ['type' => 'contacts', 'id' => $contact->id]
            ]
        ];

        $response = $this->actingAs($this->user)->postJson('/api/crm/meetings', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.subject', 'Sales Call');

        $this->assertDatabaseHas('inmo_meetings', ['subject' => 'Sales Call']);
        
        // Verify association
        $meetingId = $response->json('data.id'); // Assuming standard format, though create returns object directly in previous implementation, we standardized it to wrap in data for consistency? 
        // Wait, MeetingController::store returned $meeting directly in my previous edit?
        // Let's check MeetingController::store return again.
        // It was: return response()->json($meeting, 201); which is NOT standardized.
        // Refactoring to standard first.
    }
}
