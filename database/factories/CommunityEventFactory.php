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
        // Joplin, MO center point: 37.0842° N, 94.5133° W
        $centerLat = 37.0842;
        $centerLng = -94.5133;
        $radius = 0.3; // Approximately 20 miles in degrees

        return [
            'community_partner_id' => \App\Models\CommunityPartner::factory(),
            'date' => fake()->dateTimeBetween('now', '+3 months'),
            'time' => fake()->time(),
            'location' => fake()->address(),
            'latitude' => $centerLat + (fake()->randomFloat(6, -$radius, $radius)),
            'longitude' => $centerLng + (fake()->randomFloat(6, -$radius, $radius)),
            'description' => fake()->paragraph(),
            'link' => fake()->optional(0.6)->url(),
            'is_partner_provided' => fake()->boolean(),
        ];
    }
}
