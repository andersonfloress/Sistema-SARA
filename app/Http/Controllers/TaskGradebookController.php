<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\Section;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class TaskGradebookController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // ── Alumno: libreta personal ──────────────────────────────────
        if ($user->isStudent()) {
            return $this->studentView($user);
        }

        // ── Padre: libreta del hijo ───────────────────────────────────
        if ($user->isParent()) {
            $children    = $user->children()->orderBy('name')->get();
            $childId     = $request->filled('child_id') ? (int)$request->child_id : $children->first()?->id;
            $child       = $children->firstWhere('id', $childId);
            return $this->studentView($child, $children);
        }

        // ── Docente / Admin: libreta por sección + curso ──────────────
        // Secciones disponibles
        if ($user->isAdmin()) {
            $sections = Section::active()->orderBy('grade')->orderBy('name')->get();
        } else {
            // Solo secciones donde el docente tiene al menos un curso
            $sectionIds = Course::where('teacher_id', $user->id)->pluck('section_id')->unique();
            $sections   = Section::whereIn('id', $sectionIds)->orderBy('grade')->orderBy('name')->get();
        }

        $selectedSection = null;
        $courses         = collect();
        $selectedCourse  = null;
        $students        = collect();
        $tasks           = collect();
        $matrix          = [];    // [student_id][task_id] => TaskSubmission|null

        if ($request->filled('section_id')) {
            $selectedSection = $sections->firstWhere('id', (int)$request->section_id);

            if ($selectedSection) {
                if ($user->isAdmin()) {
                    $courses = $selectedSection->courses()->with('teacher')->orderBy('name')->get();
                } else {
                    $courses = $selectedSection->courses()
                        ->where('teacher_id', $user->id)
                        ->with('teacher')
                        ->orderBy('name')
                        ->get();
                }
            }
        }

        if ($request->filled('course_id') && $selectedSection) {
            $selectedCourse = $courses->firstWhere('id', (int)$request->course_id);

            if ($selectedCourse) {
                // Alumnos matriculados en esta sección
                $students = $selectedSection->students()
                    ->orderBy('name')
                    ->get();

                // Tareas de este curso ordenadas por fecha límite
                $tasks = Task::where('course_id', $selectedCourse->id)
                    ->orderBy('deadline')
                    ->get();

                // Construir matrix [student_id][task_id] => última submission
                if ($students->isNotEmpty() && $tasks->isNotEmpty()) {
                    $taskIds    = $tasks->pluck('id');
                    $studentIds = $students->pluck('id');

                    $submissions = TaskSubmission::whereIn('task_id', $taskIds)
                        ->whereIn('student_id', $studentIds)
                        ->orderByDesc('attempt')
                        ->get()
                        ->groupBy(fn($s) => $s->student_id . '_' . $s->task_id);

                    foreach ($students as $st) {
                        foreach ($tasks as $tk) {
                            $key = $st->id . '_' . $tk->id;
                            $matrix[$st->id][$tk->id] = $submissions->get($key)?->first();
                        }
                    }
                }
            }
        }

        return view('tareas.libreta', compact(
            'sections', 'selectedSection', 'courses', 'selectedCourse',
            'students', 'tasks', 'matrix'
        ));
    }

    private function studentView($student, $children = null)
    {
        if (!$student) {
            $grouped = collect();
            return view('tareas.libreta_alumno', compact('grouped', 'children'));
        }

        // Sección activa del alumno
        $sectionId = Enrollment::where('student_id', $student->id)
            ->join('sections', 'enrollments.section_id', '=', 'sections.id')
            ->orderByDesc('sections.year')
            ->value('enrollments.section_id');

        $courseIds = $sectionId
            ? Course::where('section_id', $sectionId)->pluck('id')
            : collect();

        $tasks = Task::with('course')
            ->whereIn('course_id', $courseIds)
            ->orderBy('deadline')
            ->get();

        $taskIds = $tasks->pluck('id');
        $submissions = TaskSubmission::where('student_id', $student->id)
            ->whereIn('task_id', $taskIds)
            ->orderByDesc('attempt')
            ->get()
            ->keyBy('task_id');

        // Agrupar tareas por curso
        $grouped = $tasks->groupBy('course_id')->map(function ($courseTasks) use ($submissions) {
            return $courseTasks->map(fn($t) => [
                'task'       => $t,
                'submission' => $submissions->get($t->id),
            ]);
        });

        return view('tareas.libreta_alumno', compact('grouped', 'children', 'student'));
    }
}
