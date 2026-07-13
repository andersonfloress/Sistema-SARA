<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\ScheduleSlot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $now   = now();

        // Últimas 4 semanas de días hábiles (lunes a viernes)
        $endDate   = Carbon::parse('2026-07-11'); // último viernes antes de hoy
        $startDate = $endDate->copy()->subWeeks(4)->startOfWeek(Carbon::MONDAY);

        // Solo matrículas del año 2026
        $enrollments = Enrollment::where('year', 2026)
                                  ->with('section.courses')
                                  ->get();

        // Un curso solo tiene asistencia los días que REALMENTE dicta clase,
        // según su horario (schedule_slots) — un curso de 2 o 3 veces por
        // semana no debe generar (ni pedir) registro los demás días.
        $dayKeys = ['lunes' => 1, 'martes' => 2, 'miercoles' => 3, 'jueves' => 4, 'viernes' => 5];
        $courseDaysMap = ScheduleSlot::query()
            ->select('course_id', 'day_of_week')
            ->distinct()
            ->get()
            ->groupBy('course_id')
            ->map(fn($rows) => $rows->pluck('day_of_week')
                ->map(fn($day) => $dayKeys[$day] ?? null)
                ->filter()
                ->unique());

        $rows = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->isWeekend()) continue;

            // Lunes tienen más inasistencias (resaca del fin de semana)
            $isMonday = $date->dayOfWeek === Carbon::MONDAY;
            $isoDow   = $date->dayOfWeekIso; // 1=lunes ... 5=viernes

            foreach ($enrollments as $enrollment) {
                foreach ($enrollment->section->courses as $course) {
                    // Saltar si el curso no tiene clase programada ese día de la semana
                    $courseDays = $courseDaysMap->get($course->id, collect());
                    if (!$courseDays->contains($isoDow)) continue;

                    // Distribución realista:
                    // Lunes:        90% presente, 6% ausente, 3% tardanza, 1% justificado
                    // Otros días:   93% presente, 4% ausente, 2% tardanza, 1% justificado
                    $r = rand(1, 100);

                    if ($isMonday) {
                        $status = match (true) {
                            $r <= 90 => 'present',
                            $r <= 96 => 'absent',
                            $r <= 99 => 'late',
                            default  => 'justified',
                        };
                    } else {
                        $status = match (true) {
                            $r <= 93 => 'present',
                            $r <= 97 => 'absent',
                            $r <= 99 => 'late',
                            default  => 'justified',
                        };
                    }

                    $rows[] = [
                        'student_id' => $enrollment->student_id,
                        'course_id'  => $course->id,
                        'date'       => $date->toDateString(),
                        'status'     => $status,
                        'created_by' => $admin->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if (count($rows) >= 1000) {
                        DB::table('attendances')->insert($rows);
                        $rows = [];
                    }
                }
            }
        }

        if (!empty($rows)) {
            DB::table('attendances')->insert($rows);
        }
        // ~1500 alumnos × 11 cursos × 20 días hábiles ≈ 330 000 registros
    }
}
