<?php

namespace Tests\Feature\Crm;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed Roles if needed or create manuall
        // We need Sales Pipeline
        $pipeline = Pipeline::create(['name' => 'Sales', 'slug' => 'sales']);
        PipelineStage::factory()->create(['pipeline_id' => $pipeline->id, 'order' => 1]);
    }

    public function test_contacting_agent_triggers_crm_automation()
    {
        // 1. Setup Data
        $agent = User::factory()->create(['email' => 'agent@example.com']);
        $property = Property::factory()->create([
            'agent_id' => $agent->id, // Assuming property has agent_id column or relation through publisher
            'publisher_id' => $agent->id,
            'publisher_type' => 'real_estate_agent'
        ]);
        
        // Ensure property has agent_id set correctly if controller relies on it
        // The Controller uses $property->agent_id
        if (!$property->agent_id) {
             $property->agent_id = $agent->id;
             $property->save();
        }

        $user = User::factory()->create(['email' => 'client@example.com']);

        // 2. Perform Request (Auth User contacts Agent)
        $payload = [
            'name' => 'Interested Client',
            'email' => 'client@example.com', // Matches user email
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
        $contact = Contact::where('email', 'client@example.com')->first();
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
        $this->assertEquals($user->id, $message->sender_id);
    }
}
