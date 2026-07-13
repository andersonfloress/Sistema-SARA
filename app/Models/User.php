<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ── Role helpers ──────────────────────────────────────────────────────────

    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isTeacher(): bool  { return $this->role === 'teacher'; }
    public function isStudent(): bool  { return $this->role === 'student'; }
    public function isParent(): bool   { return $this->role === 'parent'; }

    public function roleLabel(): string
    {
        return match($this->role) {
            'admin'   => 'Administrador',
            'teacher' => 'Docente',
            'student' => 'Alumno',
            'parent'  => 'Padre/Madre',
            default   => ucfirst($this->role),
        };
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class, 'student_id');
    }

    public function teacherProfile()
    {
        return $this->hasOne(TeacherProfile::class, 'teacher_id');
    }

    public function parentProfile()
    {
        return $this->hasOne(ParentProfile::class, 'parent_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'enrollments', 'student_id', 'section_id')
                    ->withTimestamps();
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'student_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class, 'author_id');
    }

    /** Courses taught by this teacher */
    public function courses()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    /** Students linked as children (for parents) */
    public function children()
    {
        return $this->belongsToMany(User::class, 'parent_students', 'parent_id', 'student_id')
                    ->withPivot('parentesco')
                    ->withTimestamps();
    }

    /** Parents linked to this student */
    public function parents()
    {
        return $this->belongsToMany(User::class, 'parent_students', 'student_id', 'parent_id')
                    ->withPivot('parentesco')
                    ->withTimestamps();
    }
}
