<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        // 2025 — finalizado (historial de alumnos de 5°)
        AcademicYear::create([
            'year'                => 2025,
            'status'              => 'finished',
            'default_capacity'    => 30,
            'enrollment_opened_at'=> '2025-01-20 08:00:00',
        ]);

        // 2026 — año activo en curso
        AcademicYear::create([
            'year'                => 2026,
            'status'              => 'enrollment_open',
            'default_capacity'    => 30,
            'enrollment_opened_at'=> '2026-01-15 08:00:00',
        ]);
    }
}
