<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NeoFeedController extends Controller
{
    public function feeds(Request $request)
    {
        $response = Http::get("https://api.nasa.gov/neo/rest/v1/feed?start_date=$request->start_date&end_date=$request->end_date&api_key=AEP1xIJzHen78nFrLYw5i7u3rUlOwvbNgpFm9agU");
        if ($response->successful()) {
            $resData = $response->object();
            $earthObjects = $resData->near_earth_objects;
            $asteroids = [];
            $dates = [];
            $noOfAsteroids = [];
            $fastest = [];
            $closest = [];
            $avgEstimatedDiameter = [];
            foreach ($earthObjects as $key => $value) {
                $dates[] = $key;
                $noOfAsteroids[] = count($value);

                foreach ($value as $item) {
                    $averageKm = ($item->estimated_diameter->kilometers->estimated_diameter_min + $item->estimated_diameter->kilometers->estimated_diameter_max) / 2;
                    $data = [
                        'dates' => $key,
                        'id' => $value[0]->id,
                        'neo_reference_id' => $value[0]->neo_reference_id,
                        'kilometers_per_hour' => $item->close_approach_data[0]->relative_velocity->kilometers_per_hour,
                        'miss_distance_kilometers' => $item->close_approach_data[0]->miss_distance->kilometers,
                        'average_size_km' => $averageKm
                    ];
                    if (!$fastest || $item->close_approach_data[0]->relative_velocity->kilometers_per_hour >= $fastest['kilometers_per_hour']) {
                        $fastest = $data;
                    }

                    if (!$closest || $item->close_approach_data[0]->miss_distance->kilometers <= $closest['miss_distance_kilometers']) {
                        $closest = $data;
                    }

                    $avgEstimatedDiameter[] = $averageKm;
                    $asteroids[] = $data;
                }
            }
            $fastestKM = max(array_column($asteroids, 'kilometers_per_hour'));
            /** echo ($fastestKM === $fastest['kilometers_per_hour']); Check with inbuilt function */

            $closestKM = min(array_column($asteroids, 'miss_distance_kilometers'));
            /** echo ($closestKM === $closest['miss_distance_kilometers']); Check with inbuilt function */

            $avgSize = array_sum($avgEstimatedDiameter) / count($avgEstimatedDiameter);
            return response()->json([
                'status' => 200,
                'message' => 'Data has been fetched',
                'data' => [
                    'fastest_asteroid' => $fastest,
                    'closest_asteroid' => $closest,
                    'average_size' => $avgSize,
                    'dates' => $dates,
                    'noOfAsteroids' => $noOfAsteroids,
                    'asteroids' => $asteroids
                ]
            ], 200);
        }
    }
}