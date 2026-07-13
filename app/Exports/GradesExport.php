<?php

namespace App\Exports;

use App\Models\Grade;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GradesExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private ?int    $teacherId = null,
        private ?int    $courseId  = null,
        private ?string $period    = null,
        private ?int    $year      = null,
    ) {}

    public function collection()
    {
        return Grade::with('student', 'course.section')
            ->when($this->year,       fn($q) => $q->whereHas('course.section', fn($s) => $s->where('year', $this->year)))
            ->when($this->teacherId, fn($q) => $q->whereHas('course', fn($c) => $c->where('teacher_id', $this->teacherId)))
            ->when($this->courseId,  fn($q) => $q->where('course_id', $this->courseId))
            ->when($this->period,    fn($q) => $q->where('period', $this->period))
            ->join('users', 'grades.student_id', '=', 'users.id')
            ->orderBy('users.name')
            ->select('grades.*')
            ->get();
    }

    public function headings(): array
    {
        return ['Alumno', 'Curso', 'Sección', 'Periodo', 'Nota'];
    }

    public function map($grade): array
    {
        return [
            $grade->student?->name,
            $grade->course?->name,
            $grade->course?->section?->name,
            $grade->period,
            $grade->score,
        ];
    }
}
