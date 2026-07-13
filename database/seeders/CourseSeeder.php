<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\TeacherProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        // Currículo nacional de secundaria — 11 materias, 35h/semana
        $courseConfig = [
            ['Matemática',                               5],
            ['Comunicación',                             5],
            ['Inglés',                                   3],
            ['Ciencia y Tecnología',                     4],
            ['Ciencias Sociales',                        4],
            ['Desarrollo Personal, Ciudadanía y Cívica', 2],
            ['Arte y Cultura',                           2],
            ['Educación para el Trabajo',                2],
            ['Educación Física',                         3],
            ['Educación Religiosa',                      2],
            ['Lengua Originaria',                        3],
        ];

        // Construir pools de docentes por especialidad (IDs ordenados)
        $teacherPools = [];
        TeacherProfile::orderBy('teacher_id')->get()->each(function ($profile) use (&$teacherPools) {
            $teacherPools[$profile->especialidad][] = $profile->teacher_id;
        });

        // Primero secciones 2026 (50), luego 2025 (10) — el índice determina qué docente asignar
        $sections2026 = Section::where('year', 2026)->orderBy('grade')->orderBy('name')->get();
        $sections2025 = Section::where('year', 2025)->orderBy('name')->get();
        $sections     = $sections2026->concat($sections2025);

        $rows = [];
        $now  = now();

        foreach ($sections as $sectionIdx => $section) {
            foreach ($courseConfig as [$subjectName, $hours]) {
                $pool      = $teacherPools[$subjectName] ?? [];
                $teacherId = $pool[$sectionIdx % count($pool)];

                $rows[] = [
                    'name'           => $subjectName,
                    'section_id'     => $section->id,
                    'teacher_id'     => $teacherId,
                    'hours_per_week' => $hours,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('courses')->insert($chunk);
        }
        // 11 materias × 60 secciones = 660 cursos
        // Cada docente cubre ~5-10 secciones según horas de su materia (≤30h/semana)
    }
}
