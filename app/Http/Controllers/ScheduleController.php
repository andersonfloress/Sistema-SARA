<?php

namespace App\Http\Controllers;

use App\Models\ScheduleSlot;
use App\Models\Course;
use App\Models\Section;
use App\Models\Enrollment;
use App\Http\Requests\StoreScheduleSlotRequest;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    private array $days = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

    /**
     * Horas de inicio de los periodos de clase reales, usadas solo como
     * respaldo cuando todavía no existe ningún bloque registrado en el
     * sistema (institución recién creada, sin horarios configurados aún).
     * En operación normal, las horas de la cuadrícula se calculan a partir
     * de los bloques realmente guardados en `schedule_slots` (ver
     * buildTimeSlots()), porque los periodos de clase no caen en horas en
     * punto (ej. 07:30, 08:10, 08:50…) y una lista fija de horas en punto
     * dejaba filas vacías y desordenaba la cuadrícula.
     */
    private array $fallbackTimes = ['07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

    /**
     * Recreos institucionales (fijos, no son bloques de curso en
     * schedule_slots). Se muestran como una fila especial en la cuadrícula,
     * en lugar de dejar un salto silencioso entre periodos que parece un
     * error de datos. Las horas coinciden con el hueco real que ya existe
     * entre los bloques de clase sembrados (ver ScheduleSeeder):
     * mañana 09:30-10:10 → receso 10:10-10:30 → 10:30-11:10…
     * tarde  15:00-15:40 → receso 15:40-16:00 → 16:00-16:40…
     */
    private array $recesos = [
        '10:10' => ['fin' => '10:30', 'turno' => 'mañana'],
        '15:40' => ['fin' => '16:00', 'turno' => 'tarde'],
    ];

    /**
     * Devuelve, ordenadas cronológicamente, todas las horas de inicio
     * distintas que existen en schedule_slots, más las horas fijas de
     * recreo. Se usa el conjunto global (no solo el de la sección/consulta
     * actual) para que la cuadrícula tenga siempre las mismas filas para
     * todos los días de la semana, aunque un día en particular no tenga
     * bloque en algún periodo.
     */
    private function buildTimeSlots(): array
    {
        $times = ScheduleSlot::query()
            ->distinct()
            ->orderBy('start_time')
            ->pluck('start_time')
            ->all();

        if (!$times) {
            return $this->fallbackTimes;
        }

        $times = array_unique(array_merge($times, array_keys($this->recesos)));
        sort($times);

        return $times;
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = ScheduleSlot::with('course.section', 'course.teacher');

        if ($user->isTeacher()) {
            $query->whereHas('course', fn($q) => $q->where('teacher_id', $user->id));
        } elseif ($user->isStudent()) {
            $sectionId = Enrollment::where('enrollments.student_id', $user->id)
                ->join('sections', 'enrollments.section_id', '=', 'sections.id')
                ->orderByDesc('sections.year')
                ->value('enrollments.section_id');
            if ($sectionId) {
                $query->whereHas('course', fn($q) => $q->where('section_id', $sectionId));
            } else {
                $query->whereRaw('1=0');
            }
        } elseif ($user->isParent()) {
            $children = $user->children()->orderBy('name')->get();
            $childId  = $request->filled('child_id')
                ? (int) $request->child_id
                : $children->first()?->id;
            $selectedChild = $children->firstWhere('id', $childId);

            if ($selectedChild) {
                $sectionId = Enrollment::where('enrollments.student_id', $selectedChild->id)
                    ->join('sections', 'enrollments.section_id', '=', 'sections.id')
                    ->orderByDesc('sections.year')
                    ->value('enrollments.section_id');
                if ($sectionId) {
                    $query->whereHas('course', fn($q) => $q->where('section_id', $sectionId));
                } else {
                    $query->whereRaw('1=0');
                }
            } else {
                $query->whereRaw('1=0');
            }
        } elseif ($request->filled('section_id')) {
            $query->whereHas('course', fn($q) => $q->where('section_id', $request->section_id));
        }

        $slots    = $query->get();
        // Admin: solo secciones del año activo en el filtro rápido de la vista de horario
        $sections = $user->isAdmin() ? Section::active()->orderBy('grade')->orderBy('name')->get() : collect();

        // Build grid usando las horas de inicio reales (ver buildTimeSlots()),
        // no una lista fija de horas en punto que no coincide con los
        // periodos de clase reales (07:30, 08:10, 08:50…).
        $times   = $this->buildTimeSlots();
        $recesos = $this->recesos;
        $grid    = [];
        foreach ($this->days as $day) {
            foreach ($times as $time) {
                // Los recreos no son bloques de curso: no reciben celda en
                // el grid, se pintan aparte como fila especial en la vista.
                if (isset($recesos[$time])) {
                    continue;
                }
                $grid[$day][$time] = null;
            }
        }
        foreach ($slots as $slot) {
            // Si un bloque llegara con una hora fuera del catálogo conocido
            // (dato inconsistente), se descarta en lugar de desordenar la
            // cuadrícula agregando una columna/fila fuera de $times.
            if (array_key_exists($slot->start_time, $grid[$slot->day_of_week] ?? [])) {
                $grid[$slot->day_of_week][$slot->start_time] = $slot;
            }
        }

        // Exponer children/selectedChild a la vista para el selector de padre
        $children      = $children      ?? collect();
        $selectedChild = $selectedChild ?? null;

        return view('horarios.index', compact('slots', 'grid', 'times', 'recesos', 'sections', 'children', 'selectedChild'));
    }

    public function adminIndex(Request $request)
    {
        // Años disponibles para el filtro (todos los que tienen secciones)
        $availableYears = Section::select('year')->distinct()->orderByDesc('year')->pluck('year');

        // Año seleccionado: parámetro de URL → año activo → más reciente
        $activeYear   = \App\Models\AcademicYear::currentYear();
        $selectedYear = $request->filled('year') && $availableYears->contains((int) $request->year)
            ? (int) $request->year
            : $activeYear;

        // Solo secciones del año seleccionado
        $sections = Section::with('courses')
            ->where('year', $selectedYear)
            ->orderBy('grade')->orderBy('name')
            ->get();

        $teachers = \App\Models\User::where('role', 'teacher')->orderBy('name')->get();

        $selectedSection = $request->filled('section_id')
            ? Section::with('courses.teacher')
                ->where('year', $selectedYear)   // evita section_id de otro año
                ->find($request->section_id)
            : null;

        $slots = $selectedSection
            ? ScheduleSlot::with('course.teacher')
                ->whereHas('course', fn($q) => $q->where('section_id', $selectedSection->id))
                ->orderBy('day_of_week')->orderBy('start_time')
                ->get()
            : collect();

        // Build grid for visual timetable
        $times   = $this->buildTimeSlots();
        $recesos = $this->recesos;
        $grid    = [];
        foreach ($this->days as $day) {
            foreach ($times as $time) {
                if (isset($recesos[$time])) continue;
                $grid[$day][$time] = null;
            }
        }
        foreach ($slots as $slot) {
            if (array_key_exists($slot->start_time, $grid[$slot->day_of_week] ?? [])) {
                $grid[$slot->day_of_week][$slot->start_time] = $slot;
            }
        }

        // Count assigned slots per course for the summary badge
        $courseSlotCounts = $slots->groupBy('course_id')->map->count();

        return view('horarios.admin', compact(
            'sections', 'teachers', 'selectedSection', 'slots',
            'grid', 'times', 'recesos', 'courseSlotCounts',
            'availableYears', 'selectedYear', 'activeYear'
        ));
    }

    private function findConflict(Course $course, string $day, string $start, ?string $classroom, ?int $excludeId = null): ?string
    {
        // Choque en la misma sección (dos cursos de la misma sección al mismo día/hora)
        $sectionConflict = ScheduleSlot::whereHas('course', fn($q) => $q->where('section_id', $course->section_id))
                                ->where('day_of_week', $day)
                                ->where('start_time', $start)
                                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                                ->exists();
        if ($sectionConflict) {
            return 'Ya existe un bloque en esa sección, día y hora.';
        }

        // Choque de docente (mismo profesor en otra sección al mismo día/hora)
        if ($course->teacher_id) {
            $teacherConflict = ScheduleSlot::whereHas('course', fn($q) => $q->where('teacher_id', $course->teacher_id)->where('section_id', '!=', $course->section_id))
                                ->where('day_of_week', $day)
                                ->where('start_time', $start)
                                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                                ->exists();
            if ($teacherConflict) {
                return "El docente {$course->teacher->name} ya dicta otra sección en ese día y hora.";
            }
        }

        // Choque de aula (misma aula ocupada por otra sección al mismo día/hora)
        if ($classroom) {
            $classroomConflict = ScheduleSlot::where('classroom', $classroom)
                                ->whereHas('course', fn($q) => $q->where('section_id', '!=', $course->section_id))
                                ->where('day_of_week', $day)
                                ->where('start_time', $start)
                                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                                ->exists();
            if ($classroomConflict) {
                return "El aula {$classroom} ya está ocupada por otra sección en ese día y hora.";
            }
        }

        return null;
    }

    public function store(StoreScheduleSlotRequest $request)
    {
        $course = Course::with('teacher')->findOrFail($request->course_id);
        $error  = $this->findConflict($course, $request->day_of_week, $request->start_time, $request->classroom);

        if ($error) {
            return back()->withErrors(['start_time' => $error])->withInput();
        }

        ScheduleSlot::create($request->validated());

        return redirect()->route('horarios.admin', [
                             'section_id' => $course->section_id,
                             'year'       => $request->input('year_redirect'),
                         ])->with('success', 'Bloque de horario creado correctamente.');
    }

    public function update(StoreScheduleSlotRequest $request, ScheduleSlot $horario)
    {
        $course = Course::with('teacher')->findOrFail($request->course_id);
        $error  = $this->findConflict($course, $request->day_of_week, $request->start_time, $request->classroom, $horario->id);

        if ($error) {
            return back()->withErrors(['start_time' => $error])->withInput();
        }

        $horario->update($request->validated());

        return redirect()->back()->with('success', 'Bloque actualizado correctamente.');
    }

    public function destroy(ScheduleSlot $horario)
    {
        $sectionId  = $horario->course->section_id;
        $sectionYear = $horario->course->section->year;
        $horario->delete();

        return redirect()->route('horarios.admin', [
                             'section_id' => $sectionId,
                             'year'       => $sectionYear,
                         ])->with('success', 'Bloque eliminado correctamente.');
    }
}
