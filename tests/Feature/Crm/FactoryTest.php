<?php

namespace Tests\Feature\Crm;

use Tests\TestCase;
use App\Models\User;
use App\Models\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FactoryTest extends TestCase
{
    use DatabaseTransactions;

    // protected $seed = true;

    public function test_user_factory_works()
    {
        $user = User::factory()->create();
        $this->assertNotNull($user->id);
    }

    public function test_contact_factory_works()
    {
        // Contact factory depends on User factory
        $contact = Contact::factory()->create();
        $this->assertNotNull($contact->id);
    }
}
