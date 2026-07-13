<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->enum('turno', ['mañana', 'tarde'])->default('mañana')->after('grade');
            $table->unsignedSmallInteger('cupo_maximo')->default(30)->after('turno');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn(['turno', 'cupo_maximo']);
        });
    }
};
