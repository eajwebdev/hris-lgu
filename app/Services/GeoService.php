<?php

namespace App\Services;

use App\Models\AttendanceStation;

/**
 * Where a punch happened, relative to the configured stations.
 *
 * Policy note that shapes the whole class: distance never blocks a punch. An
 * employee on field work is legitimately far from every station, so the output
 * here is a *tag* for HR to read, not a gate. That is also why "no location" and
 * "no stations configured" resolve to out_of_range = null rather than true —
 * flagging someone red because GPS was off would start exactly the arguments
 * this feature exists to end.
 */
class GeoService
{
    /**
     * Metres between two coordinates, by the haversine formula. Accurate to
     * well under a metre at municipal scale, which is far tighter than the
     * ~10–50 m the phone's GPS fix is good for anyway.
     */
    public function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1.0, sqrt($a)));
    }

    /**
     * Tag a punch location against the active stations.
     *
     * Returns station_id/name/distance of the *nearest* station and whether the
     * point falls outside that station's radius. Everything null when there is
     * nothing to judge against.
     */
    public function resolve(?float $lat, ?float $lng): array
    {
        $blank = [
            'station_id'   => null,
            'station_name' => null,
            'distance_m'   => null,
            'out_of_range' => null,
        ];

        if ($lat === null || $lng === null) {
            return $blank;
        }

        $stations = AttendanceStation::active()->get();

        if ($stations->isEmpty()) {
            return $blank;
        }

        $nearest  = null;
        $shortest = INF;

        foreach ($stations as $station) {
            $distance = $this->distanceMeters($lat, $lng, $station->lat, $station->lng);

            if ($distance < $shortest) {
                $shortest = $distance;
                $nearest  = $station;
            }
        }

        return [
            'station_id'   => $nearest->id,
            'station_name' => $nearest->name,
            'distance_m'   => (int) round($shortest),
            'out_of_range' => $shortest > $nearest->radius_m,
        ];
    }
}
