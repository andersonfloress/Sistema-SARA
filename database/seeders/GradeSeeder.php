<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $periodos = ['I', 'II', 'III'];

        // Notas solo para los cursos con horario (Matemática y Comunicación)
        $materiasPrueba = ['Matemática', 'Comunicación'];

        $enrollments = Enrollment::where('year', 2026)->with('section')->get();

        foreach ($enrollments as $enrollment) {
            foreach ($materiasPrueba as $materia) {
                $course = Course::where('section_id', $enrollment->section_id)
                    ->where('name', $materia)
                    ->first();

                if (!$course) {
                    continue;
                }

                foreach ($periodos as $periodo) {
                    Grade::create([
                        'student_id'  => $enrollment->student_id,
                        'course_id'   => $course->id,
                        'period'      => $periodo,
                        'score'       => rand(11, 20),
                        'observation' => null,
                        'created_by'  => $admin->id,
                    ]);
                }
            }
        }
    }
}
