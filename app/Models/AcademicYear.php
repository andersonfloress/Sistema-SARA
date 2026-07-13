<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    // Estados del año escolar
    const STATUS_PLANNING        = 'planning';
    const STATUS_ENROLLMENT_OPEN = 'enrollment_open';
    const STATUS_FINISHED        = 'finished';

    protected $fillable = ['year', 'status', 'default_capacity', 'enrollment_opened_at'];

    protected $casts = [
        'enrollment_opened_at' => 'datetime',
    ];

    // ── Estado helpers ────────────────────────────────────────────────────────

    public function isEnrollmentOpen(): bool
    {
        return $this->status === self::STATUS_ENROLLMENT_OPEN;
    }

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    public function isPlanning(): bool
    {
        return $this->status === self::STATUS_PLANNING;
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            self::STATUS_ENROLLMENT_OPEN => 'Matrícula habilitada',
            self::STATUS_FINISHED        => 'Año finalizado',
            default                      => 'Planificación',
        };
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_ENROLLMENT_OPEN => 'bg-green-100 text-green-700',
            self::STATUS_FINISHED        => 'bg-gray-200 text-gray-600',
            default                      => 'bg-amber-100 text-amber-700',
        };
    }

    public static function isYearEnrollmentOpen(int $year): bool
    {
        $academicYear = static::where('year', $year)->first();
        return $academicYear ? $academicYear->isEnrollmentOpen() : false;
    }

    /**
     * Año académico activo: el más reciente que no esté finalizado.
     * Si todos están finalizados, devuelve el más reciente de todos.
     */
    public static function current(): ?self
    {
        return static::where('status', '!=', self::STATUS_FINISHED)
                     ->orderByDesc('year')
                     ->first()
               ?? static::orderByDesc('year')->first();
    }

    /**
     * Número del año activo (ej. 2026). Útil para filtrar secciones.
     */
    public static function currentYear(): int
    {
        return static::current()?->year ?? (int) now()->year;
    }
}
