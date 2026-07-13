<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->date('fecha_nacimiento')->nullable()->after('dni');
            $table->enum('sexo', ['M', 'F'])->nullable()->after('fecha_nacimiento');
            $table->string('foto_perfil')->nullable()->after('sexo');
            $table->string('correo_alternativo')->nullable()->after('telefono');
            $table->string('contacto_emergencia_nombre')->nullable()->after('direccion');
            $table->string('contacto_emergencia_telefono', 20)->nullable()->after('contacto_emergencia_nombre');
            $table->date('fecha_ingreso')->nullable()->after('contacto_emergencia_telefono');
            $table->enum('condicion_laboral', ['nombrado', 'contratado'])->nullable()->after('fecha_ingreso');
            $table->enum('nivel_academico', ['bachiller', 'licenciado', 'magister', 'doctor'])->nullable()->after('condicion_laboral');
            $table->string('numero_colegiatura', 30)->nullable()->after('nivel_academico');
            $table->enum('turno', ['mañana', 'tarde', 'ambos'])->nullable()->after('numero_colegiatura');
            $table->string('cv_path')->nullable()->after('turno');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'fecha_nacimiento', 'sexo', 'foto_perfil', 'correo_alternativo',
                'contacto_emergencia_nombre', 'contacto_emergencia_telefono',
                'fecha_ingreso', 'condicion_laboral', 'nivel_academico',
                'numero_colegiatura', 'turno', 'cv_path',
            ]);
        });
    }
};
