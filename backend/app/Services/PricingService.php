<?php

namespace App\Services;

class PricingService
{
    // Tarifas en Dólares (o unidad monetaria base)
    const BASE_RATE_CAR = 3.00;
    const PER_KM_CAR = 0.50;
    const PER_MIN_CAR = 0.10;

    const BASE_RATE_MOTO = 2.00;
    const PER_KM_MOTO = 0.30;
    const PER_MIN_MOTO = 0.05;

    const APP_FEE_PERCENTAGE = 0.10; // 10%

    /**
     * Calcular precio estimado del viaje
     */
    public function calculatePrice(float $distanceKm, float $durationMin, string $vehicleType = 'car'): array
    {
        $base = $vehicleType === 'motorcycle' ? self::BASE_RATE_MOTO : self::BASE_RATE_CAR;
        $perKm = $vehicleType === 'motorcycle' ? self::PER_KM_MOTO : self::PER_KM_CAR;
        $perMin = $vehicleType === 'motorcycle' ? self::PER_MIN_MOTO : self::PER_MIN_CAR;

        $subtotal = $base + ($distanceKm * $perKm) + ($durationMin * $perMin);
        
        // Mínimo de carrera
        $minPrice = $vehicleType === 'motorcycle' ? 3.00 : 5.00;
        $finalPrice = max($minPrice, $subtotal);

        // Redondear a 2 decimales
        $finalPrice = round($finalPrice, 2);

        $appFee = round($finalPrice * self::APP_FEE_PERCENTAGE, 2);
        $driverEarnings = $finalPrice - $appFee;

        return [
            'total' => $finalPrice,
            'app_fee' => $appFee,
            'driver_earnings' => $driverEarnings,
            'breakdown' => [
                'base' => $base,
                'distance_cost' => round($distanceKm * $perKm, 2),
                'time_cost' => round($durationMin * $perMin, 2),
            ]
        ];
    }

    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $km = $miles * 1.609344;

        return round($km, 2);
    }
}
