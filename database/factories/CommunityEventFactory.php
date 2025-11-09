<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CommunityEvent>
 */
class CommunityEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'community_partner_id' => \App\Models\CommunityPartner::factory(),
            'date' => fake()->dateTimeBetween('now', '+3 months'),
            'time' => fake()->time(),
            'location' => fake()->address(),
            'description' => fake()->paragraph(),
            'link' => fake()->optional(0.6)->url(),
            'is_partner_provided' => fake()->boolean(),
        ];
    }
}
