<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Añade la columna `year` a enrollments y un índice único (student_id, year).
 *
 * Sin esta restricción un alumno puede quedar matriculado en dos secciones
 * distintas del mismo año si alguien envía peticiones directas al servidor,
 * causando duplicados en reportes, notas y asistencia.
 *
 * La columna se rellena desde sections.year para todos los registros existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Añadir columna; el default 0 es temporal y se sobreescribe en el paso 2
        Schema::table('enrollments', function (Blueprint $table) {
            $table->smallInteger('year')->default(0)->after('section_id');
        });

        // 2. Rellenar con el año real de cada sección
        DB::statement('UPDATE enrollments SET year = (SELECT year FROM sections WHERE sections.id = enrollments.section_id)');

        // 3. Agregar restricción única: un alumno solo puede tener una matrícula activa por año
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unique(['student_id', 'year'], 'enrollments_student_year_unique');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique('enrollments_student_year_unique');
            $table->dropColumn('year');
        });
    }
};
