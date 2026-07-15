<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\ScheduleSlot;
use Illuminate\Database\Seeder;

class ScheduleSlotSeeder extends Seeder
{
    public function run(): void
    {
        // Horario simplificado de prueba: Lunes a Viernes, solo 2 bloques por día,
        // para las materias principales de cada sección (1° A y 1° B)
        $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

        $materiasPrueba = ['Matemática', 'Comunicación'];

        $secciones = ['1° A', '1° B'];

        foreach ($secciones as $seccionName) {
            foreach ($materiasPrueba as $index => $materia) {
                $course = Course::whereHas('section', function ($q) use ($seccionName) {
                        $q->where('name', $seccionName)->where('year', 2026);
                    })
                    ->where('name', $materia)
                    ->first();

                if (!$course) {
                    continue;
                }

                foreach ($dias as $dia) {
                    $startHour = 7 + $index; // Matemática 7:30, Comunicación 8:30
                    ScheduleSlot::create([
                        'course_id'  => $course->id,
                        'day_of_week'=> $dia,
                        'start_time' => sprintf('%02d:30', $startHour),
                        'end_time'   => sprintf('%02d:10', $startHour + 1),
                        'classroom'  => 'Aula ' . $seccionName,
                    ]);
                }
            }
        }
    }
}
