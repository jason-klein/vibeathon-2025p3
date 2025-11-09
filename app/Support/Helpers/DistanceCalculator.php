<?php

namespace App\Support\Helpers;

class DistanceCalculator
{
    /**
     * Calculate the distance between two points using the Haversine formula.
     *
     * @param  float|null  $lat1  Latitude of the first point
     * @param  float|null  $lon1  Longitude of the first point
     * @param  float|null  $lat2  Latitude of the second point
     * @param  float|null  $lon2  Longitude of the second point
     * @return float|null Distance in miles, or null if any coordinate is missing
     */
    public static function calculate(?float $lat1, ?float $lon1, ?float $lat2, ?float $lon2): ?float
    {
        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            return null;
        }

        // Earth's radius in miles
        $earthRadius = 3959;

        // Convert degrees to radians
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        // Haversine formula
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Format distance with appropriate precision.
     *
     * @param  float|null  $distance  Distance in miles
     * @return string Formatted distance string
     */
    public static function format(?float $distance): string
    {
        if ($distance === null) {
            return 'Distance unknown';
        }

        return number_format($distance, 1).' miles away';
    }
}
