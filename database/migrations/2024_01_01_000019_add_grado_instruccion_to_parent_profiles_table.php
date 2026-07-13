<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->enum('grado_instruccion', [
                'sin_instruccion', 'primaria', 'secundaria',
                'tecnico', 'universitario', 'posgrado',
            ])->nullable()->after('ocupacion');
        });
    }

    public function down(): void
    {
        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->dropColumn('grado_instruccion');
        });
    }
};
