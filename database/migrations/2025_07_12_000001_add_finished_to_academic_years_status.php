<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el estado 'finished' al enum de academic_years.
 *
 * En SQLite el enum es TEXT sin restricción real — no hay nada que cambiar.
 * En PostgreSQL el enum de Laravel es un VARCHAR con CHECK constraint; no existe
 * un ALTER TYPE nativo, así que buscamos y eliminamos el constraint viejo y
 * recreamos uno nuevo con los tres valores posibles.
 * En MySQL/MariaDB se usa el ALTER ENUM estándar vía ->change().
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // Buscar y eliminar el CHECK constraint existente sobre 'status'
            // (puede ser anónimo o con nombre según la versión de Laravel/Doctrine)
            DB::statement(<<<SQL
                DO $$
                DECLARE
                    v_constraint text;
                BEGIN
                    SELECT con.conname INTO v_constraint
                    FROM   pg_constraint con
                    JOIN   pg_class      rel ON rel.oid = con.conrelid
                    JOIN   pg_attribute  att ON att.attrelid = rel.oid
                                           AND att.attnum = ANY(con.conkey)
                    WHERE  rel.relname  = 'academic_years'
                      AND  att.attname  = 'status'
                      AND  con.contype  = 'c'
                    LIMIT  1;

                    IF v_constraint IS NOT NULL THEN
                        EXECUTE 'ALTER TABLE academic_years DROP CONSTRAINT ' || quote_ident(v_constraint);
                    END IF;
                END;
                $$
            SQL);

            // Agregar nuevo CHECK con los tres estados posibles
            DB::statement(
                "ALTER TABLE academic_years ADD CONSTRAINT academic_years_status_check "
              . "CHECK (status IN ('planning', 'enrollment_open', 'finished'))"
            );

        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->enum('status', ['planning', 'enrollment_open', 'finished'])
                      ->default('planning')
                      ->change();
            });
        }
        // SQLite: el enum es TEXT sin constraint real — no requiere acción.
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(<<<SQL
                DO $$
                DECLARE
                    v_constraint text;
                BEGIN
                    SELECT con.conname INTO v_constraint
                    FROM   pg_constraint con
                    JOIN   pg_class      rel ON rel.oid = con.conrelid
                    JOIN   pg_attribute  att ON att.attrelid = rel.oid
                                           AND att.attnum = ANY(con.conkey)
                    WHERE  rel.relname  = 'academic_years'
                      AND  att.attname  = 'status'
                      AND  con.contype  = 'c'
                    LIMIT  1;

                    IF v_constraint IS NOT NULL THEN
                        EXECUTE 'ALTER TABLE academic_years DROP CONSTRAINT ' || quote_ident(v_constraint);
                    END IF;
                END;
                $$
            SQL);

            DB::statement(
                "ALTER TABLE academic_years ADD CONSTRAINT academic_years_status_check "
              . "CHECK (status IN ('planning', 'enrollment_open'))"
            );

        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->enum('status', ['planning', 'enrollment_open'])
                      ->default('planning')
                      ->change();
            });
        }
    }
};
