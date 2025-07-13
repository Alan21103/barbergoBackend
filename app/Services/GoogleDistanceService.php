<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDistanceService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GOOGLE_API_KEY');
    }

    public function getGoogleDistance($lat1, $lon1, $lat2, $lon2)
    {
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json";

        $response = Http::get($url, [
            'origins' => "$lat1,$lon1",
            'destinations' => "$lat2,$lon2",
            'key' => $this->apiKey,
        ]);

        $data = $response->json();
        Log::info('Google Distance API Response:', $data);

        if (
            isset($data['rows'][0]['elements'][0]['status']) &&
            $data['rows'][0]['elements'][0]['status'] === 'OK' &&
            isset($data['rows'][0]['elements'][0]['distance']['value'])
        ) {
            $distance = $data['rows'][0]['elements'][0]['distance']['value'];
            return $distance / 1000; // meter to kilometer
        }

        // Fallback jika API gagal
        Log::warning('Google API failed, using fallback Haversine.');
        return $this->haversine($lat1, $lon1, $lat2, $lon2);
    }

    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $lat1 = deg2rad($lat1);
        $lat2 = deg2rad($lat2);

        $a = sin($dLat / 2) ** 2 +
             cos($lat1) * cos($lat2) *
             sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    public function calculateOngkir($lat1, $lon1, $lat2, $lon2)
    {
        $distance = $this->getGoogleDistance($lat1, $lon1, $lat2, $lon2);
        $ongkir = $distance * 5000;
        return min($ongkir, 100000);
    }

    public function formatOngkir($ongkir)
    {
        return "Rp " . number_format($ongkir, 0, ',', '.');
    }
}
