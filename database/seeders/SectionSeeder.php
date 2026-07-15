<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        // Prueba: 2 secciones del año 2026 (para los 4 alumnos de prueba)
        Section::create([
            'name'        => '1° A',
            'grade'       => 1,
            'year'        => 2026,
            'turno'       => 'mañana',
            'cupo_maximo' => 30,
        ]);

        Section::create([
            'name'        => '1° B',
            'grade'       => 1,
            'year'        => 2026,
            'turno'       => 'mañana',
            'cupo_maximo' => 30,
        ]);
    }
}
