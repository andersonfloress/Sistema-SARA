<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Section;
use App\Models\Enrollment;
use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;

class ApiStatsController extends Controller
{
    public function stats(): JsonResponse
    {
        $currentYear = AcademicYear::current();
        $year = $currentYear?->year ?? date('Y');

        $totalStudents  = User::where('role', 'student')->count();
        $totalTeachers  = User::where('role', 'teacher')->count();
        $totalAdmins    = User::where('role', 'admin')->count();
        $totalParents   = User::where('role', 'parent')->count();
        $totalSections  = Section::where('year', $year)->count();
        $enrolledNow    = Enrollment::where('year', $year)->count();

        return response()->json([
            'institucion'    => 'IE Santa Rosa - Puno',
            'año_academico'  => $year,
            'estado_año'     => $currentYear?->status ?? 'sin año activo',
            'totales' => [
                'estudiantes'         => $totalStudents,
                'docentes'            => $totalTeachers,
                'administrativos'     => $totalAdmins,
                'padres_registrados'  => $totalParents,
                'secciones_activas'   => $totalSections,
                'matriculados_año'    => $enrolledNow,
            ],
            'generado_en' => now()->toDateTimeString(),
        ]);
    }

    public function estudiantes(): JsonResponse
    {
        $currentYear = AcademicYear::currentYear();

        $porGrado = Enrollment::where('year', $currentYear)
            ->join('sections', 'enrollments.section_id', '=', 'sections.id')
            ->selectRaw('sections.grade, COUNT(*) as total')
            ->groupBy('sections.grade')
            ->orderBy('sections.grade')
            ->get()
            ->mapWithKeys(fn($row) => ["grado_{$row->grade}" => (int) $row->total]);

        return response()->json([
            'total_estudiantes'    => User::where('role', 'student')->count(),
            'matriculados_año'     => Enrollment::where('year', $currentYear)->count(),
            'año_academico'        => $currentYear,
            'distribucion_por_grado' => $porGrado,
        ]);
    }

    public function docentes(): JsonResponse
    {
        $currentYear = AcademicYear::currentYear();

        $total = User::where('role', 'teacher')->count();

        $conCursos = User::where('role', 'teacher')
            ->whereHas('courses', fn($q) => $q->whereHas('section', fn($q2) => $q2->where('year', $currentYear)))
            ->count();

        return response()->json([
            'total_docentes'       => $total,
            'activos_año'          => $conCursos,
            'sin_cursos_asignados' => $total - $conCursos,
            'año_academico'        => $currentYear,
        ]);
    }
}
