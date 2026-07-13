<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSubmission extends Model
{
    protected $fillable = [
        'task_id', 'student_id', 'file_path', 'original_name',
        'attempt', 'grade', 'teacher_note', 'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function fileExtension(): string
    {
        return strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
    }

    public function fileIcon(): string
    {
        return match($this->fileExtension()) {
            'pdf'           => 'file-text',
            'doc', 'docx'   => 'file-type',
            'xls', 'xlsx'   => 'table',
            'ppt', 'pptx'   => 'presentation',
            default         => 'file',
        };
    }
}
