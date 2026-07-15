<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        Section::create([
            'name'  => '1° A',
            'grade' => 1,
            'year'  => 2026,
        ]);

        Section::create([
            'name'  => '1° B',
            'grade' => 1,
            'year'  => 2026,
        ]);
    }
}
