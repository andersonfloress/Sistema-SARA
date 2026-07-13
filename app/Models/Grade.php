<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = ['student_id', 'course_id', 'period', 'score', 'observation', 'created_by'];

    protected $casts = [
        'score' => 'decimal:1',
    ];

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

    public function scoreClass(): string
    {
        return match(true) {
            $this->score >= 18 => 'text-green-600 font-bold',
            $this->score >= 11 => 'text-blue-600',
            default            => 'text-red-600 font-bold',
        };
    }
}
