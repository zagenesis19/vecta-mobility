<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\VehicleLocation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLocationUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $driverId;
    protected float $latitude;
    protected float $longitude;
    protected ?float $speed;
    protected ?float $heading;
    protected ?int $tripId;
    protected ?int $municipalityId;
    protected string $recordedAt;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $driverId,
        float $latitude,
        float $longitude,
        ?float $speed = null,
        ?float $heading = null,
        ?int $tripId = null,
        ?int $municipalityId = null,
        ?string $recordedAt = null
    ) {
        $this->driverId = $driverId;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->speed = $speed;
        $this->heading = $heading;
        $this->tripId = $tripId;
        $this->municipalityId = $municipalityId;
        $this->recordedAt = $recordedAt ?? now()->toDateTimeString();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // 1. Insertar el punto de telemetría en el histórico (Breadcrumb trail)
            VehicleLocation::create([
                'driver_id' => $this->driverId,
                'trip_id' => $this->tripId,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'speed' => $this->speed,
                'heading' => $this->heading,
                'municipality_id' => $this->municipalityId,
                'recorded_at' => $this->recordedAt,
            ]);

            // 2. Actualizar el snapshot en la tabla users (lectura rápida de respaldo)
            User::where('id', $this->driverId)->update([
                'current_lat' => $this->latitude,
                'current_lng' => $this->longitude,
            ]);
        } catch (\Throwable $e) {
            Log::error("Error procesando actualización de ubicación para conductor {$this->driverId}: " . $e->getMessage());
        }
    }
}
