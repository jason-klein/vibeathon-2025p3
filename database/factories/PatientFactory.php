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
        // Joplin, MO center coordinates
        $centerLat = 37.0842;
        $centerLon = -94.5133;
        $radiusMiles = 10;

        $coords = $this->generateRandomCoordinates($centerLat, $centerLon, $radiusMiles);

        return [
            'user_id' => User::factory(),
            'summary' => fake()->optional()->paragraph(),
            'latitude' => $coords['lat'],
            'longitude' => $coords['lon'],
            'plain_english_record' => fake()->optional()->paragraphs(3, true),
            'executive_summary' => fake()->optional()->paragraph(),
            'executive_summary_updated_at' => fake()->optional()->dateTimeBetween('-30 days'),
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
