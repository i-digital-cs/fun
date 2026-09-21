<?php

namespace App\Support;

final class GeoDistance
{
    /**
     * Haversine distance in kilometers between two WGS84 points.
     */
    public static function kilometers(
        float $latFrom,
        float $lonFrom,
        float $latTo,
        float $lonTo,
    ): float {
        $earthRadiusKm = 6371.0;

        $latFromRad = deg2rad($latFrom);
        $latToRad = deg2rad($latTo);
        $deltaLat = deg2rad($latTo - $latFrom);
        $deltaLon = deg2rad($lonTo - $lonFrom);

        $a = sin($deltaLat / 2) ** 2
            + cos($latFromRad) * cos($latToRad) * sin($deltaLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    public static function isWithinKm(
        float $latFrom,
        float $lonFrom,
        float $latTo,
        float $lonTo,
        int $maxKm,
    ): bool {
        return self::kilometers($latFrom, $lonFrom, $latTo, $lonTo) <= $maxKm;
    }
}
