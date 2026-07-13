<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ScheduleSlot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    // ── Vista mensual (index) ──────────────────────────────────────────────────
    public function index(Request $request)
    {
        $user = auth()->user();

        // ── Padre: selector de hijo ───────────────────────────────────────────
        $children      = collect();
        $selectedChild = null;
        if ($user->isParent()) {
            $children = $user->children()->orderBy('name')->get();
            $childId  = $request->filled('child_id')
                ? (int) $request->child_id
                : $children->first()?->id;
            $selectedChild = $children->firstWhere('id', $childId);
        }

        // Cursos disponibles por rol — basado en matrícula, no en registros previos
        $allCourses = match (true) {
            $user->isTeacher() => Course::where('teacher_id', $user->id)
                                        ->with('section')->get(),
            $user->isStudent() => Course::whereHas('section', fn($q) =>
                                        $q->whereHas('enrollments', fn($q2) =>
                                            $q2->where('student_id', $user->id)))
                                        ->with('section')->get(),
            $user->isParent()  => $selectedChild
                // Si hay hijo seleccionado, solo sus cursos
                ? Course::whereHas('section', fn($q) =>
                      $q->whereHas('enrollments', fn($q2) =>
                          $q2->where('student_id', $selectedChild->id)))
                      ->with('section')->get()
                : collect(),
            default            => Course::with('section')->get(),
        };

        // ── Años académicos disponibles y año seleccionado ────────────────────
        $years = $allCourses->pluck('section.year')->filter()->unique()->sortDesc()->values();
        $selectedYear = $request->filled('year') && $years->contains((int) $request->year)
            ? (int) $request->year
            : $years->first();

        $coursesOfYear = $allCourses->filter(fn($c) => (int) $c->section?->year === (int) $selectedYear)->values();

        // ── Grados disponibles en el año seleccionado y grado seleccionado ────
        // Normalizamos a string: la columna `grade` puede venir como int desde la BD,
        // mientras que el parámetro de la URL siempre llega como string.
        $grades = $coursesOfYear->pluck('section.grade')->filter()->map(fn($g) => (string) $g)->unique()->sort()->values();
        $selectedGrade = $request->filled('grado') && $grades->contains((string) $request->grado)
            ? (string) $request->grado
            : null;

        $coursesOfGrade = $coursesOfYear
            ->when($selectedGrade, fn($c) => $c->filter(fn($course) => (string) $course->section?->grade === $selectedGrade))
            ->values();

        // ── Secciones disponibles en el año/grado seleccionado y sección seleccionada ─
        $sections = $coursesOfGrade->pluck('section')->filter()->unique('id')
            ->sortBy([['grade', 'asc'], ['name', 'asc']])->values();
        $selectedSectionId = null;
        if ($request->filled('section_id')) {
            $allowedSectionIds = $sections->pluck('id');
            if ($allowedSectionIds->contains((int) $request->section_id)) {
                $selectedSectionId = (int) $request->section_id;
            }
        }

        $courses = $coursesOfGrade
            ->when($selectedSectionId, fn($c) => $c->filter(fn($course) => $course->section_id === $selectedSectionId))
            ->values();

        // Parámetros seleccionados — validar course_id contra cursos permitidos
        $selectedCourse = null;
        if ($request->filled('course_id')) {
            $allowedIds = $courses->pluck('id');
            if ($allowedIds->contains((int) $request->course_id)) {
                $selectedCourse = $courses->firstWhere('id', (int) $request->course_id);
            }
        }
        if (!$selectedCourse && $selectedSectionId && $courses->count() === 1) {
            $selectedCourse = $courses->first();
        }

        // Mes seleccionado (default = mes actual)
        try {
            $month = $request->filled('month')
                ? Carbon::parse($request->month . '-01')
                : Carbon::now()->startOfMonth();
        } catch (\Exception $e) {
            $month = Carbon::now()->startOfMonth();
        }
        $monthStart  = $month->copy()->startOfMonth();
        $monthEnd    = $month->copy()->endOfMonth();
        $daysInMonth = $monthEnd->day;

        // Matriz de asistencia [student_id][day_of_month] = status
        $students         = collect();
        $attendanceMatrix = [];

        if ($selectedCourse) {
            $studentIds = Enrollment::where('section_id', $selectedCourse->section_id)
                ->pluck('student_id');

            // Alumno ve solo su fila; padre ve solo el hijo seleccionado
            if ($user->isStudent()) {
                $studentIds = $studentIds->intersect([$user->id]);
            } elseif ($user->isParent() && $selectedChild) {
                $studentIds = $studentIds->intersect([$selectedChild->id]);
            }

            $students = User::whereIn('id', $studentIds)->orderBy('name')->get();

            $existing = Attendance::where('course_id', $selectedCourse->id)
                ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->whereIn('student_id', $studentIds)
                ->with('creator')
                ->get();

            $creatorMatrix = [];
            foreach ($existing as $att) {
                $attendanceMatrix[$att->student_id][$att->date->day] = $att->status;
                if ($att->created_by) {
                    $creatorMatrix[$att->student_id][$att->date->day] = $att->creator?->name ?? null;
                }
            }
        }

        // ── Semana destino para el botón "Registrar asistencia" ──────────────
        // Mes actual → semana actual (sin parámetro).
        // Mes pasado → última semana de lunes que empieza dentro de ese mes.
        $registerWeekStart = null;
        if (!$month->isSameMonth(Carbon::now())) {
            $lastMonday = $monthEnd->copy()->startOfWeek(Carbon::MONDAY);
            if ($lastMonday->month !== $monthEnd->month) {
                $lastMonday->subWeek();
            }
            $registerWeekStart = $lastMonday->toDateString();
        }

        // $creatorMatrix puede no haberse inicializado si no hay selectedCourse
        if (!isset($creatorMatrix)) $creatorMatrix = [];

        return view('asistencia.index', compact(
            'courses', 'selectedCourse', 'month', 'monthStart', 'monthEnd',
            'daysInMonth', 'students', 'attendanceMatrix', 'creatorMatrix',
            'children', 'selectedChild', 'years', 'selectedYear',
            'grades', 'selectedGrade', 'sections', 'selectedSectionId',
            'registerWeekStart'
        ));
    }

    // ── Grilla semanal (registro) ──────────────────────────────────────────────
    public function create(Request $request)
    {
        $user = auth()->user();

        // Solo cursos del año académico activo para no mezclar años cerrados
        $activeYear = \App\Models\AcademicYear::currentYear();
        $courses = $user->isTeacher()
            ? Course::where('teacher_id', $user->id)
                    ->whereHas('section', fn($q) => $q->where('year', $activeYear))
                    ->with('section')->orderBy('name')->get()
            : Course::whereHas('section', fn($q) => $q->where('year', $activeYear))
                    ->with('section')->orderBy('name')->get();

        $selectedCourse = $request->filled('course_id') ? Course::with('section')->find($request->course_id) : null;

        if ($selectedCourse && $user->isTeacher() && (int) $selectedCourse->teacher_id !== (int) $user->id) {
            abort(403, 'No tienes permiso para este curso.');
        }

        try {
            $weekStart = $request->filled('week_start')
                ? Carbon::parse($request->week_start)->startOfWeek(Carbon::MONDAY)
                : Carbon::now()->startOfWeek(Carbon::MONDAY);
        } catch (\Exception $e) {
            $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        }

        $weekEnd          = $weekStart->copy()->endOfWeek(Carbon::FRIDAY);
        $today            = Carbon::today();
        $currentWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);

        // Solo se pide asistencia los días que el curso realmente tiene clase
        // según su horario (schedule_slots). Antes se mostraban las 5 columnas
        // lunes-viernes siempre, aunque el curso solo dictara 2 o 3 veces por
        // semana, lo que llevaba a registrar (y luego mostrar) asistencia en
        // días sin clase.
        $dayKeys = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
        $courseDays = $selectedCourse
            ? \App\Models\ScheduleSlot::where('course_id', $selectedCourse->id)->pluck('day_of_week')->unique()
            : collect();

        $weekDays = collect(range(0, 4))
            ->map(fn($i) => $weekStart->copy()->addDays($i))
            ->when($selectedCourse, fn($collection) => $collection->filter(
                fn($day) => $courseDays->contains($dayKeys[$day->dayOfWeekIso - 1])
            ))
            ->values();

        $students         = collect();
        $attendanceMatrix = [];

        if ($selectedCourse) {
            $studentIds = Enrollment::where('section_id', $selectedCourse->section_id)->pluck('student_id');
            $students   = User::whereIn('id', $studentIds)->orderBy('name')->get();

            $existing = Attendance::where('course_id', $selectedCourse->id)
                ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->whereIn('student_id', $studentIds)
                ->get();

            foreach ($existing as $att) {
                $attendanceMatrix[$att->student_id][$att->date->format('Y-m-d')] = $att->status;
            }
        }

        // ── Faltas acumuladas por alumno en este curso (todo el año) ─────────
        // Se muestra en la grilla para que el docente vea el historial al registrar.
        $annualAbsences = [];
        if ($selectedCourse && $students->isNotEmpty()) {
            $annualAbsences = Attendance::where('course_id', $selectedCourse->id)
                ->where('status', 'absent')
                ->whereIn('student_id', $students->pluck('id'))
                ->selectRaw('student_id, COUNT(*) as total')
                ->groupBy('student_id')
                ->pluck('total', 'student_id')
                ->toArray();
        }

        // Eventos/feriados del calendario que caen en la semana mostrada
        $feriadosByDate = collect();
        if ($selectedCourse) {
            $feriadosByDate = \App\Models\AcademicEvent::whereBetween('event_date', [
                $weekStart->toDateString(),
                $weekEnd->toDateString(),
            ])->get()->keyBy(fn($e) => $e->event_date->format('Y-m-d'));
        }

        return view('asistencia.create', compact(
            'courses', 'selectedCourse', 'weekStart', 'weekEnd',
            'weekDays', 'today', 'currentWeekStart',
            'students', 'attendanceMatrix', 'annualAbsences',
            'feriadosByDate'
        ));
    }

    // ── Guardar semana ─────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'course_id'  => 'required|exists:courses,id',
            'week_start' => 'required|date',
        ]);

        $user   = auth()->user();
        $course = Course::findOrFail($request->course_id);

        if ($user->isTeacher() && (int) $course->teacher_id !== (int) $user->id) {
            abort(403, 'No tienes permiso para registrar asistencia en este curso.');
        }

        $today       = now()->toDateString();
        $validStatus = ['present', 'absent', 'late', 'justified'];

        // ── Límites de la semana enviada ──────────────────────────────────────
        $weekStartDate = Carbon::parse($request->week_start)->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEndDate   = Carbon::parse($request->week_start)->endOfWeek(Carbon::FRIDAY)->toDateString();

        // ── Días de clase según horario del curso ─────────────────────────────
        // Mapeamos nombre de día → número ISO (1=lun … 5=vie).
        $dayKeyToIso = ['lunes' => 1, 'martes' => 2, 'miercoles' => 3, 'jueves' => 4, 'viernes' => 5];
        $scheduledIsoDays = ScheduleSlot::where('course_id', $course->id)
            ->pluck('day_of_week')
            ->map(fn($d) => $dayKeyToIso[$d] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->toArray();
        $hasSchedule = ! empty($scheduledIsoDays);

        $enrolledIds = Enrollment::where('section_id', $course->section_id)
            ->pluck('student_id')->flip();

        $saved = 0;

        foreach (($request->attendance ?? []) as $studentId => $dates) {
            if (! $enrolledIds->has($studentId)) continue;

            foreach ($dates as $date => $status) {
                // Debe estar dentro de la semana del formulario
                if ($date < $weekStartDate || $date > $weekEndDate) continue;
                // No fechas futuras
                if ($date > $today) continue;
                // Solo días de clase programados (si el curso tiene horario)
                if ($hasSchedule && ! in_array(Carbon::parse($date)->isoWeekday(), $scheduledIsoDays)) continue;

                if (in_array($status, $validStatus)) {
                    Attendance::updateOrCreate(
                        ['student_id' => $studentId, 'course_id' => $course->id, 'date' => $date],
                        ['status' => $status, 'created_by' => $user->id]
                    );
                    $saved++;
                } elseif ($status === '') {
                    Attendance::where('student_id', $studentId)
                        ->where('course_id', $course->id)
                        ->whereDate('date', $date)
                        ->delete();
                }
            }
        }

        return redirect()->route('asistencia.create', [
            'course_id'  => $course->id,
            'week_start' => $request->week_start,
        ])->with('success', "Asistencia guardada: $saved registro(s).");
    }
}
