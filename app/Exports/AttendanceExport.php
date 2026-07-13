<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exporta el registro de asistencia en formato MATRIZ:
 * filas = alumnos, columnas = fechas de clase.
 * Genera una hoja de Excel por cada curso encontrado.
 */
class AttendanceExport implements WithMultipleSheets
{
    public function __construct(
        private ?int    $teacherId = null,
        private ?int    $courseId  = null,
        private ?int    $year      = null,
        private ?array  $dateRange = null,   // [start, end] o null = sin filtro de fechas
    ) {}

    public function sheets(): array
    {
        $attendances = Attendance::with('student', 'course.section', 'course.teacher')
            ->when($this->year,      fn($q) => $q->whereHas('course.section', fn($s) => $s->where('year', $this->year)))
            ->when($this->teacherId, fn($q) => $q->whereHas('course', fn($c) => $c->where('teacher_id', $this->teacherId)))
            ->when($this->courseId,  fn($q) => $q->where('course_id', $this->courseId))
            ->when($this->dateRange, fn($q) => $q->whereBetween('date', $this->dateRange))
            ->get();

        $sheets = [];
        foreach ($attendances->groupBy('course_id') as $courseAtts) {
            $sheets[] = new CourseAttendanceSheet($courseAtts, $this->year);
        }

        return $sheets ?: [new CourseAttendanceSheet(collect(), $this->year)];
    }
}
