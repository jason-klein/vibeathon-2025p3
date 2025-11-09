<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PatientAppointment>
 */
class PatientAppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'time' => fake()->time('H:i'),
            'location' => fake()->address(),
            'summary' => fake()->sentence(),
            'patient_notes' => null,
        ];
    }
}
