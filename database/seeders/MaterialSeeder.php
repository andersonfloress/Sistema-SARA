<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Material;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $materiales = [
            [
                'title'       => 'Guía de Álgebra y Funciones',
                'type'        => 'document',
                'url'         => 'https://recursos.perueduca.pe/matematica-algebra.pdf',
                'description' => 'Material de estudio sobre álgebra básica.',
                'materia'     => 'Matemática',
                'seccion'     => '1° A',
            ],
            [
                'title'       => 'Khan Academy — Matemáticas',
                'type'        => 'link',
                'url'         => 'https://es.khanacademy.org/math',
                'description' => 'Plataforma interactiva de matemáticas.',
                'materia'     => 'Matemática',
                'seccion'     => '1° A',
            ],
            [
                'title'       => 'Guía de Comprensión Lectora',
                'type'        => 'link',
                'url'         => 'https://www.lecturayvida.fahce.unlp.edu.ar/',
                'description' => 'Recursos para comprensión de textos.',
                'materia'     => 'Comunicación',
                'seccion'     => '1° B',
            ],
        ];

        foreach ($materiales as $m) {
            $course = Course::whereHas('section', function ($q) use ($m) {
                    $q->where('name', $m['seccion'])->where('year', 2026);
                })
                ->where('name', $m['materia'])
                ->first();

            if (!$course) {
                continue;
            }

            Material::create([
                'title'       => $m['title'],
                'type'        => $m['type'],
                'url'         => $m['url'],
                'description' => $m['description'],
                'course_id'   => $course->id,
                'teacher_id'  => $course->teacher_id,
            ]);
        }
    }
}
