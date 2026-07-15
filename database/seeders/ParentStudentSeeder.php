<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ParentStudent;
use Illuminate\Database\Seeder;

class ParentStudentSeeder extends Seeder
{
    public function run(): void
    {
        // Cada padre con su hijo correspondiente (1 a 1 en esta prueba)
        $relaciones = [
            ['gapaza.padre@gmail.com',  'alumno001@santarosa.edu.pe', 'padre'],
            ['dcallo.madre@gmail.com',  'alumno002@santarosa.edu.pe', 'madre'],
            ['mticona.padre@gmail.com', 'alumno003@santarosa.edu.pe', 'padre'],
            ['rhuanca.madre@gmail.com', 'alumno004@santarosa.edu.pe', 'madre'],
        ];

        foreach ($relaciones as [$parentEmail, $studentEmail, $parentesco]) {
            $parent  = User::where('email', $parentEmail)->first();
            $student = User::where('email', $studentEmail)->first();

            if (!$parent || !$student) {
                continue;
            }

            ParentStudent::create([
                'parent_id'  => $parent->id,
                'student_id' => $student->id,
                'parentesco' => $parentesco,
            ]);
        }
    }
}
