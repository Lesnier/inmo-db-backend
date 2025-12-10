<?php

namespace Tests\Feature\Crm;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Property;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Activity;
use App\Models\Chat;
use App\Models\Association;
use TCG\Voyager\Models\Role;

class ContactAgentFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure Sales Pipeline exists
        if (!Pipeline::where('name', 'Sales')->exists()) {
             $pipeline = Pipeline::create(['name' => 'Sales', 'entity_type' => 'deal']);
             PipelineStage::factory()->create(['pipeline_id' => $pipeline->id, 'position' => 1]);
        }
    }

    public function test_contacting_agent_triggers_crm_automation()
    {
        // 1. Setup Data
        // Create Role matching PublisherType
        $roleName = 'real_estate_agent'; // From PublisherType enum
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => 'Real Estate Agent']);
        
        $agent = User::factory()->create([
            'email' => 'agent_' . uniqid() . '@example.com',
            'role_id' => $role->id
        ]);
        
        $property = Property::factory()->create([
            'publisher_id' => $agent->id,
            'publisher_type' => $roleName // e.g. 'real_estate_agent'
        ]);
        
        $user = User::factory()->create(['email' => 'client_' . uniqid() . '@example.com']);

        // 2. Perform Request (Auth User contacts Agent)
        $payload = [
            'name' => 'Interested Client',
            'email' => $user->email,
            'phone' => '123456789',
            'message' => 'I want to buy this house.'
        ];

        $response = $this->actingAs($user)
                         ->postJson("/api/real-estate/{$property->id}/contact", $payload);

        $response->assertStatus(201);

        // 3. Verify CRM Automation
        
        // A. Verify PropertyContact (Legacy - removed)
        // $this->assertDatabaseHas('property_contacts', ...);

        // B. Verify CRM Contact (Should be found/created by email)
        $contact = Contact::where('email', $user->email)->first();
        $this->assertNotNull($contact, 'CRM Contact not created');
        $this->assertEquals($agent->id, $contact->owner_id);

        // C. Verify Deal Created in Sales Pipeline
        $deal = Deal::where('owner_id', $agent->id)->latest()->first();
        $this->assertNotNull($deal, 'Deal not created');
        $this->assertEquals('open', $deal->status);
        $this->assertStringContainsString('Inquiry', $deal->title);

        // D. Verify Associations
        // Deal <-> Contact
        $this->assertDatabaseHas('inmo_associations', [
            'object_type_a' => 'deal',
            'object_id_a' => $deal->id,
            'object_type_b' => 'contact',
            'object_id_b' => $contact->id
        ]);
        
        // Deal <-> Property
        $this->assertDatabaseHas('inmo_associations', [
            'object_type_a' => 'deal',
            'object_id_a' => $deal->id,
            'object_type_b' => 'property', // or inmo_properties depending on listener
            'object_id_b' => $property->id
        ]);

        // E. Verify Activity (Note)
        $activity = Activity::where('created_by', $agent->id)->latest()->first();
        $this->assertNotNull($activity);
        $this->assertEquals('note', $activity->type);
        $this->assertStringContainsString('I want to buy', $activity->content);
        
        // Verify Activity <-> Deal Association
        $this->assertDatabaseHas('inmo_associations', [
            'object_type_a' => 'activity',
            'object_id_a' => $activity->id,
            'object_type_b' => 'deal',
            'object_id_b' => $deal->id
        ]);

        // F. Verify Chat Room
        // Should be between Agent and User
        $chat = Chat::latest()->first();
        $this->assertNotNull($chat, 'Chat not created');
        $this->assertEquals('private', $chat->type);
        
        // Check Participants
        $this->assertTrue($chat->participants->contains($agent->id));
        $this->assertTrue($chat->participants->contains($user->id));
        
        // Check Message
        $message = $chat->messages()->latest()->first();
        $this->assertNotNull($message);
        $this->assertEquals('I want to buy this house.', $message->content);
        $this->assertEquals($user->id, $message->user_id);
    }
}
