<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Database\Seeder;

class StudentProfileSeeder extends Seeder
{
    public function run(): void
    {
        $alumnos = [
            ['alumno001@santarosa.edu.pe', 'EST-0001', '71000001', '2013-01-15', 'M'],
            ['alumno002@santarosa.edu.pe', 'EST-0002', '71000002', '2013-03-22', 'F'],
            ['alumno003@santarosa.edu.pe', 'EST-0003', '71000003', '2013-05-10', 'M'],
            ['alumno004@santarosa.edu.pe', 'EST-0004', '71000004', '2013-07-08', 'F'],
        ];

        foreach ($alumnos as [$email, $codigo, $dni, $fechaNac, $sexo]) {
            $student = User::where('email', $email)->first();

            if (!$student) {
                continue;
            }

            StudentProfile::create([
                'student_id'        => $student->id,
                'codigo_estudiante' => $codigo,
                'dni'               => $dni,
                'fecha_nacimiento'  => $fechaNac,
                'sexo'              => $sexo,
                'grado'             => 1,
                'turno'             => 'mañana',
                'direccion'         => 'Jr. Puno ' . rand(100, 999) . ', Puno',
                'telefono'          => '9510000' . rand(10, 99),
            ]);
        }
    }
}
