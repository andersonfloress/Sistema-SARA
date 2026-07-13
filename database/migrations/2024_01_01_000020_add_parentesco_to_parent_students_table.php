<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El parentesco va en la tabla pivote porque una misma persona
     * puede ser "padre" de un alumno y "tutor" de otro.
     */
    public function up(): void
    {
        Schema::table('parent_students', function (Blueprint $table) {
            $table->enum('parentesco', [
                'padre', 'madre', 'tutor', 'tutora',
                'abuelo', 'abuela', 'tio', 'tia', 'otro',
            ])->nullable()->after('student_id');
        });
    }

    public function down(): void
    {
        Schema::table('parent_students', function (Blueprint $table) {
            $table->dropColumn('parentesco');
        });
    }
};
