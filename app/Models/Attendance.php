<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['student_id', 'course_id', 'date', 'status', 'created_by'];

    protected $casts = ['date' => 'date'];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'present'   => 'Presente',
            'absent'    => 'Ausente',
            'late'      => 'Tardanza',
            'justified' => 'Justificado',
            default     => ucfirst($this->status),
        };
    }

    public function statusClass(): string
    {
        return match($this->status) {
            'present'   => 'bg-green-100 text-green-800',
            'absent'    => 'bg-red-100 text-red-800',
            'late'      => 'bg-yellow-100 text-yellow-800',
            'justified' => 'bg-blue-100 text-blue-800',
            default     => 'bg-gray-100 text-gray-800',
        };
    }
}
