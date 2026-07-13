<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un mismo slot de sección/día/hora no puede estar duplicado.
     * La validación ya existe en el controlador; esto la refuerza a nivel de BD.
     *
     * La unicidad es por sección (derivada del curso), día y hora de inicio.
     * Como course_id ya implica la sección, usamos la combinación
     * (course_id, day_of_week, start_time) que es equivalente e indexable directamente.
     */
    public function up(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->unique(['course_id', 'day_of_week', 'start_time'], 'schedule_slots_course_day_time_unique');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->dropUnique('schedule_slots_course_day_time_unique');
        });
    }
};
