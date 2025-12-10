<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Creator
            'owner_id' => User::factory(), // Owner
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'mobile' => $this->faker->phoneNumber(),
            'lifecycle_stage' => $this->faker->randomElement(['subscriber', 'lead', 'marketing_qualified_lead', 'sales_qualified_lead', 'opportunity', 'customer']),
            'lead_status' => $this->faker->randomElement(['new', 'open', 'in_progress', 'unqualified', 'bad_timing']),
            
            // Address
            'country' => $this->faker->country(),
            'state' => $this->faker->state(),
            'city' => $this->faker->city(),
            'address' => $this->faker->address(),
            'zip_code' => $this->faker->postcode(),
            
            'last_activity_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            
            'data' => [
                'notes' => $this->faker->sentence(),
            ],
        ];
    }
}
