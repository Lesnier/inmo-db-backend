<?php

namespace Tests\Feature\Crm;

use Tests\TestCase;
use App\Models\User;
use App\Models\Deal;
use App\Models\Contact;
use App\Models\Task;
use App\Models\Activity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Meeting;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\Category;
use App\Models\Company;
use App\Models\Media;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AssociationCreationTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $pipeline;
    protected $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->pipeline = Pipeline::factory()->create();
        $this->stage = PipelineStage::factory()->create(['pipeline_id' => $this->pipeline->id]);
    }

    public function test_can_create_contact_with_association_to_deal()
    {
        $deal = Deal::factory()->create([
            'owner_id' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id
        ]);

        $payload = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'associations' => [
                ['type' => 'deals', 'id' => $deal->id]
            ]
        ];

        $response = $this->actingAs($this->user)->postJson('/api/crm/contacts', $payload);

        $response->assertStatus(201);
        $contactId = $response->json('data.id');

        $this->assertDatabaseHas('inmo_associations', [
            'object_type_a' => 'contact',
            'object_id_a' => $contactId,
            'object_type_b' => 'deal',
            'object_id_b' => $deal->id
        ]);
    }

    public function test_can_create_task_with_association_to_contact()
    {
        $contact = Contact::factory()->create(['owner_id' => $this->user->id]);

        $payload = [
            'title' => 'Follow up task',
            'associations' => [
                ['type' => 'contacts', 'id' => $contact->id]
            ]
        ];

        $response = $this->actingAs($this->user)->postJson('/api/crm/tasks', $payload);

        $response->assertStatus(201);
        $taskId = $response->json('id'); // Task controller usage of 'data' wrapper check needed

        // Check if TaskController uses 'data' wrapper or raw object. 
        // Based on my edit, I modified store to "return response()->json($task, 201);" in step 484 diff.
        // Wait, step 484 diff showed "return response()->json($task, 201);" 
        // But Standardization task changed this earlier? 
        // Let's check if the previous standardization applied to TaskController.
        // I don't recall Standardizing TaskController explicitly in the "Controllers Standardized" summary list.
        // The summary said "Refactor remaining CRM controllers... TaskController".
        // Let's assume raw or standardize during test.
        
        // Actually, if I look at Step 484 output, I see:
        // +        return response()->json($task, 201);
        // It does NOT wrap in data? 
        // Check ContactController in Step 476: return response()->json(['data' => $contact], 201); (Wrapped)
        
        // I should probably check the response key.
        if (!$taskId) {
            $taskId = $response->json('data.id');
        }

        $this->assertDatabaseHas('inmo_associations', [
            'object_type_a' => 'task',
            'object_id_a' => $taskId,
            'object_type_b' => 'contact',
            'object_id_b' => $contact->id
        ]);
    }

    public function test_can_create_activity_with_association_to_deal()
    {
        $deal = Deal::factory()->create([
            'owner_id' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id
        ]);

        $payload = [
            'type' => 'call',
            'content' => 'Call content',
            'associations' => [
                ['type' => 'deals', 'id' => $deal->id]
            ]
        ];

        $response = $this->actingAs($this->user)->postJson('/api/crm/activities', $payload);

        $response->assertStatus(201);
        $activityId = $response->json('data.id');

        $this->assertDatabaseHas('inmo_associations', [
            'object_type_a' => 'activity',
            'object_id_a' => $activityId,
            'object_type_b' => 'deal',
            'object_id_b' => $deal->id
        ]);
    }

    public function test_can_create_deal_with_association_to_contact()
    {
        $contact = Contact::factory()->create(['owner_id' => $this->user->id]);

        $payload = [
            'title' => 'New Deal',
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'associations' => [
                ['type' => 'contacts', 'id' => $contact->id]
            ]
        ];

        $response = $this->actingAs($this->user)->postJson('/api/crm/deals', $payload);

        $response->assertStatus(201);
        $dealId = $response->json('data.id');

        $this->assertDatabaseHas('inmo_associations', [
            'object_type_a' => 'deal',
            'object_id_a' => $dealId,
            'object_type_b' => 'contact',
            'object_id_b' => $contact->id
        ]);
    }

    public function test_can_create_meeting_with_association_to_contact()
    {
        $contact = Contact::factory()->create(['owner_id' => $this->user->id]);

        $payload = [
            'subject' => 'Client Meeting',
            'scheduled_at' => now()->addDays(1)->toDateTimeString(),
            'meeting_type' => 'in_person',
            'associations' => [
                ['type' => 'contacts', 'id' => $contact->id]
            ]
        ];

        $response = $this->actingAs($this->user)->postJson('/api/crm/meetings', $payload);
        $response->assertStatus(201);
        $meetingId = $response->json('id'); 

        $this->assertDatabaseHas('inmo_associations', [
            'object_type_a' => 'meeting',
            'object_id_a' => $meetingId,
            'object_type_b' => 'contact',
            'object_id_b' => $contact->id
        ]);
    }

    public function test_can_create_ticket_with_association_to_deal()
    {
        $deal = Deal::factory()->create([
            'owner_id' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id
        ]);

        $payload = [
            'title' => 'Fix issue',
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'associations' => [
                ['type' => 'deals', 'id' => $deal->id]
            ]
        ];

        $response = $this->actingAs($this->user)->postJson('/api/crm/tickets', $payload);
        $response->assertStatus(201);
        $ticketId = $response->json('data.id');

        $this->assertDatabaseHas('inmo_associations', [
            'object_type_a' => 'ticket',
            'object_id_a' => $ticketId,
            'object_type_b' => 'deal',
            'object_id_b' => $deal->id
        ]);
    }

    public function test_can_create_property_with_association_to_contact()
    {
        // Category is required for Property
        $category = Category::factory()->create();
        $contact = Contact::factory()->create(['owner_id' => $this->user->id]);

        // Note: Generic associations with property usually connect to Deal or Contact owner
        // Here we test simply establishing the link.
        $payload = [
            'title' => 'New House',
            'price' => 500000,
            'category_id' => $category->id,
            'associations' => [
                ['type' => 'contacts', 'id' => $contact->id]
            ]
        ];

        $response = $this->actingAs($this->user)->postJson('/api/real-estate', $payload);
        $response->assertStatus(201);
        $propertyId = $response->json('data.id');

        $this->assertDatabaseHas('inmo_associations', [
            'object_type_a' => 'property',
            'object_id_a' => $propertyId,
            'object_type_b' => 'contact',
            'object_id_b' => $contact->id
        ]);
    }

    public function test_can_create_company_with_association_to_contact()
    {
        $contact = Contact::factory()->create(['owner_id' => $this->user->id]);

        $payload = [
            'name' => 'Tech Corp',
            'associations' => [
                ['type' => 'contacts', 'id' => $contact->id]
            ]
        ];

        $response = $this->actingAs($this->user)->postJson('/api/crm/companies', $payload);
        $response->assertStatus(201);
        $companyId = $response->json('data.id');

        $this->assertDatabaseHas('inmo_associations', [
            'object_type_a' => 'company',
            'object_id_a' => $companyId,
            'object_type_b' => 'contact',
            'object_id_b' => $contact->id
        ]);
    }

    public function test_can_create_media_with_association_to_property()
    {
        $property = Property::factory()->create(['publisher_id' => $this->user->id]);

        $payload = [
            'url' => 'https://example.com/image.jpg',
            'type' => 'image',
            'associations' => [
                ['type' => 'properties', 'id' => $property->id]
            ]
        ];

        $response = $this->actingAs($this->user)->postJson('/api/media', $payload);
        $response->assertStatus(201);
        $mediaId = $response->json('data.id');

        $this->assertDatabaseHas('inmo_associations', [
            'object_type_a' => 'media',
            'object_id_a' => $mediaId,
            'object_type_b' => 'property',
            'object_id_b' => $property->id
        ]);
    }
}
