<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $tareas = [
            [
                'title'       => 'Tarea 1 — Funciones lineales',
                'description' => 'Resolver los ejercicios 1 al 10 del libro página 45.',
                'materia'     => 'Matemática',
                'seccion'     => '1° A',
                'due_date'    => '2026-08-15 23:59:00',
            ],
            [
                'title'       => 'Tarea 1 — Comprensión de textos',
                'description' => 'Leer el texto asignado y responder el cuestionario.',
                'materia'     => 'Comunicación',
                'seccion'     => '1° B',
                'due_date'    => '2026-08-20 23:59:00',
            ],
        ];

        foreach ($tareas as $t) {
            $course = Course::whereHas('section', function ($q) use ($t) {
                    $q->where('name', $t['seccion'])->where('year', 2026);
                })
                ->where('name', $t['materia'])
                ->first();

            if (!$course) {
                continue;
            }

            Task::create([
                'title'       => $t['title'],
                'description' => $t['description'],
                'course_id'   => $course->id,
                'teacher_id'  => $course->teacher_id,
                'due_date'    => $t['due_date'],
            ]);
        }
    }
}
