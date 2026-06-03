<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

class GeofencingService
{
    /**
     * Earth's radius in meters.
     */
    private const float EARTH_RADIUS = 6_371_000.0;

    private readonly float $creLatitude;
    private readonly float $creLongitude;

    public function __construct()
    {
        // Use database settings (configured by admin via Settings page) as the single source of truth.
        // Fall back to .env values if the settings table has not been initialized yet.
        $this->creLatitude  = (float) (Setting::getValue('cre_latitude')  ?: config('geofencing.cre_latitude'));
        $this->creLongitude = (float) (Setting::getValue('cre_longitude') ?: config('geofencing.cre_longitude'));
    }


    /**
     * Calculate the distance in meters between two GPS coordinates
     * using the Haversine formula.
     */
    public function haversineDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2,
    ): float {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1))
            * cos(deg2rad($lat2))
            * sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS * $c;
    }

    /**
     * Calculate the distance from the CRE premises.
     */
    public function distanceFromCre(float $latitude, float $longitude): float
    {
        return $this->haversineDistance(
            $this->creLatitude,
            $this->creLongitude,
            $latitude,
            $longitude,
        );
    }

    /**
     * Check if the given coordinates are within the allowed radius of the CRE.
     */
    public function isWithinPremises(float $latitude, float $longitude, float $maxMeters = 20.0): bool
    {
        return $this->distanceFromCre($latitude, $longitude) <= $maxMeters;
    }
}
