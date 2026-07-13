<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // null = en curso | 'approved' = aprobado | 'retained' = repitente | 'graduated' = egresado (5°)
            $table->string('result')->nullable()->after('enrolled_at');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('result');
        });
    }
};
