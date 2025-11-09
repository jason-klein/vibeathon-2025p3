<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PatientTask>
 */
class PatientTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => fake()->sentence(),
            'instructions' => fake()->optional()->paragraph(),
            'is_scheduling_task' => fake()->boolean(30), // 30% scheduling tasks
            'provider_specialty_needed' => null,
            'completed_at' => null,
        ];
    }
}
