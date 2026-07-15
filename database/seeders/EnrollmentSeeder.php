<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Section;
use App\Models\Enrollment;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $seccionA = Section::where('name', '1° A')->where('year', 2026)->first();
        $seccionB = Section::where('name', '1° B')->where('year', 2026)->first();

        // 2 alumnos en cada sección
        $matriculas = [
            ['alumno001@santarosa.edu.pe', $seccionA],
            ['alumno002@santarosa.edu.pe', $seccionA],
            ['alumno003@santarosa.edu.pe', $seccionB],
            ['alumno004@santarosa.edu.pe', $seccionB],
        ];

        foreach ($matriculas as [$email, $section]) {
            $student = User::where('email', $email)->first();

            if (!$student || !$section) {
                continue;
            }

            Enrollment::create([
                'student_id'  => $student->id,
                'section_id'  => $section->id,
                'enrolled_at' => '2026-03-03',
                'result'      => null,
                'year'        => 2026,
            ]);
        }
    }
}
