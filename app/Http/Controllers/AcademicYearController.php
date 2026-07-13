<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::orderByDesc('year')->get();

        // Conteo de secciones por año para mostrarlo en la tabla
        $sectionCounts = Section::selectRaw('year, COUNT(*) as total')
            ->groupBy('year')
            ->pluck('total', 'year');

        // Años que ya tienen secciones pero aún no tienen registro de año escolar
        $existingYears = Section::select('year')->distinct()->pluck('year');
        $missingYears  = $existingYears->diff($academicYears->pluck('year'))->sortDesc()->values();

        return view('anios.index', compact('academicYears', 'missingYears', 'sectionCounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'year'             => ['required', 'integer', 'min:2000', 'max:2100', 'unique:academic_years,year'],
            'default_capacity' => ['required', 'integer', 'min:1', 'max:200'],
        ], [
            'year.required'             => 'El año es obligatorio.',
            'year.unique'               => 'Ese año escolar ya existe.',
            'default_capacity.required' => 'El cupo máximo por sección es obligatorio.',
        ]);

        AcademicYear::create([
            'year'             => $data['year'],
            'default_capacity' => $data['default_capacity'],
            'status'           => AcademicYear::STATUS_PLANNING,
        ]);

        return redirect()->route('anios.index')
                         ->with('success', "Año escolar {$data['year']} creado en estado Planificación.");
    }

    public function openEnrollment(AcademicYear $anio)
    {
        if ($anio->isFinished()) {
            return redirect()->route('anios.index')
                             ->with('error', "El año {$anio->year} ya está finalizado y no se puede reabrir.");
        }

        $anio->update([
            'status'               => AcademicYear::STATUS_ENROLLMENT_OPEN,
            'enrollment_opened_at' => now(),
        ]);

        return redirect()->route('anios.index')
                         ->with('success', "Matrícula habilitada para el año {$anio->year}.");
    }

    public function closeEnrollment(AcademicYear $anio)
    {
        $anio->update(['status' => AcademicYear::STATUS_PLANNING]);

        return redirect()->route('anios.index')
                         ->with('success', "El año {$anio->year} volvió a estado Planificación.");
    }

    public function finish(AcademicYear $anio)
    {
        // Verificar que no queden alumnos sin resultado si hay matrículas
        $sinResultado = Enrollment::whereHas('section', fn($q) => $q->where('year', $anio->year))
            ->whereNull('result')
            ->count();

        if ($sinResultado > 0) {
            return redirect()->route('anios.index')
                ->with('error', "No se puede finalizar el año {$anio->year}: {$sinResultado} alumno(s) aún no tienen resultado registrado. Ve a Promoción para registrar los resultados.");
        }

        $anio->update(['status' => AcademicYear::STATUS_FINISHED]);

        return redirect()->route('anios.index')
                         ->with('success', "El año escolar {$anio->year} ha sido marcado como Finalizado.");
    }
}
