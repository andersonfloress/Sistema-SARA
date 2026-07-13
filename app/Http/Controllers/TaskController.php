<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Task::with('course.section', 'teacher', 'submissions');

        $courses = collect();

        if ($user->isTeacher()) {
            $courses   = Course::with('section')->where('teacher_id', $user->id)->orderBy('name')->get();
            $courseIds = $courses->pluck('id');
            $query->whereIn('course_id', $courseIds);
        } elseif ($user->isStudent()) {
            $sectionId = Enrollment::where('student_id', $user->id)
                ->join('sections', 'enrollments.section_id', '=', 'sections.id')
                ->orderByDesc('sections.year')
                ->value('enrollments.section_id');
            $courses   = $sectionId
                ? Course::with('section')->where('section_id', $sectionId)->orderBy('name')->get()
                : collect();
            $courseIds = $courses->pluck('id');
            $query->whereIn('course_id', $courseIds);
        } elseif ($user->isParent()) {
            $children   = $user->children()->orderBy('name')->get();
            $childId    = $request->filled('child_id') ? (int)$request->child_id : $children->first()?->id;
            $child      = $children->firstWhere('id', $childId);
            $sectionId  = $child
                ? Enrollment::where('student_id', $child->id)
                    ->join('sections', 'enrollments.section_id', '=', 'sections.id')
                    ->orderByDesc('sections.year')
                    ->value('enrollments.section_id')
                : null;
            $courses   = $sectionId
                ? Course::with('section')->where('section_id', $sectionId)->orderBy('name')->get()
                : collect();
            $courseIds = $courses->pluck('id');
            $query->whereIn('course_id', $courseIds);

            $tasks = $query->latest('deadline')->get();
            return view('tareas.index', compact('tasks', 'courses', 'children', 'child'));
        }

        $tasks = $query->latest('deadline')->get();
        return view('tareas.index', compact('tasks', 'courses'));
    }

    public function create()
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->isTeacher(), 403);

        $courses = $user->isAdmin()
            ? Course::with('section')->orderBy('name')->get()
            : Course::with('section')->where('teacher_id', $user->id)->orderBy('name')->get();

        return view('tareas.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->isTeacher(), 403);

        $data = $request->validate([
            'title'        => 'required|string|max:200',
            'description'  => 'nullable|string|max:2000',
            'deadline'     => 'required|date|after:now',
            'max_attempts' => 'required|integer|min:1|max:10',
            'course_id'    => 'required|exists:courses,id',
            'file'         => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480',
        ]);

        if ($user->isTeacher()) {
            $owns = Course::where('id', $data['course_id'])->where('teacher_id', $user->id)->exists();
            abort_unless($owns, 403, 'No puedes asignar tareas a un curso que no dictas.');
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('tareas/adjuntos', 'public');
        }

        Task::create([
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'deadline'     => $data['deadline'],
            'max_attempts' => $data['max_attempts'],
            'course_id'    => $data['course_id'],
            'teacher_id'   => $user->isAdmin() ? Course::find($data['course_id'])->teacher_id : $user->id,
            'file_path'    => $filePath,
        ]);

        return redirect()->route('tareas.index')->with('success', 'Tarea publicada correctamente.');
    }

    public function show(Task $tarea)
    {
        $user = auth()->user();
        $tarea->load('course.section', 'teacher', 'submissions.student');

        // Verificar acceso
        if ($user->isTeacher()) {
            abort_unless($tarea->teacher_id === $user->id, 403);
        } elseif ($user->isStudent()) {
            $sectionId = Enrollment::where('student_id', $user->id)
                ->join('sections', 'enrollments.section_id', '=', 'sections.id')
                ->orderByDesc('sections.year')
                ->value('enrollments.section_id');
            $allowed = $sectionId
                ? Course::where('section_id', $sectionId)->where('id', $tarea->course_id)->exists()
                : false;
            abort_unless($allowed, 403);
        } elseif ($user->isParent()) {
            // Verificar que al menos un hijo esté en el curso de la tarea
            $childIds   = $user->children()->pluck('users.id');
            $sectionIds = Enrollment::whereIn('student_id', $childIds)->pluck('section_id');
            $allowed    = Course::where('id', $tarea->course_id)->whereIn('section_id', $sectionIds)->exists();
            abort_unless($allowed, 403);
        }

        $mySubmissions = $user->isStudent()
            ? $tarea->submissionsForStudent($user->id)->get()
            : collect();

        $latestSubmission = $user->isStudent()
            ? $tarea->latestSubmissionForStudent($user->id)
            : null;

        // Para padres: submissions de sus hijos
        $childrenSubmissions = collect();
        if ($user->isParent()) {
            $childIds = $user->children()->pluck('users.id');
            $childrenSubmissions = $tarea->submissions()
                ->with('student')
                ->whereIn('student_id', $childIds)
                ->orderByDesc('attempt')
                ->get()
                ->groupBy('student_id');
        }

        // Para docente/admin: todas las submissions agrupadas por alumno
        $allSubmissions = collect();
        if ($user->isTeacher() || $user->isAdmin()) {
            $allSubmissions = $tarea->submissions()
                ->with('student')
                ->orderBy('student_id')
                ->orderByDesc('attempt')
                ->get()
                ->groupBy('student_id');
        }

        return view('tareas.show', compact(
            'tarea', 'mySubmissions', 'latestSubmission',
            'childrenSubmissions', 'allSubmissions'
        ));
    }

    public function submit(Request $request, Task $tarea)
    {
        $user = auth()->user();
        abort_unless($user->isStudent(), 403);
        abort_if($tarea->isExpired(), 422, 'La fecha límite de esta tarea ya venció.');

        $sectionId = Enrollment::where('student_id', $user->id)
            ->join('sections', 'enrollments.section_id', '=', 'sections.id')
            ->orderByDesc('sections.year')
            ->value('enrollments.section_id');
        $allowed = $sectionId
            ? Course::where('section_id', $sectionId)->where('id', $tarea->course_id)->exists()
            : false;
        abort_unless($allowed, 403);

        $attempts = $tarea->submissions()->where('student_id', $user->id)->count();
        if ($attempts >= $tarea->max_attempts) {
            return back()->withErrors(['file' => 'Ya alcanzaste el número máximo de intentos para esta tarea.']);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480',
        ]);

        $file         = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $filePath     = $file->store('tareas/entregas', 'public');

        TaskSubmission::create([
            'task_id'      => $tarea->id,
            'student_id'   => $user->id,
            'file_path'    => $filePath,
            'original_name'=> $originalName,
            'attempt'      => $attempts + 1,
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Entrega registrada correctamente.');
    }

    public function grade(Request $request, Task $tarea, TaskSubmission $submission)
    {
        $user = auth()->user();
        abort_unless($user->isTeacher() || $user->isAdmin(), 403);

        if ($user->isTeacher()) {
            abort_unless($tarea->teacher_id === $user->id, 403);
        }

        $data = $request->validate([
            'grade'       => 'nullable|numeric|min:0|max:20',
            'teacher_note'=> 'nullable|string|max:300',
        ]);

        $submission->update([
            'grade'        => $data['grade'] ?? null,
            'teacher_note' => $data['teacher_note'] ?? null,
        ]);

        return back()->with('success', 'Nota guardada.');
    }

    public function destroy(Task $tarea)
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->isTeacher(), 403);
        if ($user->isTeacher()) {
            abort_unless($tarea->teacher_id === $user->id, 403);
        }

        // Eliminar archivos del disco
        if ($tarea->file_path) {
            Storage::disk('public')->delete($tarea->file_path);
        }
        foreach ($tarea->submissions as $sub) {
            Storage::disk('public')->delete($sub->file_path);
        }

        $tarea->delete();
        return redirect()->route('tareas.index')->with('success', 'Tarea eliminada.');
    }
}
