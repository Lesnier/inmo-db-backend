<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'publisher_id' => function() {
                return Agent::factory()->create()->user_id;
            },
            'publisher_type' => 'real_estate_agent',
            'contact_id' => Contact::factory(),
            'property_id' => Property::factory(),
            'status' => $this->faker->randomElement(['new', 'contacted', 'scheduled', 'closed']),
            'source' => $this->faker->randomElement(['web', 'phone', 'referral']),
            'message' => $this->faker->sentence(),
            'data' => [],
        ];
    }
}
