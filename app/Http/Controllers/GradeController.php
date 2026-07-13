<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Course;
use App\Models\Section;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();
        $periods = ['I', 'II', 'III'];

        // ── Vista especial para alumno ────────────────────────────────────────
        if ($user->isStudent()) {
            return $this->studentFullView($user, $periods);
        }

        // ── Vista especial para padre: boleta del hijo seleccionado ──────────
        if ($user->isParent()) {
            $children = $user->children()->orderBy('name')->get();
            // Default al primer hijo si no se especificó child_id
            $childId  = $request->filled('child_id')
                ? (int) $request->child_id
                : $children->first()?->id;
            $child = $children->firstWhere('id', $childId);

            // Verificar que el hijo pertenece a este padre
            if ($child && $children->pluck('id')->contains($child->id)) {
                return $this->studentFullView($child, $periods, $children, $child);
            }

            // Sin hijos vinculados
            return view('calificaciones.student', [
                'student'     => $user,
                'section'     => null,
                'courses'     => collect(),
                'gradeMatrix' => [],
                'periods'     => $periods,
                'overallAvg'  => null,
                'history'     => [],
                'children'    => $children,
                'selectedChild' => null,
            ]);
        }

        // ── Secciones disponibles según rol (todas, para conocer los años) ────
        // Nota: las ramas isStudent() e isParent() ya retornaron antes de este punto.
        if ($user->isTeacher()) {
            $allSections = Section::whereHas('courses', fn($q) => $q->where('teacher_id', $user->id))
                               ->orderBy('grade')->orderBy('name')->get();
        } else {
            // admin
            $allSections = Section::orderBy('grade')->orderBy('name')->get();
        }

        // ── Años académicos disponibles y año seleccionado ────────────────────
        $years = $allSections->pluck('year')->unique()->sortDesc()->values();
        $selectedYear = $request->filled('year') && $years->contains((int) $request->year)
            ? (int) $request->year
            : $years->first();

        // ── Grados disponibles en el año seleccionado y grado seleccionado ────
        // Normalizamos a string: la columna `grade` puede venir como int desde la BD,
        // mientras que el parámetro de la URL siempre llega como string.
        $grades = $allSections->where('year', $selectedYear)->pluck('grade')->filter()->map(fn($g) => (string) $g)->unique()->sort()->values();
        $selectedGrade = $request->filled('grado') && $grades->contains((string) $request->grado)
            ? (string) $request->grado
            : null;

        // ── Secciones del año (y grado, si aplica) seleccionado ───────────────
        $sections = $allSections->where('year', $selectedYear)
            ->when($selectedGrade, fn($c) => $c->filter(fn($sec) => (string) $sec->grade === $selectedGrade))
            ->values();

        // ── Sección seleccionada ──────────────────────────────────────────────
        $selectedSection = null;
        if ($request->filled('section_id')) {
            $allowedIds = $sections->pluck('id');
            if ($allowedIds->contains((int) $request->section_id)) {
                $selectedSection = $sections->firstWhere('id', (int) $request->section_id);
            }
        }

        // ── Cursos de la sección ──────────────────────────────────────────────
        $courses        = collect();
        $selectedCourse = null;
        if ($selectedSection) {
            $courses = $selectedSection->courses()
                ->when($user->isTeacher(), fn($q) => $q->where('teacher_id', $user->id))
                ->orderBy('name')
                ->get();

            if ($request->filled('course_id')) {
                $allowedCourseIds = $courses->pluck('id');
                if ($allowedCourseIds->contains((int) $request->course_id)) {
                    $selectedCourse = $courses->firstWhere('id', (int) $request->course_id);
                }
            }
            if (!$selectedCourse && $courses->count() === 1) {
                $selectedCourse = $courses->first();
            }
        }

        // ── Grilla de notas [student_id][period] ─────────────────────────────
        $students    = collect();
        $gradeMatrix = [];

        if ($selectedCourse) {
            $studentIds = Enrollment::where('section_id', $selectedCourse->section_id)
                                    ->pluck('student_id');

            if ($user->isParent()) {
                $childIds   = $user->children()->pluck('users.id');
                $studentIds = $studentIds->intersect($childIds);
            }

            $students = User::whereIn('id', $studentIds)
                            ->with('studentProfile')
                            ->orderBy('name')
                            ->get();

            $rawGrades = Grade::where('course_id', $selectedCourse->id)
                              ->whereIn('student_id', $studentIds)
                              ->get();

            foreach ($rawGrades as $g) {
                $gradeMatrix[$g->student_id][$g->period] = [
                    'score'       => (float) $g->score,
                    'observation' => $g->observation,
                ];
            }
        }

        // ── Estado de avance por curso (¿tiene notas registradas?) ───────────
        // Solo cuenta alumnos actualmente matriculados en la sección (evita
        // inflar el conteo con notas históricas de alumnos ya no matriculados)
        // y usa una única consulta agregada para evitar N+1.
        $courseProgress = [];
        if ($courses->isNotEmpty()) {
            $enrolledStudentIds = Enrollment::where('section_id', $selectedSection->id)->pluck('student_id');
            $sectionStudentCount = $enrolledStudentIds->count();

            $gradedCounts = Grade::whereIn('course_id', $courses->pluck('id'))
                ->whereIn('student_id', $enrolledStudentIds)
                ->selectRaw('course_id, COUNT(DISTINCT student_id) as graded')
                ->groupBy('course_id')
                ->pluck('graded', 'course_id');

            foreach ($courses as $c) {
                $graded = (int) ($gradedCounts[$c->id] ?? 0);
                $courseProgress[$c->id] = [
                    'graded' => $graded,
                    'total'  => $sectionStudentCount,
                    'complete' => $sectionStudentCount > 0 && $graded >= $sectionStudentCount,
                ];
            }
        }

        return view('calificaciones.index', compact(
            'sections', 'selectedSection',
            'courses',  'selectedCourse',
            'students', 'gradeMatrix', 'periods',
            'years', 'selectedYear', 'courseProgress',
            'grades', 'selectedGrade'
        ));
    }

    /**
     * Vista completa de notas para un alumno (propio o hijo de padre):
     * año actual (tabla principal) + historial de grados anteriores.
     * $children y $selectedChild se pasan cuando lo llama un padre.
     */
    private function studentFullView(
        User $student,
        array $periods,
        $children    = null,
        $selectedChild = null
    )
    {
        // Todas las matrículas del alumno (actual + históricas), con su sección
        $allEnrollments = Enrollment::where('student_id', $student->id)
                                    ->with('section')
                                    ->get()
                                    ->sortByDesc(fn($e) => $e->section->year);

        // Año más reciente = año actual
        $currentYear = $allEnrollments->first()?->section?->year;

        // Separar: actual vs historial
        $currentEnrollment  = $allEnrollments->firstWhere(fn($e) => $e->section->year === $currentYear);
        $historicalEnrollments = $allEnrollments->filter(fn($e) => $e->section->year !== $currentYear)
                                                ->sortByDesc(fn($e) => $e->section->year)
                                                ->values();

        $section = $currentEnrollment?->section;

        // ── Año actual ───────────────────────────────────────────────────
        $courses = $section
            ? Course::with('teacher')->where('section_id', $section->id)->orderBy('name')->get()
            : collect();

        $courseIds = $courses->pluck('id');
        $rawGrades = Grade::where('student_id', $student->id)
                          ->whereIn('course_id', $courseIds)
                          ->get();

        $gradeMatrix = [];
        foreach ($rawGrades as $g) {
            $gradeMatrix[$g->course_id][$g->period] = [
                'score'       => (float) $g->score,
                'observation' => $g->observation,
            ];
        }

        $allScores  = $rawGrades->pluck('score')->map(fn($v) => (float) $v);
        $overallAvg = $allScores->count() > 0 ? round($allScores->avg(), 1) : null;

        // ── Historial de grados anteriores ───────────────────────────────
        // Cada elemento: [section, courses, gradeMatrix, overallAvg]
        $history = [];
        foreach ($historicalEnrollments as $enrollment) {
            $histSection   = $enrollment->section;
            $histCourses   = Course::with('teacher')
                                   ->where('section_id', $histSection->id)
                                   ->orderBy('name')
                                   ->get();
            $histCourseIds = $histCourses->pluck('id');
            $histGrades    = Grade::where('student_id', $student->id)
                                  ->whereIn('course_id', $histCourseIds)
                                  ->get();

            $histMatrix = [];
            foreach ($histGrades as $g) {
                $histMatrix[$g->course_id][$g->period] = [
                    'score'       => (float) $g->score,
                    'observation' => $g->observation,
                ];
            }

            $histScores = $histGrades->pluck('score')->map(fn($v) => (float) $v);
            $histAvg    = $histScores->count() > 0 ? round($histScores->avg(), 1) : null;

            $history[] = [
                'section'     => $histSection,
                'courses'     => $histCourses,
                'gradeMatrix' => $histMatrix,
                'overallAvg'  => $histAvg,
            ];
        }

        return view('calificaciones.student', compact(
            'student', 'section', 'courses',
            'gradeMatrix', 'periods', 'overallAvg',
            'history', 'children', 'selectedChild'
        ));
    }

    public function create(Request $request)
    {
        $user     = auth()->user();
        $sections = $user->isTeacher()
            ? Section::whereHas('courses', fn($q) => $q->where('teacher_id', $user->id))
                     ->orderByDesc('year')->orderBy('grade')->orderBy('name')->get()
            : Section::orderByDesc('year')->orderBy('grade')->orderBy('name')->get();

        // Validar que la sección solicitada pertenece al conjunto permitido para este usuario.
        // Section::find() sin esta verificación permitiría a un docente acceder a cualquier
        // sección pasando un section_id arbitrario en la URL.
        $selectedSection = null;
        if ($request->filled('section_id')) {
            $allowedSectionIds = $sections->pluck('id');
            if ($allowedSectionIds->contains((int) $request->section_id)) {
                $selectedSection = $sections->firstWhere('id', (int) $request->section_id);
            }
        }

        $selectedCourse  = $request->filled('course_id') ? Course::find($request->course_id) : null;
        $selectedPeriod  = $request->input('period', 'I');

        // Teachers can only access their own courses
        if ($selectedCourse && $user->isTeacher() && (int) $selectedCourse->teacher_id !== (int) $user->id) {
            abort(403, 'No tienes permiso para acceder a este curso.');
        }

        $courses = $selectedSection
            ? $selectedSection->courses()
                ->when($user->isTeacher(), fn($q) => $q->where('teacher_id', $user->id))
                ->get()
            : collect();

        $students = collect();
        $existingGrades = [];

        if ($selectedCourse) {
            $studentIds = Enrollment::where('section_id', $selectedCourse->section_id)->pluck('student_id');
            $students   = User::whereIn('id', $studentIds)->orderBy('name')->get();

            $existingGrades = Grade::where('course_id', $selectedCourse->id)
                ->where('period', $selectedPeriod)
                ->get()
                ->keyBy('student_id');
        }

        return view('calificaciones.create', compact(
            'sections', 'selectedSection', 'selectedCourse',
            'selectedPeriod', 'courses', 'students', 'existingGrades'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id'            => 'required|exists:courses,id',
            'period'               => 'required|in:I,II,III',
            'grades'               => 'required|array',
            'grades.*.score'       => 'nullable|numeric|min:0|max:20',
            'grades.*.observation' => 'nullable|string|max:500',
        ], [
            'grades.*.score.numeric' => 'La nota debe ser un número.',
            'grades.*.score.min'     => 'La nota mínima es 0.',
            'grades.*.score.max'     => 'La nota máxima es 20.',
        ]);

        $user   = auth()->user();
        $course = Course::findOrFail($request->course_id);

        // Teachers may only register grades for courses they teach
        if ($user->isTeacher() && (int) $course->teacher_id !== (int) $user->id) {
            abort(403, 'No tienes permiso para registrar notas en este curso.');
        }

        $courseId  = $course->id;
        $period    = $request->period;
        $createdBy = $user->id;
        $saved     = 0;

        // Verify all student IDs belong to this section's enrollment
        $enrolledIds = Enrollment::where('section_id', $course->section_id)->pluck('student_id')->flip();

        foreach ($request->grades as $studentId => $data) {
            // Skip blank scores
            if (!isset($data['score']) || $data['score'] === '' || $data['score'] === null) {
                continue;
            }
            // Skip students not enrolled in this section
            if (!$enrolledIds->has($studentId)) {
                continue;
            }

            Grade::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'course_id'  => $courseId,
                    'period'     => $period,
                ],
                [
                    'score'       => $data['score'],
                    'observation' => $data['observation'] ?? null,
                    'created_by'  => $createdBy,
                ]
            );
            $saved++;
        }

        return redirect()->route('calificaciones.index')
                         ->with('success', "Se guardaron {$saved} calificaciones correctamente.");
    }
}
