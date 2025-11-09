<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'summary' => fake()->optional()->paragraph(),
            'latitude' => fake()->optional()->latitude(),
            'longitude' => fake()->optional()->longitude(),
            'plain_english_record' => fake()->optional()->paragraphs(3, true),
            'executive_summary' => fake()->optional()->paragraph(),
            'executive_summary_updated_at' => fake()->optional()->dateTimeBetween('-30 days'),
        ];
    }
}
