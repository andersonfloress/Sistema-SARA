<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Section;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Announcement;
use App\Models\AcademicEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Año académico activo: el más reciente registrado en secciones.
     * Así el dashboard siempre muestra datos del ciclo en curso,
     * sin importar cuántos años históricos existan en la BD.
     */
    private function currentYear(): int
    {
        return (int) (Section::max('year') ?? date('Y'));
    }

    /**
     * IDs de las secciones del año en curso (las 10 actuales).
     */
    private function currentSectionIds(): \Illuminate\Support\Collection
    {
        return Section::where('year', $this->currentYear())->pluck('id');
    }

    /**
     * IDs de todos los cursos del año en curso.
     */
    private function currentCourseIds(): \Illuminate\Support\Collection
    {
        return Course::whereIn('section_id', $this->currentSectionIds())->pluck('id');
    }

    /**
     * Devuelve el rango de fechas [inicio, fin] para un trimestre dado.
     * Trimestre I → mar–may, II → jun–ago, III → sep–nov.
     * Retorna null cuando $period === 'all'.
     */
    private function periodDateRange(string $period, int $year): ?array
    {
        return match ($period) {
            'I'   => ["{$year}-03-01", "{$year}-05-31"],
            'II'  => ["{$year}-06-01", "{$year}-08-31"],
            'III' => ["{$year}-09-01", "{$year}-11-30"],
            default => null,
        };
    }

    /**
     * Etiqueta legible del periodo seleccionado.
     */
    private function periodLabel(string $period, int $year): string
    {
        return match ($period) {
            'I'   => "Trimestre I · mar–may {$year}",
            'II'  => "Trimestre II · jun–ago {$year}",
            'III' => "Trimestre III · sep–nov {$year}",
            default => "Todos los periodos · {$year}",
        };
    }

    public function index()
    {
        $user   = auth()->user();
        $period = in_array(request('period'), ['I', 'II', 'III']) ? request('period') : 'all';

        if ($user->isTeacher()) {
            $data = $this->teacherStats($user, $period);
        } elseif ($user->isStudent()) {
            $data = $this->studentStats($user);
        } else {
            // admin & parent
            $data = $this->schoolStats($period);
        }

        // Recent announcements visible to this user
        $announcements = Announcement::with('author')
            ->where(function ($q) use ($user) {
                $q->where('target_role', 'all')
                  ->orWhere('target_role', $user->role);
            })
            ->latest()
            ->take(5)
            ->get();

        // Upcoming academic events visible to this user
        $upcomingEvents = AcademicEvent::where('event_date', '>=', now()->toDateString())
            ->where(function ($q) use ($user) {
                $q->where('target_role', 'all')
                  ->orWhere('target_role', $user->role);
            })
            ->orderBy('event_date')
            ->take(3)
            ->get();

        $periodLabel = $this->periodLabel($period, $this->currentYear());

        return view('dashboard.index', array_merge(
            $data,
            compact('announcements', 'upcomingEvents', 'period', 'periodLabel')
        ));
    }

    /**
     * Stats del año en curso para admin y padre.
     * Filtra secciones, cursos y notas solo al año académico activo.
     * $period: 'I' | 'II' | 'III' | 'all'
     */
    private function schoolStats(string $period = 'all'): array
    {
        $currentYear       = $this->currentYear();
        $currentSectionIds = $this->currentSectionIds();
        $currentCourseIds  = $this->currentCourseIds();
        $dateRange         = $this->periodDateRange($period, $currentYear);

        $totalStudents = User::where('role', 'student')->count();
        $totalTeachers = User::where('role', 'teacher')->count();
        $totalSections = $currentSectionIds->count();

        // Promedio: filtrado por period cuando corresponda
        $gradeQuery = Grade::whereIn('course_id', $currentCourseIds);
        if ($period !== 'all') {
            $gradeQuery->where('period', $period);
        }
        $avgGrade = $gradeQuery->avg('score');
        $avgGrade = $avgGrade ? round($avgGrade, 1) : 0;

        // Asistencia: filtrada por rango de fechas del trimestre
        $attBase = Attendance::whereIn('course_id', $currentCourseIds);
        if ($dateRange) {
            $attBase->whereBetween('date', $dateRange);
        }
        $totalAtt   = (clone $attBase)->count();
        $presentAtt = (clone $attBase)->whereIn('status', ['present', 'justified'])->count();
        $attPct = $totalAtt > 0 ? round(($presentAtt / $totalAtt) * 100, 1) : 0;

        // Alumnos en riesgo: promedio < 11 en el period seleccionado
        $riskQuery = Grade::whereIn('course_id', $currentCourseIds);
        if ($period !== 'all') {
            $riskQuery->where('period', $period);
        }
        $atRisk = $riskQuery->select('student_id')
            ->groupBy('student_id')
            ->havingRaw('AVG(score) < 11')
            ->count();

        return [
            'isTeacherView' => false,
            'isStudentView' => false,
            'totalStudents' => $totalStudents,
            'totalTeachers' => $totalTeachers,
            'totalSections' => $totalSections,
            'avgGrade'      => $avgGrade,
            'attPct'        => $attPct,
            'atRisk'        => $atRisk,
        ];
    }

    /**
     * Stats personales del alumno autenticado.
     * Usa solo la matrícula y los cursos del año en curso.
     */
    private function studentStats(User $student): array
    {
        $currentYear = $this->currentYear();

        // Matrícula del año en curso (la más reciente)
        $enrollment = Enrollment::where('student_id', $student->id)
            ->whereHas('section', fn($q) => $q->where('year', $currentYear))
            ->with('section')
            ->first();

        $sectionId = $enrollment?->section_id;

        // Cursos del año en curso de su sección
        $myCourses    = $sectionId ? Course::where('section_id', $sectionId)->count() : 0;
        $myCourseIds  = $sectionId ? Course::where('section_id', $sectionId)->pluck('id') : collect();

        // Promedio solo con notas del año en curso
        $scores     = Grade::where('student_id', $student->id)
                           ->whereIn('course_id', $myCourseIds)
                           ->pluck('score')
                           ->map(fn($v) => (float) $v);
        $myAvgGrade = $scores->count() > 0 ? round($scores->avg(), 1) : 0;

        // Asistencia del alumno (la asistencia siempre es del año actual, no hay histórica)
        $myAtts      = Attendance::where('student_id', $student->id);
        $myTotal     = (clone $myAtts)->count();
        $myPresent   = (clone $myAtts)->whereIn('status', ['present', 'justified'])->count();
        $myAbsent    = (clone $myAtts)->where('status', 'absent')->count();
        $myAttPct    = $myTotal > 0 ? round(($myPresent / $myTotal) * 100, 1) : 100;
        $myAbsentPct = $myTotal > 0 ? round(($myAbsent  / $myTotal) * 100, 1) : 0;

        $atRisk = (($myAvgGrade > 0 && $myAvgGrade < 11) || $myAbsentPct > 30) ? 1 : 0;

        $sectionLabel = $enrollment?->section
            ? 'Grado ' . $enrollment->section->grade . ' — ' . $enrollment->section->name
            : null;

        return [
            'isTeacherView' => false,
            'isStudentView' => true,
            'myCourses'     => $myCourses,
            'myAvgGrade'    => $myAvgGrade,
            'myAttPct'      => $myAttPct,
            'sectionLabel'  => $sectionLabel,
            // Keys compartidas con la vista general (no se muestran en rama student)
            'totalStudents' => $myCourses,
            'totalTeachers' => 0,
            'totalSections' => 0,
            'avgGrade'      => $myAvgGrade,
            'attPct'        => $myAttPct,
            'atRisk'        => $atRisk,
        ];
    }

    /**
     * Stats del docente autenticado, acotados al año en curso.
     * $period: 'I' | 'II' | 'III' | 'all'
     */
    private function teacherStats(User $teacher, string $period = 'all'): array
    {
        $currentYear = $this->currentYear();
        $dateRange   = $this->periodDateRange($period, $currentYear);

        // Solo cursos del año en curso asignados a este docente
        $courses = Course::with('section')
            ->where('teacher_id', $teacher->id)
            ->whereHas('section', fn($q) => $q->where('year', $currentYear))
            ->get();

        $courseIds = $courses->pluck('id');

        $totalCourses  = $courses->count();
        $totalSections = $courses->pluck('section_id')->unique()->count();

        $totalStudents = Section::whereIn('id', $courses->pluck('section_id')->unique())
            ->withCount('students')
            ->get()
            ->sum('students_count');

        // Promedio filtrado por period
        $gradeQ   = Grade::whereIn('course_id', $courseIds);
        if ($period !== 'all') $gradeQ->where('period', $period);
        $avgGrade = $gradeQ->avg('score');
        $avgGrade = $avgGrade ? round($avgGrade, 1) : 0;

        // Asistencia filtrada por rango de fechas del trimestre
        $attBase = Attendance::whereIn('course_id', $courseIds);
        if ($dateRange) $attBase->whereBetween('date', $dateRange);
        $totalAtt   = (clone $attBase)->count();
        $presentAtt = (clone $attBase)->whereIn('status', ['present', 'justified'])->count();
        $attPct = $totalAtt > 0 ? round(($presentAtt / $totalAtt) * 100, 1) : 0;

        // En riesgo filtrado por period
        $riskQ = Grade::whereIn('course_id', $courseIds);
        if ($period !== 'all') $riskQ->where('period', $period);
        $atRisk = $riskQ->select('student_id')
            ->groupBy('student_id')
            ->havingRaw('AVG(score) < 11')
            ->count();

        // Top alumnos por promedio (también filtrado por period)
        $topStudentsQ = Grade::whereIn('course_id', $courseIds)
            ->with('student:id,name');
        if ($period !== 'all') $topStudentsQ->where('period', $period);
        $topStudents = $topStudentsQ
            ->select('student_id', DB::raw('AVG(score) as avg_score'))
            ->groupBy('student_id')
            ->orderByDesc('avg_score')
            ->limit(5)
            ->get()
            ->map(fn($g) => [
                'name'  => $g->student?->name ?? '—',
                'score' => round($g->avg_score, 1),
            ]);

        // Desglose por curso (también filtrado)
        $avgByCourseQ = Grade::whereIn('course_id', $courseIds)
            ->select('course_id', DB::raw('AVG(score) as avg_score'));
        if ($period !== 'all') $avgByCourseQ->where('period', $period);
        $avgByCourse = $avgByCourseQ->groupBy('course_id')->pluck('avg_score', 'course_id');

        $attByCourseQ = Attendance::whereIn('course_id', $courseIds);
        if ($dateRange) $attByCourseQ->whereBetween('date', $dateRange);
        $attByCourse = $attByCourseQ
            ->select(
                'course_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status IN ('present','justified') THEN 1 ELSE 0 END) as present_total")
            )
            ->groupBy('course_id')
            ->get()
            ->keyBy('course_id');

        $courseBreakdown = $courses->map(function (Course $course) use ($avgByCourse, $attByCourse) {
            $courseAvg = $avgByCourse->get($course->id);
            $att       = $attByCourse->get($course->id);
            $attPct    = ($att && $att->total > 0) ? round(($att->present_total / $att->total) * 100, 1) : null;

            return [
                'name'     => $course->name,
                'section'  => $course->section?->name,
                'avgGrade' => $courseAvg ? round($courseAvg, 1) : null,
                'attPct'   => $attPct,
            ];
        });

        return [
            'isTeacherView'   => true,
            'isStudentView'   => false,
            'totalStudents'   => $totalStudents,
            'totalTeachers'   => $totalCourses,
            'totalSections'   => $totalSections,
            'avgGrade'        => $avgGrade,
            'attPct'          => $attPct,
            'atRisk'          => $atRisk,
            'topStudents'     => $topStudents,
            'courseBreakdown' => $courseBreakdown,
        ];
    }
}
