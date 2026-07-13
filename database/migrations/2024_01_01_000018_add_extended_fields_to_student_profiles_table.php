<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('nacionalidad', 80)->nullable()->after('sexo');
            $table->enum('tipo_sangre', ['A+','A-','B+','B-','AB+','AB-','O+','O-'])->nullable()->after('nacionalidad');
            $table->string('foto_perfil')->nullable()->after('tipo_sangre');
            $table->smallInteger('anio_ingreso')->nullable()->after('turno');
            $table->string('nombre_apoderado', 200)->nullable()->after('anio_ingreso');
            $table->string('dni_apoderado', 20)->nullable()->after('nombre_apoderado');
            $table->string('telefono_emergencia', 20)->nullable()->after('dni_apoderado');
            $table->text('condicion_especial')->nullable()->after('telefono_emergencia');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'nacionalidad', 'tipo_sangre', 'foto_perfil', 'anio_ingreso',
                'nombre_apoderado', 'dni_apoderado', 'telefono_emergencia', 'condicion_especial',
            ]);
        });
    }
};
