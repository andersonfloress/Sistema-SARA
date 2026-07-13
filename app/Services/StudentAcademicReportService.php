<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Enrollment;

/**
 * Construye el reporte académico completo de un alumno (notas del año
 * actual + historial de años anteriores + resumen de asistencia).
 *
 * Es la fuente única de datos para: el portal del padre (pantalla y PDF)
 * y el boletín individual generado desde Reportes / ficha del alumno,
 * así todos muestran exactamente lo mismo.
 */
class StudentAcademicReportService
{
    /**
     * @param  User      $alumno  El alumno para quien se genera el reporte.
     * @param  int|null  $year    Año escolar a usar. Si es null, usa el más reciente.
     */
    public function build(User $alumno, ?int $year = null): array
    {
        $periods = ['I', 'II', 'III'];

        // All enrollments ordered newest first
        $allEnrollments = Enrollment::where('student_id', $alumno->id)
                                    ->with('section')
                                    ->get()
                                    ->sortByDesc(fn($e) => $e->section->year);

        // Si se pide un año específico úsalo; si no, el más reciente disponible.
        $currentYear       = $year ?? $allEnrollments->first()?->section?->year;
        $currentEnrollment = $allEnrollments->firstWhere(fn($e) => $e->section->year === $currentYear);

        $currentSection = $currentEnrollment?->section;

        // Current-year courses & grades
        $currentCourses = $currentSection
            ? Course::with('teacher')->where('section_id', $currentSection->id)->orderBy('name')->get()
            : collect();

        $currentCourseIds = $currentCourses->pluck('id');
        $currentGrades    = Grade::where('student_id', $alumno->id)
                                 ->whereIn('course_id', $currentCourseIds)
                                 ->get();

        // Build grade matrix [course_id][period]
        $gradeMatrix = [];
        foreach ($currentGrades as $g) {
            $gradeMatrix[$g->course_id][$g->period] = [
                'score'       => (float) $g->score,
                'observation' => $g->observation,
            ];
        }

        $allScores  = $currentGrades->pluck('score')->map(fn($v) => (float) $v);
        $overallAvg = $allScores->count() > 0 ? round($allScores->avg(), 1) : null;

        // Historical years
        $history = [];
        foreach ($allEnrollments->filter(fn($e) => $e->section->year !== $currentYear)->sortByDesc(fn($e) => $e->section->year) as $enr) {
            $histSection   = $enr->section;
            $histCourses   = Course::with('teacher')->where('section_id', $histSection->id)->orderBy('name')->get();
            $histCourseIds = $histCourses->pluck('id');
            $histGrades    = Grade::where('student_id', $alumno->id)->whereIn('course_id', $histCourseIds)->get();

            $histMatrix = [];
            foreach ($histGrades as $g) {
                $histMatrix[$g->course_id][$g->period] = [
                    'score'       => (float) $g->score,
                    'observation' => $g->observation,
                ];
            }

            $histScores = $histGrades->pluck('score')->map(fn($v) => (float) $v);
            $history[]  = [
                'section'     => $histSection,
                'courses'     => $histCourses,
                'gradeMatrix' => $histMatrix,
                'overallAvg'  => $histScores->count() > 0 ? round($histScores->avg(), 1) : null,
            ];
        }

        // ── Attendance — filtrada al año del enrollment actual ────────────────
        $atts = Attendance::with('course')
            ->where('student_id', $alumno->id)
            ->when($currentYear, fn($q) => $q->whereHas('course.section', fn($s) => $s->where('year', $currentYear)))
            ->latest('date')
            ->get();
        $total     = $atts->count();
        $present   = $atts->where('status', 'present')->count();
        $absent    = $atts->where('status', 'absent')->count();
        $late      = $atts->where('status', 'late')->count();
        $justified = $atts->where('status', 'justified')->count();
        $attPct    = $total > 0 ? round((($present + $justified) / $total) * 100) : 100;
        $absentPct = $total > 0 ? round(($absent / $total) * 100) : 0;

        $atRisk = ($overallAvg !== null && $overallAvg < 11) || $absentPct > 30;

        return compact(
            'periods', 'currentEnrollment', 'currentSection',
            'currentCourses', 'gradeMatrix', 'overallAvg', 'history',
            'atts', 'total', 'present', 'absent', 'late', 'justified',
            'attPct', 'absentPct', 'atRisk'
        );
    }
}
