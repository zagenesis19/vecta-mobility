<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Municipality;

class MunicipalitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $municipalities = [
            [
                'name' => 'Urdaneta',
                'capital' => 'Cúa',
                'description' => 'Municipio Urdaneta, capital Cúa. Conocida como La Perla del Tuy.'
            ],
            [
                'name' => 'Cristóbal Rojas',
                'capital' => 'Charallave',
                'description' => 'Municipio Cristóbal Rojas, capital Charallave. Importante nodo de transporte. Ciudad del Cemento.'
            ],
            [
                'name' => 'Tomás Lander',
                'capital' => 'Ocumare del Tuy',
                'description' => 'Municipio Tomás Lander, capital Ocumare del Tuy. Historia y tradición.'
            ],
            [
                'name' => 'Simón Bolívar',
                'capital' => 'San Francisco de Yare',
                'description' => 'Municipio Simón Bolívar, capital San Francisco de Yare. Hogar de los Diablos Danzantes.'
            ],
            [
                'name' => 'Independencia',
                'capital' => 'Santa Teresa del Tuy',
                'description' => 'Municipio Independencia, capital Santa Teresa del Tuy. Centro comercial y agrícola.'
            ],
            [
                'name' => 'Paz Castillo',
                'capital' => 'Santa Lucía del Tuy',
                'description' => 'Municipio Paz Castillo, capital Santa Lucía del Tuy. Cuna cultural.'
            ],
        ];

        foreach ($municipalities as $municipality) {
            Municipality::firstOrCreate(
                ['name' => $municipality['name']],
                $municipality
            );
        }
    }
}
