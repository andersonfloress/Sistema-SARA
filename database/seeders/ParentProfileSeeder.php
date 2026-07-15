<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ParentProfile;
use Illuminate\Database\Seeder;

class ParentProfileSeeder extends Seeder
{
    public function run(): void
    {
        $padres = [
            ['gapaza.padre@gmail.com',  '45000001', 'primaria'],
            ['dcallo.madre@gmail.com',  '45000002', 'primaria'],
            ['mticona.padre@gmail.com', '45000003', 'secundaria'],
            ['rhuanca.madre@gmail.com', '45000004', 'secundaria'],
        ];

        foreach ($padres as [$email, $dni, $gradoInstruccion]) {
            $parent = User::where('email', $email)->first();

            if (!$parent) {
                continue;
            }

            ParentProfile::create([
                'parent_id'         => $parent->id,
                'dni'               => $dni,
                'telefono'          => '9520000' . rand(10, 99),
                'direccion'         => 'Jr. Puno ' . rand(100, 999) . ', Puno',
                'ocupacion'         => 'Comerciante',
                'grado_instruccion' => $gradoInstruccion,
            ]);
        }
    }
}
