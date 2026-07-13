<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleSlot extends Model
{
    protected $fillable = ['course_id', 'day_of_week', 'start_time', 'end_time', 'classroom'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function dayLabel(): string
    {
        return match($this->day_of_week) {
            'lunes'     => 'Lunes',
            'martes'    => 'Martes',
            'miercoles' => 'Miércoles',
            'jueves'    => 'Jueves',
            'viernes'   => 'Viernes',
            default     => ucfirst($this->day_of_week),
        };
    }
}
