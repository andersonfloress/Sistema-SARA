<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['name', 'grade', 'year', 'turno', 'cupo_maximo'];

    /**
     * Filtra secciones al año académico activo.
     * Uso: Section::active()->orderBy('grade')->get()
     */
    public function scopeActive($query)
    {
        return $query->where('year', AcademicYear::currentYear());
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments', 'section_id', 'student_id')
                    ->withTimestamps();
    }
}
