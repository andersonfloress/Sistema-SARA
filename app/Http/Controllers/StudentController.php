<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\StudentProfile;
use App\Models\ParentProfile;
use App\Models\User;
use App\Http\Requests\UpdateStudentProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // ── Umbrales del semáforo — fuente única de verdad ────────────────
        $thresholds = [
            'risk_grade'       => 11,
            'risk_absent_pct'  => 30,
            'attn_grade'       => 14,
            'attn_att_pct'     => 85,
        ];

        // ── Cargar secciones visibles según rol ────────────────────────────
        $flatSections = collect();
        if ($user->isTeacher()) {
            // Eager-load solo los cursos que imparte este docente (para mostrarlos en sidebar)
            $flatSections = Section::whereHas('courses', fn($q) => $q->where('teacher_id', $user->id))
                                   ->with(['courses' => fn($q) => $q->where('teacher_id', $user->id)->select('id', 'name', 'section_id')])
                                   ->orderByDesc('year')->orderBy('grade')->orderBy('name')->get();
        } elseif ($user->isAdmin()) {
            $flatSections = Section::orderByDesc('year')->orderBy('grade')->orderBy('name')->get();
        } elseif ($user->isStudent()) {
            $flatSections = Section::whereHas('enrollments', fn($q) => $q->where('student_id', $user->id))
                                   ->orderByDesc('year')->orderBy('grade')->orderBy('name')->get();
        }

        // Agrupar por año → acordeón del sidebar
        $allSections = $flatSections->groupBy('year');

        // Estado inicial del acordeón: año más reciente abierto
        $openYears = [];
        foreach ($allSections->keys() as $i => $yr) {
            $openYears[(string) $yr] = ($i === 0);
        }

        // ── Sección seleccionada ──────────────────────────────────────────
        $selectedSection = null;
        if ($request->filled('section_id')) {
            $selectedSection = $flatSections->firstWhere('id', (int) $request->section_id);
        } elseif ($flatSections->count() === 1) {
            $selectedSection = $flatSections->first();
        }

        // ── Mapa section_id → nombres de cursos del docente (sidebar) ─────
        $teacherCoursesBySectionId = [];
        if ($user->isTeacher()) {
            foreach ($flatSections as $sec) {
                $teacherCoursesBySectionId[$sec->id] = $sec->courses->pluck('name');
            }
        }

        // ── Alumnos de la sección seleccionada ───────────────────────────
        // Grades y attendances filtrados a los cursos de ESTA sección,
        // no a todo el historial del alumno.
        $students = collect();
        if ($selectedSection) {
            $selectedSection->loadMissing('courses');
            $sectionCourseIds = $selectedSection->courses->pluck('id')->toArray();

            $studentIds = Enrollment::where('section_id', $selectedSection->id)->pluck('student_id');
            $students   = User::whereIn('id', $studentIds)
                              ->orderBy('name')
                              ->with([
                                  'studentProfile',
                                  'grades'      => fn($q) => $q->whereIn('course_id', $sectionCourseIds),
                                  'attendances' => fn($q) => $q->whereIn('course_id', $sectionCourseIds),
                              ])
                              ->get()
                              ->each(function ($s) use ($thresholds) {
                                  $avgs = $s->grades->groupBy('course_id')
                                      ->map(fn($g) => $g->avg('score'))
                                      ->filter()
                                      ->values();
                                  $s->gradeAvg = $avgs->count() > 0
                                      ? round($avgs->avg(), 1)
                                      : null;

                                  $total       = $s->attendances->count();
                                  $effective   = $s->attendances->whereIn('status', ['present', 'justified'])->count();
                                  $absent      = $s->attendances->where('status', 'absent')->count();
                                  $s->attPct    = $total > 0 ? round(($effective / $total) * 100) : null;
                                  $s->attAbsent = $absent;

                                  $absentPct = $total > 0 ? round(($absent / $total) * 100) : 0;
                                  $s->atRisk = ($s->gradeAvg !== null && $s->gradeAvg < $thresholds['risk_grade'])
                                      || $absentPct > $thresholds['risk_absent_pct'];
                              });
        }

        // ── Búsqueda global por DNI (solo admin) ──────────────────────────
        $dniQuery   = null;
        $dniResults = collect();
        if ($user->isAdmin() && $request->filled('dni')) {
            $dniQuery   = trim($request->input('dni'));
            $searchYear = $selectedSection?->year ?? $allSections->keys()->first();

            if ($dniQuery !== '' && $searchYear) {
                $sectionIdsOfYear = $flatSections->where('year', $searchYear)->pluck('id');
                $studentIdsOfYear = Enrollment::whereIn('section_id', $sectionIdsOfYear)->pluck('student_id');

                $dniResults = User::whereIn('id', $studentIdsOfYear)
                    ->whereHas('studentProfile', fn($q) => $q->where('dni', 'like', '%' . $dniQuery . '%'))
                    ->with(['studentProfile', 'enrollments' => fn($q) => $q->whereIn('section_id', $sectionIdsOfYear)->with('section')])
                    ->orderBy('name')
                    ->get();
            }
        }

        return view('alumnos.index', compact(
            'allSections', 'openYears', 'selectedSection', 'students',
            'dniQuery', 'dniResults', 'thresholds', 'teacherCoursesBySectionId'
        ));
    }

    public function show(User $alumno)
    {
        abort_unless($alumno->role === 'student', 404);

        $user = auth()->user();

        if ($user->isStudent() && $user->id !== $alumno->id) {
            abort(403);
        }

        if ($user->isTeacher()) {
            $teacherSectionIds = Section::whereHas('courses', fn($q) => $q->where('teacher_id', $user->id))
                                        ->pluck('id');
            $inSection = Enrollment::whereIn('section_id', $teacherSectionIds)
                                   ->where('student_id', $alumno->id)
                                   ->exists();
            if (! $inSection) {
                abort(403);
            }
        }

        // ── Fase 1: cargar matrículas para determinar el año de contexto ──
        $alumno->load(['enrollments.section', 'studentProfile', 'parents']);

        $latestEnrollment = $alumno->enrollments->sortByDesc('year')->first();
        $contextYear      = $latestEnrollment?->section?->year ?? now()->year;

        // IDs de cursos del año de contexto (filtro para notas y asistencia)
        $enrolledSectionIds = $alumno->enrollments
            ->where('year', $contextYear)
            ->pluck('section_id');
        $yearCourseIds = Course::whereIn('section_id', $enrolledSectionIds)
            ->pluck('id')->toArray();

        // Cursos que imparte el docente autenticado dentro de ese año
        $teacherCourseIds = [];
        if ($user->isTeacher() && ! empty($yearCourseIds)) {
            $teacherCourseIds = Course::whereIn('id', $yearCourseIds)
                ->where('teacher_id', $user->id)
                ->pluck('id')->toArray();
        }

        // ── Fase 2: cargar notas y asistencias filtradas al año ───────────
        $alumno->load([
            'grades'      => fn($q) => $q->whereIn('course_id', $yearCourseIds)->with('course'),
            'attendances' => fn($q) => $q->whereIn('course_id', $yearCourseIds),
        ]);

        // Grades by course
        $gradesByCourse = [];
        foreach ($alumno->grades as $g) {
            $key = $g->course_id;
            if (!isset($gradesByCourse[$key])) {
                $gradesByCourse[$key] = [
                    'courseName'      => $g->course->name,
                    'grades'          => [],
                    'isTeacherCourse' => in_array($g->course_id, $teacherCourseIds),
                ];
            }
            $gradesByCourse[$key]['grades'][$g->period] = (float) $g->score;
        }

        foreach ($gradesByCourse as &$c) {
            $vals = array_values($c['grades']);
            $c['avg'] = count($vals) > 0 ? round(array_sum($vals) / count($vals), 1) : null;
        }
        unset($c);

        $allAvgs    = array_filter(array_column($gradesByCourse, 'avg'));
        $overallAvg = count($allAvgs) > 0 ? round(array_sum($allAvgs) / count($allAvgs), 1) : null;

        // Attendance summary
        $attTotal     = $alumno->attendances->count();
        $attPresent   = $alumno->attendances->where('status', 'present')->count();
        $attAbsent    = $alumno->attendances->where('status', 'absent')->count();
        $attLate      = $alumno->attendances->where('status', 'late')->count();
        $attJustified = $alumno->attendances->where('status', 'justified')->count();
        $effective    = $attPresent + $attJustified;
        $attPct       = $attTotal > 0 ? round(($effective / $attTotal) * 100) : 100;
        $absentPct    = $attTotal > 0 ? round(($attAbsent / $attTotal) * 100) : 0;

        $atRisk = ($overallAvg !== null && $overallAvg < 11) || $absentPct > 30;

        return view('alumnos.show', compact(
            'alumno', 'gradesByCourse', 'overallAvg',
            'attTotal', 'attPresent', 'attAbsent', 'attLate', 'attJustified',
            'attPct', 'absentPct', 'atRisk',
            'contextYear', 'teacherCourseIds'
        ));
    }

    public function editProfile(User $alumno)
    {
        abort_unless($alumno->role === 'student', 404);
        $profile = $alumno->studentProfile ?? new StudentProfile(['student_id' => $alumno->id]);
        return view('alumnos.edit-profile', compact('alumno', 'profile'));
    }

    public function updateProfile(UpdateStudentProfileRequest $request, User $alumno)
    {
        abort_unless($alumno->role === 'student', 404);

        $data = $request->validated();

        // ── Foto de perfil ────────────────────────────────────────────────────
        $profile = $alumno->studentProfile;
        unset($data['foto_perfil'], $data['parentesco']);

        if ($request->hasFile('foto_perfil')) {
            if ($profile?->foto_perfil) {
                Storage::disk('public')->delete($profile->foto_perfil);
            }
            $data['foto_perfil'] = $request->file('foto_perfil')->store('alumnos/fotos', 'public');
        }

        // ── Persistir perfil ─────────────────────────────────────────────────
        $profile = StudentProfile::updateOrCreate(
            ['student_id' => $alumno->id],
            $data
        );

        // ── Auto-vincular padre por DNI del apoderado ────────────────────────
        if ($request->filled('dni_apoderado')) {
            $parentProfile = ParentProfile::where('dni', $request->dni_apoderado)->first();
            if ($parentProfile) {
                DB::table('parent_students')->updateOrInsert(
                    ['parent_id' => $parentProfile->parent_id, 'student_id' => $alumno->id],
                    [
                        'parentesco' => $request->parentesco ?? null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        return redirect()->route('alumnos.show', $alumno)
                         ->with('success', 'Perfil del alumno actualizado correctamente.');
    }
}
