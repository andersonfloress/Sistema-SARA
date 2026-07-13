<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Announcement;
use App\Models\AcademicEvent;
use App\Models\Material;
use App\Models\ScheduleSlot;
use App\Models\Course;
use App\Services\StudentAcademicReportService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ParentPortalController extends Controller
{
    public function __construct(private StudentAcademicReportService $academicReportService) {}

    public function index()
    {
        $user     = auth()->user();
        $children = $user->children()->with('enrollments.section')->get();

        $enriched = $children->map(function (User $child) {
            $grades  = Grade::where('student_id', $child->id)->get();
            $scores  = $grades->map(fn($g) => (float) $g->score)->filter(fn($v) => !is_nan($v));
            $avgGrade = $scores->count() > 0 ? round($scores->avg(), 1) : null;

            $atts      = Attendance::where('student_id', $child->id)->get();
            $total     = $atts->count();
            $effective = $atts->whereIn('status', ['present', 'justified'])->count();
            $absent    = $atts->where('status', 'absent')->count();
            $attPct    = $total > 0 ? round(($effective / $total) * 100) : 100;
            $absentPct = $total > 0 ? round(($absent / $total) * 100) : 0;

            return [
                'student'     => $child,
                'section'     => $child->enrollments->first()?->section,
                'avgGrade'    => $avgGrade,
                'attPct'      => $attPct,
                'atRisk'      => ($avgGrade !== null && $avgGrade < 11) || $absentPct > 30,
            ];
        });

        return view('padres.index', compact('enriched'));
    }

    public function show(User $alumno, Request $request)
    {
        // Only student-role users can be viewed in the parent portal
        abort_unless($alumno->role === 'student', 404);

        $user = auth()->user();

        // Verify access
        if ($user->isParent()) {
            $isChild = $user->children()->where('users.id', $alumno->id)->exists();
            if (!$isChild) abort(403);
        }

        // ── Siblings (all children of this parent) ────────────────────────────
        $siblings = $user->isParent()
            ? $user->children()->with('enrollments.section')->get()
            : collect();

        $alumno->load('enrollments.section');

        [
            'periods' => $periods, 'currentEnrollment' => $currentEnrollment, 'currentSection' => $currentSection,
            'currentCourses' => $currentCourses, 'gradeMatrix' => $gradeMatrix, 'overallAvg' => $overallAvg, 'history' => $history,
            'atts' => $atts, 'total' => $total, 'present' => $present, 'absent' => $absent, 'late' => $late, 'justified' => $justified,
            'attPct' => $attPct, 'absentPct' => $absentPct, 'atRisk' => $atRisk,
        ] = $this->academicReportService->build($alumno);

        // ── Section & Courses (for schedule / materials / teachers tabs) ──────
        $enrollment = $currentEnrollment;
        $sectionId  = $currentSection?->id;

        $sectionCourses = $sectionId
            ? Course::with(['teacher', 'teacher.teacherProfile', 'scheduleSlots'])
                    ->where('section_id', $sectionId)
                    ->get()
            : collect();

        // ── Schedule ──────────────────────────────────────────────────────────
        // Las horas de la cuadrícula se calculan a partir de los bloques reales
        // guardados en schedule_slots (no caen en horas en punto, ej. 07:30,
        // 08:10...). Usar una lista fija de horas en punto (como antes) dejaba
        // todas las celdas vacías porque nunca coincidía con $slot->start_time.
        // Ver ScheduleController::buildTimeSlots() para la misma lógica.
        $days    = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
        $recesos = [
            '10:10' => ['fin' => '10:30', 'turno' => 'mañana'],
            '15:40' => ['fin' => '16:00', 'turno' => 'tarde'],
        ];

        $times = ScheduleSlot::query()->distinct()->orderBy('start_time')->pluck('start_time')->all();
        if (!$times) {
            $times = ['07:00','08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00'];
        }
        $times = array_unique(array_merge($times, array_keys($recesos)));
        sort($times);

        $scheduleGrid = [];
        foreach ($days as $day) {
            foreach ($times as $time) {
                $scheduleGrid[$day][$time] = null;
            }
        }

        $courseIds = $sectionCourses->pluck('id');
        $slots = ScheduleSlot::with('course')->whereIn('course_id', $courseIds)->get();
        foreach ($slots as $slot) {
            $scheduleGrid[$slot->day_of_week][$slot->start_time] = $slot;
        }

        // ── Attendance — vista semanal ──────────────────────────────────────────
        // El padre pidió verla organizada igual que el horario (por día de la
        // semana), pero OJO: no todos los cursos tienen clase todos los días.
        // Por eso la cuadrícula de asistencia NO se arma con "todos los cursos x
        // todos los días" (eso mostraba filas fantasma para días sin esa clase).
        // Se arma calcando exactamente $scheduleGrid (mismas celdas [día][hora]
        // que la pestaña Horario): solo hay celda de asistencia donde realmente
        // hay un bloque de clase programado ese día/hora. El estado se resuelve
        // buscando el registro de asistencia del curso de ese bloque en la
        // fecha real de ese día dentro de la semana seleccionada.
        $weekParam = $request->query('semana');
        $maxAttDate = Attendance::where('student_id', $alumno->id)->max('date');
        try {
            $weekStart = $weekParam
                ? \Carbon\Carbon::parse($weekParam)->startOfWeek(\Carbon\Carbon::MONDAY)
                : ($maxAttDate
                    ? \Carbon\Carbon::parse($maxAttDate)->startOfWeek(\Carbon\Carbon::MONDAY)
                    : now()->startOfWeek(\Carbon\Carbon::MONDAY));
        } catch (\Exception $e) {
            $weekStart = now()->startOfWeek(\Carbon\Carbon::MONDAY);
        }
        $weekEnd = $weekStart->copy()->addDays(4); // viernes

        // Fecha real de cada día de esta semana (lunes..viernes)
        $weekDates = [];
        foreach ($days as $i => $day) {
            $weekDates[$day] = $weekStart->copy()->addDays($i)->toDateString();
        }

        $weekAttendances = Attendance::with('course')
            ->where('student_id', $alumno->id)
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get();

        // Índice rápido [curso_id][fecha] = registro, para resolver cada celda
        $attByCourseDate = [];
        foreach ($weekAttendances as $att) {
            $attByCourseDate[$att->course_id][\Illuminate\Support\Carbon::parse($att->date)->toDateString()] = $att;
        }

        // Cuadrícula [día][hora] = registro de asistencia, SOLO si $scheduleGrid
        // tiene un bloque de clase real en esa celda (igual estructura que Horario).
        $attendanceWeekGrid = [];
        foreach ($days as $day) {
            foreach ($times as $time) {
                $slot = $scheduleGrid[$day][$time] ?? null;
                $attendanceWeekGrid[$day][$time] = $slot
                    ? ($attByCourseDate[$slot->course_id][$weekDates[$day]] ?? null)
                    : null;
            }
        }

        // Resumen de lo que necesita atención esta semana (todo menos "presente")
        $dayLabels = ['lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes'];
        $weekAlerts = $weekAttendances
            ->whereIn('status', ['absent', 'late', 'justified'])
            ->sortBy('date')
            ->values()
            ->map(function ($att) use ($days, $dayLabels) {
                $dow = \Illuminate\Support\Carbon::parse($att->date)->dayOfWeekIso; // 1=lunes ... 5=viernes
                $dayKey = $days[$dow - 1] ?? null;
                $att->dayLabel = $dayKey ? $dayLabels[$dayKey] : \Illuminate\Support\Carbon::parse($att->date)->translatedFormat('l');
                return $att;
            });

        $hasPrevAttendance = Attendance::where('student_id', $alumno->id)
            ->where('date', '<', $weekStart->toDateString())->exists();
        $hasNextAttendance = Attendance::where('student_id', $alumno->id)
            ->where('date', '>', $weekEnd->toDateString())->exists();

        // ── Materials ─────────────────────────────────────────────────────────
        $materials = Material::with(['teacher', 'course'])
            ->whereIn('course_id', $courseIds)
            ->latest()
            ->get();

        // ── Academic Calendar ─────────────────────────────────────────────────
        $calendarEvents = AcademicEvent::whereIn('target_role', ['all', 'parent', 'student'])
            ->orderBy('event_date')
            ->get();

        // ── Announcements ─────────────────────────────────────────────────────
        $announcements = Announcement::with('author')
            ->whereIn('target_role', ['all', 'student', 'parent'])
            ->latest()
            ->take(10)
            ->get();

        // ── Teacher contacts ──────────────────────────────────────────────────
        $teachers = $sectionCourses
            ->filter(fn($c) => $c->teacher !== null)
            ->map(fn($c) => [
                'name'         => $c->teacher->name,
                'email'        => $c->teacher->email,
                'course'       => $c->name,
                'phone'        => $c->teacher->teacherProfile?->phone ?? null,
                'especialidad' => $c->teacher->teacherProfile?->especialidad ?? null,
            ])
            ->values();

        return view('padres.show', compact(
            'alumno', 'siblings',
            'currentCourses', 'gradeMatrix', 'overallAvg', 'periods', 'history',
            'total', 'present', 'absent', 'late', 'justified',
            'attPct', 'absentPct', 'announcements', 'atRisk', 'atts',
            'scheduleGrid', 'days', 'times', 'recesos', 'materials', 'calendarEvents', 'teachers',
            'enrollment', 'currentSection',
            'sectionCourses', 'attendanceWeekGrid', 'weekAlerts', 'weekStart', 'weekEnd',
            'hasPrevAttendance', 'hasNextAttendance'
        ));
    }

    /**
     * Genera el reporte académico en PDF de un alumno, para que el padre
     * lo descargue individualmente. Usa exactamente los mismos datos que
     * la vista en pantalla (ver buildAcademicReport()).
     */
    public function exportPdf(User $alumno)
    {
        abort_unless($alumno->role === 'student', 404);

        $user = auth()->user();
        if ($user->isParent()) {
            $isChild = $user->children()->where('users.id', $alumno->id)->exists();
            if (!$isChild) abort(403);
        }

        $alumno->load('enrollments.section');

        $data = $this->academicReportService->build($alumno);

        ini_set('memory_limit', '512M');

        $pdf = Pdf::loadView('padres.pdf-reporte', array_merge($data, ['alumno' => $alumno]));

        $fileName = 'reporte_' . \Illuminate\Support\Str::slug($alumno->name) . '_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }
}
