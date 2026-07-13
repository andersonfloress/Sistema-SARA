<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    // Resultados posibles al cierre del año escolar
    const RESULT_APPROVED  = 'approved';   // Aprobó → sube de grado
    const RESULT_RETAINED  = 'retained';   // Repitente → queda en el mismo grado
    const RESULT_GRADUATED = 'graduated';  // Egresado → culminó 5° grado

    protected $fillable = ['student_id', 'section_id', 'year', 'enrolled_at', 'result'];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    // ── Helpers de resultado ──────────────────────────────────────────────────

    public function isApproved(): bool  { return $this->result === self::RESULT_APPROVED; }
    public function isRetained(): bool  { return $this->result === self::RESULT_RETAINED; }
    public function isGraduated(): bool { return $this->result === self::RESULT_GRADUATED; }
    public function hasPendingResult(): bool { return $this->result === null; }

    public function resultLabel(): string
    {
        return match($this->result) {
            self::RESULT_APPROVED  => 'Aprobado',
            self::RESULT_RETAINED  => 'Repitente',
            self::RESULT_GRADUATED => 'Egresado',
            default                => 'Sin resultado',
        };
    }

    public function resultBadgeClass(): string
    {
        return match($this->result) {
            self::RESULT_APPROVED  => 'bg-green-100 text-green-700',
            self::RESULT_RETAINED  => 'bg-red-100 text-red-700',
            self::RESULT_GRADUATED => 'bg-purple-100 text-purple-700',
            default                => 'bg-gray-100 text-gray-500',
        };
    }
}
