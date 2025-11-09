<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HealthcareProvider>
 */
class HealthcareProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Joplin, MO center coordinates
        $centerLat = 37.0842;
        $centerLon = -94.5133;
        $radiusMiles = 20;

        $coords = $this->generateRandomCoordinates($centerLat, $centerLon, $radiusMiles);

        $specialties = [
            'Family Medicine',
            'Cardiology',
            'Orthopedics',
            'Endocrinology',
            'Pulmonology',
            'Radiology',
            'Physical Therapy',
            'Dermatology',
            'Neurology',
            'Gastroenterology',
        ];

        return [
            'name' => fake()->name(),
            'specialty' => fake()->randomElement($specialties),
            'location' => fake()->streetAddress().', Joplin, MO '.fake()->numberBetween(64801, 64804),
            'latitude' => $coords['lat'],
            'longitude' => $coords['lon'],
            'phone' => fake()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
        ];
    }

    /**
     * Generate random coordinates within a radius from a center point.
     */
    protected function generateRandomCoordinates(float $centerLat, float $centerLon, float $radiusMiles): array
    {
        $radiusKm = $radiusMiles * 1.60934;
        $radiusDegrees = $radiusKm / 111.32;

        $u = fake()->randomFloat(2, 0, 1);
        $v = fake()->randomFloat(2, 0, 1);
        $w = $radiusDegrees * sqrt($u);
        $t = 2 * pi() * $v;
        $x = $w * cos($t);
        $y = $w * sin($t);

        $newLat = $centerLat + $y;
        $newLon = $centerLon + ($x / cos(deg2rad($centerLat)));

        return [
            'lat' => round($newLat, 6),
            'lon' => round($newLon, 6),
        ];
    }
}
