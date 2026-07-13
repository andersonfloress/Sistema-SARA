<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Task extends Model
{
    protected $fillable = [
        'title', 'description', 'deadline', 'max_attempts',
        'file_path', 'course_id', 'teacher_id',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class);
    }

    public function submissionsForStudent(int $studentId): HasMany
    {
        return $this->submissions()->where('student_id', $studentId)->orderBy('attempt');
    }

    public function latestSubmissionForStudent(int $studentId): ?TaskSubmission
    {
        return $this->submissions()
            ->where('student_id', $studentId)
            ->orderByDesc('attempt')
            ->first();
    }

    public function isExpired(): bool
    {
        return Carbon::now()->isAfter($this->deadline);
    }

    public function statusLabel(): string
    {
        return $this->isExpired() ? 'Vencida' : 'Activa';
    }

    public function statusColor(): string
    {
        return $this->isExpired() ? 'red' : 'green';
    }
}
