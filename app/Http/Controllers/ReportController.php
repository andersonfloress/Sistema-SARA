<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Section;
use App\Models\Enrollment;
use App\Models\AcademicYear;
use App\Exports\GradesExport;
use App\Exports\AttendanceExport;
use App\Services\StudentAcademicReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(private StudentAcademicReportService $academicReportService) {}

    public function index(Request $request)
    {
        $user      = auth()->user();
        $teacherId = $user->isTeacher() ? $user->id : null;

        // ── Año escolar seleccionado (por defecto, el año activo) ────────
        $availableYears = Section::select('year')->distinct()->orderByDesc('year')->pluck('year');
        $selectedYear   = $request->filled('year') ? (int) $request->year : AcademicYear::currentYear();

        // Filtro reutilizable: curso del docente (si aplica) + año escolar de la sección
        $courseScope = fn($q) => $q->whereHas('section', fn($s) => $s->where('year', $selectedYear))
                                    ->when($teacherId, fn($c) => $c->where('teacher_id', $teacherId));

        // ── Estadísticas con scope de docente + año escolar ──────────────
        $avgGrade = round((float) Grade::whereHas('course', $courseScope)->avg('score'), 1);

        $totalAtt   = Attendance::whereHas('course', $courseScope)->count();
        $presentAtt = Attendance::whereHas('course', $courseScope)->whereIn('status', ['present', 'justified'])->count();
        $attPct     = $totalAtt > 0 ? round(($presentAtt / $totalAtt) * 100, 1) : 0;

        $atRisk = Grade::whereHas('course', $courseScope)
            ->select('student_id')
            ->groupBy('student_id')
            ->havingRaw('AVG(score) < 11')
            ->count();

        $outstanding = Grade::whereHas('course', $courseScope)
            ->select('student_id')
            ->groupBy('student_id')
            ->havingRaw('AVG(score) >= 18')
            ->count();

        $totalStudents = Grade::whereHas('course', $courseScope)
            ->distinct('student_id')->count('student_id');

        $gradesByPeriod = Grade::whereHas('course', $courseScope)
            ->select('period', DB::raw('ROUND(AVG(score),1) as avg'))
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('avg', 'period');

        $attByStatus = Attendance::whereHas('course', $courseScope)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $topSections = Grade::whereHas('course', $courseScope)
            ->join('courses', 'grades.course_id', '=', 'courses.id')
            ->join('sections', 'courses.section_id', '=', 'sections.id')
            ->select('sections.name', DB::raw('ROUND(AVG(grades.score),1) as avg'))
            ->groupBy('sections.id', 'sections.name')
            ->orderByDesc('avg')
            ->take(5)
            ->get();

        // Cursos para filtros de exportación (del año escolar seleccionado)
        $cursos = Course::whereHas('section', fn($s) => $s->where('year', $selectedYear))
            ->when($teacherId, fn($q) => $q->where('teacher_id', $teacherId))
            ->with('section')
            ->orderBy('name')
            ->get();

        return view('reportes.index', compact(
            'avgGrade', 'attPct', 'atRisk', 'outstanding', 'totalStudents',
            'gradesByPeriod', 'attByStatus', 'topSections', 'cursos',
            'availableYears', 'selectedYear'
        ));
    }

    /** Devuelve el teacher_id si el usuario autenticado es docente, null si es admin. */
    private function teacherScope(): ?int
    {
        $user = auth()->user();
        return $user->isTeacher() ? $user->id : null;
    }

    /** Año escolar a usar en un export: el que venga en la URL, o el activo si no se especifica. */
    private function yearScope(Request $request): int
    {
        return $request->filled('year') ? (int) $request->year : AcademicYear::currentYear();
    }

    /**
     * Un año escolar completo sin filtrar por curso puede tener decenas de
     * miles de registros; dompdf no está hecho para tablas de ese tamaño
     * (se vuelve lentísimo o agota la memoria del proceso). Por encima de
     * este límite, pedimos filtrar por curso o usar Excel en su lugar.
     */
    private const PDF_ROW_LIMIT = 15000;

    public function exportGradesPdf(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(120);
        $teacherId = $this->teacherScope();
        $courseId  = $request->integer('course_id') ?: null;
        $period    = $request->input('period');
        $year      = $this->yearScope($request);

        $baseQuery = fn() => Grade::whereHas('course.section', fn($q) => $q->where('year', $year))
            ->when($teacherId, fn($q) => $q->whereHas('course', fn($c) => $c->where('teacher_id', $teacherId)))
            ->when($courseId,  fn($q) => $q->where('course_id', $courseId))
            ->when($period,    fn($q) => $q->where('period', $period));

        if ($baseQuery()->count() > self::PDF_ROW_LIMIT) {
            return back()->with('error', 'Ese año escolar tiene demasiados registros para generar un PDF. Filtra por un curso específico, o usa la exportación a Excel para ver todos los datos.');
        }

        $grades = $baseQuery()
            ->with('student', 'course.section')
            ->join('users', 'grades.student_id', '=', 'users.id')
            ->orderBy('users.name')
            ->select('grades.*')
            ->get();

        $teacher = auth()->user()->isTeacher() ? auth()->user() : null;
        $curso   = $courseId ? Course::with('section')->find($courseId) : null;

        $pdf = Pdf::loadView('reportes.pdf-calificaciones', compact('grades', 'teacher', 'curso', 'period', 'year'));
        return $pdf->download('calificaciones_' . $year . '_' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportGradesExcel(Request $request)
    {
        $courseId = $request->integer('course_id') ?: null;
        $period   = $request->input('period');
        $year     = $this->yearScope($request);
        return Excel::download(
            new GradesExport($this->teacherScope(), $courseId, $period, $year),
            'calificaciones_' . $year . '_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Convierte el código de periodo al rango de fechas correspondiente.
     * 'all' o null → todo el año lectivo (mar–nov).
     * Retorna [fecha_inicio, fecha_fin] como strings 'Y-m-d'.
     */
    private function attendanceDateRange(?string $period, int $year): array
    {
        return match ($period) {
            'I'     => ["{$year}-03-01", "{$year}-05-31"],
            'II'    => ["{$year}-06-01", "{$year}-08-31"],
            'III'   => ["{$year}-09-01", "{$year}-11-30"],
            default => ["{$year}-03-01", "{$year}-11-30"],  // todos los periodos
        };
    }

    /** Etiqueta legible del periodo para mostrar en el PDF. */
    private function attendancePeriodLabel(?string $period): string
    {
        return match ($period) {
            'I'     => 'Trimestre I · mar–may',
            'II'    => 'Trimestre II · jun–ago',
            'III'   => 'Trimestre III · sep–nov',
            default => 'Todos los periodos · mar–nov',
        };
    }

    public function exportAttendancePdf(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(120);
        $teacherId   = $this->teacherScope();
        $courseId    = $request->integer('course_id') ?: null;
        $year        = $this->yearScope($request);
        $period      = in_array($request->input('period'), ['I','II','III']) ? $request->input('period') : null;
        $dateRange   = $this->attendanceDateRange($period, $year);
        $periodLabel = $this->attendancePeriodLabel($period);

        $baseQuery = fn() => Attendance::whereHas('course.section', fn($q) => $q->where('year', $year))
            ->when($teacherId, fn($q) => $q->whereHas('course', fn($c) => $c->where('teacher_id', $teacherId)))
            ->when($courseId,  fn($q) => $q->where('course_id', $courseId))
            ->whereBetween('date', $dateRange);

        if ($baseQuery()->count() > self::PDF_ROW_LIMIT) {
            return back()->with('error', 'Demasiados registros para generar el PDF. Filtra por un curso específico o usa la exportación a Excel.');
        }

        $attendances  = $baseQuery()->with('student', 'course.section', 'course.teacher')->get();
        $courseMatrix = $this->buildAttendanceMatrix($attendances);
        $teacher      = auth()->user()->isTeacher() ? auth()->user() : null;
        $curso        = $courseId ? Course::with('section')->find($courseId) : null;

        $pdf = Pdf::loadView('reportes.pdf-asistencia',
                    compact('courseMatrix', 'teacher', 'curso', 'year', 'periodLabel'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download('asistencia_' . $year . '_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Construye la estructura de asistencia agrupada por curso.
     * Retorna array con: course, students, matrix, totals.
     */
    private function buildAttendanceMatrix($attendances): array
    {
        $result = [];

        foreach (collect($attendances)->groupBy('course_id') as $courseAtts) {
            $course = $courseAtts->first()->course;

            $students = $courseAtts
                ->map(fn($a) => $a->student)
                ->filter()
                ->unique('id')
                ->sortBy('name')
                ->values();

            // matrix[student_id]['Y-m-d'] = status
            $matrix = [];
            foreach ($courseAtts as $att) {
                $matrix[$att->student_id][$att->date->format('Y-m-d')] = $att->status;
            }

            // Totales por alumno
            $totals = [];
            foreach ($students as $s) {
                $statuses = collect($matrix[$s->id] ?? []);
                $p = $statuses->filter(fn($x) => $x === 'present')->count();
                $a = $statuses->filter(fn($x) => $x === 'absent')->count();
                $t = $statuses->filter(fn($x) => $x === 'late')->count();
                $j = $statuses->filter(fn($x) => $x === 'justified')->count();
                $total = $p + $a + $t + $j;
                $totals[$s->id] = [
                    'P' => $p, 'A' => $a, 'T' => $t, 'J' => $j,
                    'total' => $total,
                    'pct'   => $total > 0 ? round((($p + $j) / $total) * 100) : 100,
                ];
            }

            $result[] = compact('course', 'students', 'matrix', 'totals');
        }

        return $result;
    }

    public function exportAttendanceExcel(Request $request)
    {
        $courseId  = $request->integer('course_id') ?: null;
        $year      = $this->yearScope($request);
        $period    = in_array($request->input('period'), ['I','II','III']) ? $request->input('period') : null;
        $dateRange = $this->attendanceDateRange($period, $year);

        return Excel::download(
            new AttendanceExport($this->teacherScope(), $courseId, $year, $dateRange),
            'asistencia_' . $year . '_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Selector de alumno para generar su Boletín de Notas individual.
     * Admin ve todos los alumnos; docente solo los de sus secciones.
     */
    public function boletinSelector(Request $request)
    {
        $user = auth()->user();

        $availableYears = Section::select('year')->distinct()->orderByDesc('year')->pluck('year');
        $selectedYear   = $request->filled('year') ? (int) $request->year : AcademicYear::currentYear();

        $sectionIds = $user->isTeacher()
            ? Section::whereHas('courses', fn ($q) => $q->where('teacher_id', $user->id))->pluck('id')
            : Section::query()->pluck('id');

        $students = User::where('role', 'student')
            ->whereHas('enrollments', function ($q) use ($sectionIds, $selectedYear) {
                $q->whereIn('section_id', $sectionIds)->where('year', $selectedYear);
            })
            ->with(['enrollments' => fn ($q) => $q->where('year', $selectedYear)->with('section')])
            ->orderBy('name')
            ->get();

        return view('reportes.boletin', compact('students', 'availableYears', 'selectedYear'));
    }

    /**
     * Genera el Boletín de Notas individual del alumno en PDF, con el
     * formato oficial (encabezado tipo MINEDU + datos del colegio).
     * Acepta ?year= para generar el boletín de un año escolar específico.
     */
    public function boletinPdf(Request $request, User $alumno)
    {
        abort_unless($alumno->role === 'student', 404);

        $user = auth()->user();
        if ($user->isTeacher()) {
            $teacherSectionIds = Section::whereHas('courses', fn ($q) => $q->where('teacher_id', $user->id))->pluck('id');
            $inSection = Enrollment::whereIn('section_id', $teacherSectionIds)
                                   ->where('student_id', $alumno->id)
                                   ->exists();
            abort_unless($inSection, 403);
        }

        $year = $request->filled('year') ? (int) $request->year : null;

        $alumno->load('studentProfile', 'enrollments.section');

        $data = $this->academicReportService->build($alumno, $year);

        ini_set('memory_limit', '512M');

        $pdf = Pdf::loadView('reportes.pdf-boletin', array_merge($data, ['alumno' => $alumno]));

        $yearLabel = $data['currentSection']?->year ?? now()->year;
        $fileName  = 'boletin_' . \Illuminate\Support\Str::slug($alumno->name) . '_' . $yearLabel . '.pdf';

        return $pdf->download($fileName);
    }
}
