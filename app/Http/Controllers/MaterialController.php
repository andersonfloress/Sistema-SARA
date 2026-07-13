<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Course;
use App\Models\Enrollment;
use App\Http\Requests\StoreMaterialRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Material::with('course.section', 'teacher');

        $isStudentOrParent = $user->isStudent() || $user->isParent();

        // Courses this user is allowed to see materials for (null = no restriction, i.e. admin).
        $allowedCourseIds = null;
        $children         = collect();
        $selectedChild    = null;

        if ($user->isTeacher()) {
            $allowedCourseIds = Course::where('teacher_id', $user->id)->pluck('id');
        } elseif ($user->isStudent()) {
            $sectionId = Enrollment::where('enrollments.student_id', $user->id)
                ->join('sections', 'enrollments.section_id', '=', 'sections.id')
                ->orderByDesc('sections.year')
                ->value('enrollments.section_id');
            $allowedCourseIds = $sectionId
                ? Course::where('section_id', $sectionId)->pluck('id')
                : collect();
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
                $allowedCourseIds = $sectionId
                    ? Course::where('section_id', $sectionId)->pluck('id')
                    : collect();
            } else {
                $allowedCourseIds = collect();
            }
        }
        // admin: $allowedCourseIds stays null (no restriction)

        if ($allowedCourseIds !== null) {
            $query->whereIn('course_id', $allowedCourseIds);
        }

        if ($request->filled('course_id')) {
            if ($allowedCourseIds !== null && !$allowedCourseIds->contains((int) $request->course_id)) {
                abort(403, 'No tienes acceso a los materiales de ese curso.');
            }
            $query->where('course_id', $request->course_id);
        }

        // Alumnos y padres: cargar todo (sin paginación) para que Alpine.js filtre en cliente.
        // Admin y docentes: paginar normalmente.
        $materials = $isStudentOrParent
            ? $query->latest()->paginate(500)->withQueryString()
            : $query->latest()->paginate(15)->withQueryString();

        if ($user->isAdmin()) {
            $courses = Course::with('section')->orderBy('name')->get();
        } elseif ($user->isTeacher()) {
            $courses = Course::with('section')->where('teacher_id', $user->id)->orderBy('name')->get();
        } elseif ($allowedCourseIds !== null && $allowedCourseIds->isNotEmpty()) {
            // Cubre tanto isStudent() como isParent() — ambos ya tienen $allowedCourseIds resuelto
            $courses = Course::with('section')->whereIn('id', $allowedCourseIds)->orderBy('name')->get();
        } else {
            $courses = collect();
        }

        return view('materiales.index', compact('materials', 'courses', 'children', 'selectedChild'));
    }

    public function create()
    {
        $user = auth()->user();

        $courses = $user->isAdmin()
            ? Course::with('section')->orderBy('name')->get()
            : Course::with('section')->where('teacher_id', $user->id)->orderBy('name')->get();

        return view('materiales.create', compact('courses'));
    }

    public function store(StoreMaterialRequest $request)
    {
        $user = auth()->user();

        // A teacher may only publish materials for their own courses.
        if ($user->isTeacher()) {
            $owns = Course::where('id', $request->course_id)->where('teacher_id', $user->id)->exists();
            if (!$owns) {
                abort(403, 'No puedes publicar material para un curso que no dictas.');
            }
        }

        $url = $request->url;

        if ($request->type === 'document' && $request->hasFile('file')) {
            $path = $request->file('file')->store('materiales', 'public');
            $url  = Storage::url($path);
        }

        Material::create([
            'title'       => $request->title,
            'type'        => $request->type,
            'url'         => $url,
            'description' => $request->description,
            'course_id'   => $request->course_id,
            'teacher_id'  => $user->id,
        ]);

        return redirect()->route('materiales.index')
                         ->with('success', 'Material publicado correctamente.');
    }

    public function destroy(Material $material)
    {
        $user = auth()->user();

        if (!$user->isAdmin() && $material->teacher_id !== $user->id) {
            abort(403);
        }

        // Si es un documento subido, eliminar también el archivo físico del disco
        // para evitar acumulación de archivos huérfanos en storage/app/public/materiales/
        if ($material->type === 'document' && $material->url) {
            // Storage::url() devuelve "/storage/materiales/archivo.pdf"
            // Quitamos el prefijo "/storage/" para obtener la ruta relativa al disco 'public'
            $storagePath = ltrim(str_replace('/storage/', '', $material->url), '/');
            Storage::disk('public')->delete($storagePath);
        }

        $material->delete();

        return redirect()->route('materiales.index')
                         ->with('success', 'Material eliminado correctamente.');
    }
}
