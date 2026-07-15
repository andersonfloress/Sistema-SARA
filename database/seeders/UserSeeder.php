<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ADMIN
        User::create([
            'name'     => 'Dirección IE Santa Rosa',
            'email'    => 'admin@santarosa.edu.pe',
            'password' => bcrypt('admin123'), // contraseña real: admin123
            'role'     => 'admin',
        ]);

        // DOCENTES — 11, uno por cada materia del currículo
        $docentes = [
            ['Abelardo Mamani Apaza',  'amamani.docente@santarosa.edu.pe'],
            ['Beatriz Callo Larico',   'bcallo.docente@santarosa.edu.pe'],
            ['Carlos Quispe Huanca',   'cquispe.docente@santarosa.edu.pe'],
            ['Doris Turpo Ccama',      'dturpo.docente@santarosa.edu.pe'],
            ['Edgar Flores Ramos',     'eflores.docente@santarosa.edu.pe'],
            ['Fabiola Chura Yana',     'fchura.docente@santarosa.edu.pe'],
            ['Gustavo Pari Condori',   'gpari.docente@santarosa.edu.pe'],
            ['Herminia Vilca Suca',    'hvilca.docente@santarosa.edu.pe'],
            ['Ismael Ticona Zapana',   'iticona.docente@santarosa.edu.pe'],
            ['Julia Huanca Poma',      'jhuanca.docente@santarosa.edu.pe'],
            ['Félix Condori Mamani',   'fcondori.docente@santarosa.edu.pe'],
        ];

        foreach ($docentes as [$name, $email]) {
            User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => bcrypt('doc123'), // contraseña real: doc123
                'role'     => 'teacher',
            ]);
        }

        // ALUMNOS — 4 de prueba
        $alumnos = [
            ['Alumno Uno',  'alumno001@santarosa.edu.pe'],
            ['Alumno Dos',  'alumno002@santarosa.edu.pe'],
            ['Alumno Tres', 'alumno003@santarosa.edu.pe'],
            ['Alumno Cuatro','alumno004@santarosa.edu.pe'],
        ];

        foreach ($alumnos as [$name, $email]) {
            User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => bcrypt('alu123'), // contraseña real: alu123
                'role'     => 'student',
            ]);
        }

        // PADRES — 4 de prueba
        $padres = [
            ['Gregorio Apaza Lupaca', 'gapaza.padre@gmail.com'],
            ['Dionisia Callo Coila',  'dcallo.madre@gmail.com'],
            ['Marcelino Ticona Flores','mticona.padre@gmail.com'],
            ['Rosa Huanca Suca',      'rhuanca.madre@gmail.com'],
        ];

        foreach ($padres as [$name, $email]) {
            User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => bcrypt('pad123'), // contraseña real: pad123
                'role'     => 'parent',
            ]);
        }
    }
}
