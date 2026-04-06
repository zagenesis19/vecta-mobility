<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Calcula los segundos exactos que faltan hasta el próximo corte del BCV.
     * El BCV actualiza a las 9:00 AM y 5:00 PM (hora Venezuela, UTC-4).
     */
    protected function secondsUntilNextBcvUpdate(): int
    {
        $now = Carbon::now('America/Caracas');

        $cutoff9am  = $now->copy()->setTime(9, 0, 0);
        $cutoff5pm  = $now->copy()->setTime(17, 0, 0);

        if ($now->lt($cutoff9am)) {
            // Antes de las 9AM: próximo corte hoy a las 9AM
            $next = $cutoff9am;
        } elseif ($now->lt($cutoff5pm)) {
            // Entre 9AM y 5PM: próximo corte hoy a las 5PM
            $next = $cutoff5pm;
        } else {
            // Después de las 5PM: próximo corte mañana a las 9AM
            $next = $cutoff9am->addDay();
        }

        // Mínimo 60 segundos para evitar TTL de cero
        return max($now->diffInSeconds($next), 60);
    }

    /**
     * Obtiene la tasa BCV oficial desde la API externa con caché dinámica.
     * IMPORTANTE: Solo cachea valores válidos. Nunca cachea null para
     * evitar "cache poisoning" si la API falla al primer intento.
     */
    protected function getBcvRate(): float|null
    {
        try {
            // 1. ¿Tenemos un valor válido en caché?
            $cached = Cache::get('bcv_rate_oficial');
            if ($cached !== null && is_numeric($cached)) {
                return (float) $cached;
            }

            // 2. No hay caché válida → consultar la API
            $response = Http::timeout(5)->get('https://ve.dolarapi.com/v1/dolares/oficial');

            if ($response->successful()) {
                $value = $response->json('promedio');
                if (is_numeric($value)) {
                    $ttl = $this->secondsUntilNextBcvUpdate();
                    Cache::put('bcv_rate_oficial', (float) $value, $ttl);
                    return (float) $value;
                }
            }

            // 3. API caída → no cacheamos null, próxima request lo reintentará
            return null;

        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                // Usamos fresh() para asegurarnos de que los 'appends' (average_rating) se calculen con datos frescos de la BD
                'user' => $request->user() ? $request->user()->fresh()->toArray() : null,
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            // Tasa BCV oficial (Bs./USD). Se cachea hasta el próximo corte del BCV (9AM o 5PM VET).
            // Si la API externa falla, se comparte null y el frontend lo maneja gracefully.
            'bcv_rate' => $this->getBcvRate(),
        ];
    }
}