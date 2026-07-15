<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\TeacherProfile;
use Illuminate\Database\Seeder;

class TeacherProfileSeeder extends Seeder
{
    public function run(): void
    {
        $perfiles = [
            ['amamani.docente@santarosa.edu.pe', '42000001', 'DOC-001', 'Matemática'],
            ['bcallo.docente@santarosa.edu.pe',  '42000002', 'DOC-002', 'Comunicación'],
            ['cquispe.docente@santarosa.edu.pe', '42000003', 'DOC-003', 'Inglés'],
            ['dturpo.docente@santarosa.edu.pe',  '42000004', 'DOC-004', 'Ciencia y Tecnología'],
            ['eflores.docente@santarosa.edu.pe', '42000005', 'DOC-005', 'Ciencias Sociales'],
            ['fchura.docente@santarosa.edu.pe',  '42000006', 'DOC-006', 'Desarrollo Personal, Ciudadanía y Cívica'],
            ['gpari.docente@santarosa.edu.pe',   '42000007', 'DOC-007', 'Arte y Cultura'],
            ['hvilca.docente@santarosa.edu.pe',  '42000008', 'DOC-008', 'Educación para el Trabajo'],
            ['iticona.docente@santarosa.edu.pe', '42000009', 'DOC-009', 'Educación Física'],
            ['jhuanca.docente@santarosa.edu.pe', '42000010', 'DOC-010', 'Educación Religiosa'],
            ['fcondori.docente@santarosa.edu.pe','42000011', 'DOC-011', 'Lengua Originaria'],
        ];

        foreach ($perfiles as [$email, $dni, $codigo, $especialidad]) {
            $teacher = User::where('email', $email)->first();

            if (!$teacher) {
                continue;
            }

            TeacherProfile::create([
                'teacher_id'     => $teacher->id,
                'dni'            => $dni,
                'codigo_docente' => $codigo,
                'especialidad'   => $especialidad,
                'telefono'       => '9511000' . rand(10, 99),
                'direccion'      => 'Jr. Puno ' . rand(100, 999) . ', Puno',
            ]);
        }
    }
}
