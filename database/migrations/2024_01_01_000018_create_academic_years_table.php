<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year')->unique();
            $table->enum('status', ['planning', 'enrollment_open'])->default('planning');
            $table->unsignedSmallInteger('default_capacity')->default(30);
            $table->timestamp('enrollment_opened_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
