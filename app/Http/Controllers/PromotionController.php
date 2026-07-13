<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    /** Nota mínima aprobatoria (escala vigesimal peruana) */
    const PASSING_SCORE = 11;

    // ── Vista principal ───────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $years    = Section::select('year')->distinct()->orderByDesc('year')->pluck('year');
        $fromYear = $request->filled('from_year') ? (int) $request->from_year : $years->first();
        $selectedSectionId = $request->filled('section_id') ? (int) $request->section_id : null;

        $sections      = collect();
        $enrollments   = collect();
        $stats         = null;
        $selectedSection = null;
        $academicYear  = null;

        if ($fromYear) {
            $academicYear = AcademicYear::where('year', $fromYear)->first();

            // Secciones con conteo de resultados registrados
            $sections = Section::where('year', $fromYear)
                ->orderBy('grade')->orderBy('name')
                ->get()
                ->map(function ($section) {
                    $total      = Enrollment::where('section_id', $section->id)->count();
                    $withResult = Enrollment::where('section_id', $section->id)->whereNotNull('result')->count();
                    $section->total_students = $total;
                    $section->with_result    = $withResult;
                    $section->is_complete    = $total > 0 && $total === $withResult;
                    return $section;
                });

            // Estadísticas globales del año
            $totalEnrollments = $sections->sum('total_students');
            $totalWithResult  = $sections->sum('with_result');

            $approved  = Enrollment::whereHas('section', fn($q) => $q->where('year', $fromYear))
                ->where('result', Enrollment::RESULT_APPROVED)->count();
            $graduated = Enrollment::whereHas('section', fn($q) => $q->where('year', $fromYear))
                ->where('result', Enrollment::RESULT_GRADUATED)->count();
            $retained  = Enrollment::whereHas('section', fn($q) => $q->where('year', $fromYear))
                ->where('result', Enrollment::RESULT_RETAINED)->count();

            $stats = [
                'total'       => $totalEnrollments,
                'with_result' => $totalWithResult,
                'approved'    => $approved,
                'graduated'   => $graduated,
                'retained'    => $retained,
                'pending'     => $totalEnrollments - $totalWithResult,
            ];

            // Alumnos de la sección seleccionada (con promedio de notas)
            if ($selectedSectionId) {
                $selectedSection = $sections->firstWhere('id', $selectedSectionId);

                if ($selectedSection) {
                    $courseIds = Course::where('section_id', $selectedSectionId)->pluck('id');

                    $enrollments = Enrollment::where('section_id', $selectedSectionId)
                        ->with(['student.studentProfile'])
                        ->get()
                        ->map(function ($enrollment) use ($courseIds) {
                            $avg = $courseIds->isNotEmpty()
                                ? Grade::where('student_id', $enrollment->student_id)
                                    ->whereIn('course_id', $courseIds)
                                    ->avg('score')
                                : null;
                            $enrollment->avg_score = $avg !== null ? round((float) $avg, 1) : null;
                            return $enrollment;
                        })
                        ->sortBy('student.name')
                        ->values();
                }
            }
        }

        return view('promocion.index', compact(
            'years', 'fromYear', 'sections', 'selectedSectionId',
            'selectedSection', 'enrollments', 'stats', 'academicYear'
        ));
    }

    // ── Registrar resultado individual ────────────────────────────────────────

    public function setResult(Request $request)
    {
        $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'result'        => ['required', 'in:approved,retained'],
        ]);

        $enrollment   = Enrollment::with('section')->findOrFail($request->enrollment_id);
        $academicYear = AcademicYear::where('year', $enrollment->section->year)->first();

        if ($academicYear?->isFinished()) {
            return back()->with('error', "El año {$enrollment->section->year} ya está finalizado. No se pueden modificar resultados.");
        }

        // Si el alumno está en 5° y se le marca Aprobado → automáticamente Egresado
        $result = $request->result;
        if ($result === Enrollment::RESULT_APPROVED && $enrollment->section->grade >= 5) {
            $result = Enrollment::RESULT_GRADUATED;
        }

        $enrollment->update(['result' => $result]);

        $fromYear  = $enrollment->section->year;
        $sectionId = $enrollment->section_id;

        return redirect()
            ->route('promocion.index', ['from_year' => $fromYear, 'section_id' => $sectionId])
            ->with('success', "Resultado de {$enrollment->student->name} actualizado a: " . $enrollment->fresh()->resultLabel() . ".");
    }

    // ── Marcar toda una sección de golpe ──────────────────────────────────────

    public function setBulkResult(Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'result'     => ['required', 'in:approved,retained'],
        ]);

        $section      = Section::findOrFail($request->section_id);
        $academicYear = AcademicYear::where('year', $section->year)->first();

        if ($academicYear?->isFinished()) {
            return back()->with('error', "El año {$section->year} ya está finalizado. No se pueden modificar resultados.");
        }

        $result  = $request->result;

        // En 5° grado "aprobado" significa "egresado"
        if ($result === Enrollment::RESULT_APPROVED && $section->grade >= 5) {
            $result = Enrollment::RESULT_GRADUATED;
        }

        Enrollment::where('section_id', $section->id)->update(['result' => $result]);

        $label = $request->result === 'approved'
            ? ($section->grade >= 5 ? 'Egresados' : 'Aprobados')
            : 'Repitentes';

        return redirect()
            ->route('promocion.index', ['from_year' => $section->year, 'section_id' => $section->id])
            ->with('success', "Todos los alumnos de {$section->grade}° {$section->name} marcados como {$label}.");
    }

    // ── Auto-calcular resultado de una sección según promedio de notas ────────

    public function autoCalculateSection(Request $request)
    {
        $request->validate(['section_id' => 'required|exists:sections,id']);

        $section      = Section::findOrFail($request->section_id);
        $academicYear = AcademicYear::where('year', $section->year)->first();

        if ($academicYear?->isFinished()) {
            return back()->with('error', "El año {$section->year} ya está finalizado. No se pueden modificar resultados.");
        }

        $courseIds = Course::where('section_id', $section->id)->pluck('id');

        $enrollments = Enrollment::where('section_id', $section->id)->get();

        $set     = 0;
        $skipped = 0;

        foreach ($enrollments as $enrollment) {
            if ($courseIds->isEmpty()) {
                $skipped++;
                continue;
            }

            $avg = Grade::where('student_id', $enrollment->student_id)
                ->whereIn('course_id', $courseIds)
                ->avg('score');

            if ($avg === null) {
                $skipped++;
                continue;
            }

            $result = (float) $avg >= self::PASSING_SCORE
                ? Enrollment::RESULT_APPROVED
                : Enrollment::RESULT_RETAINED;

            if ($result === Enrollment::RESULT_APPROVED && $section->grade >= 5) {
                $result = Enrollment::RESULT_GRADUATED;
            }

            $enrollment->update(['result' => $result]);
            $set++;
        }

        $msg = "Auto-cálculo completado para {$section->grade}° {$section->name}: "
             . "{$set} resultado(s) asignados (mínimo aprobatorio: " . self::PASSING_SCORE . "/20)";
        if ($skipped > 0) {
            $msg .= " — {$skipped} alumno(s) sin notas quedaron sin resultado";
        }
        $msg .= ".";

        return redirect()
            ->route('promocion.index', ['from_year' => $section->year, 'section_id' => $section->id])
            ->with('success', $msg);
    }

    // ── Auto-calcular resultados de TODAS las secciones del año ───────────────

    public function autoCalculateYear(Request $request)
    {
        $request->validate(['from_year' => 'required|integer|min:2000|max:2100']);

        $fromYear     = (int) $request->from_year;
        $academicYear = AcademicYear::where('year', $fromYear)->first();

        if ($academicYear?->isFinished()) {
            return back()->with('error', "El año {$fromYear} ya está finalizado. No se pueden modificar resultados.");
        }

        $sections = Section::where('year', $fromYear)->get();

        $totalSet     = 0;
        $totalSkipped = 0;

        foreach ($sections as $section) {
            $courseIds   = Course::where('section_id', $section->id)->pluck('id');
            $enrollments = Enrollment::where('section_id', $section->id)->get();

            foreach ($enrollments as $enrollment) {
                if ($courseIds->isEmpty()) {
                    $totalSkipped++;
                    continue;
                }

                $avg = Grade::where('student_id', $enrollment->student_id)
                    ->whereIn('course_id', $courseIds)
                    ->avg('score');

                if ($avg === null) {
                    $totalSkipped++;
                    continue;
                }

                $result = (float) $avg >= self::PASSING_SCORE
                    ? Enrollment::RESULT_APPROVED
                    : Enrollment::RESULT_RETAINED;

                if ($result === Enrollment::RESULT_APPROVED && $section->grade >= 5) {
                    $result = Enrollment::RESULT_GRADUATED;
                }

                $enrollment->update(['result' => $result]);
                $totalSet++;
            }
        }

        $msg = "Auto-cálculo para {$fromYear} completado: {$totalSet} resultado(s) asignados "
             . "(mínimo aprobatorio: " . self::PASSING_SCORE . "/20)";
        if ($totalSkipped > 0) {
            $msg .= " — {$totalSkipped} alumno(s) sin notas quedaron sin resultado";
        }
        $msg .= ".";

        return redirect()
            ->route('promocion.index', ['from_year' => $fromYear])
            ->with('success', $msg);
    }

    // ── Limpiar resultados de una sección (volver a "sin resultado") ──────────

    public function clearSectionResult(Request $request)
    {
        $request->validate(['section_id' => 'required|exists:sections,id']);

        $section      = Section::findOrFail($request->section_id);
        $academicYear = AcademicYear::where('year', $section->year)->first();

        if ($academicYear?->isFinished()) {
            return back()->with('error', "El año {$section->year} ya está finalizado. No se pueden limpiar resultados.");
        }

        Enrollment::where('section_id', $section->id)->update(['result' => null]);

        return redirect()
            ->route('promocion.index', ['from_year' => $section->year, 'section_id' => $section->id])
            ->with('success', "Resultados de {$section->grade}° {$section->name} limpiados. Vuelven a estado 'Sin resultado'.");
    }

    // ── Ejecutar la promoción ─────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate(['from_year' => 'required|integer|min:2000|max:2100']);
        $fromYear = (int) $request->from_year;
        $toYear   = $fromYear + 1;

        // Verificar que todos tengan resultado registrado
        $pendingCount = Enrollment::whereHas('section', fn($q) => $q->where('year', $fromYear))
            ->whereNull('result')
            ->count();

        if ($pendingCount > 0) {
            return back()->with('error',
                "No se puede ejecutar la promoción: {$pendingCount} alumno(s) aún no tienen resultado registrado. " .
                "Registra los resultados de todas las secciones antes de continuar."
            );
        }

        $sections = Section::where('year', $fromYear)->orderBy('grade')->orderBy('name')->get();

        if ($sections->isEmpty()) {
            return back()->with('error', "No hay secciones registradas para el año {$fromYear}.");
        }

        $promoted        = 0;
        $retained        = 0;
        $graduated       = 0;
        $sectionsCreated = 0;

        DB::transaction(function () use ($sections, $toYear, $fromYear, &$promoted, &$retained, &$graduated, &$sectionsCreated) {
            foreach ($sections as $section) {
                $enrollments = Enrollment::where('section_id', $section->id)->get();

                foreach ($enrollments as $enrollment) {
                    // Repitente: no se crea nueva matrícula — el alumno aparecerá en
                    // la pantalla de matrícula del siguiente año con el mismo grado.
                    if ($enrollment->result === Enrollment::RESULT_RETAINED) {
                        $retained++;
                        continue;
                    }

                    // Egresado (5° aprobado): culmina la secundaria, no recibe nueva matrícula.
                    if ($enrollment->result === Enrollment::RESULT_GRADUATED) {
                        $graduated++;
                        continue;
                    }

                    // Aprobado: crear matrícula en el grado siguiente del año siguiente.
                    $newGrade = $section->grade + 1;
                    if ($newGrade > 5) {
                        // Seguridad: no debería ocurrir (5° ya sería GRADUATED), pero si pasa
                        // no creamos una sección inexistente.
                        $graduated++;
                        continue;
                    }

                    $newSection = Section::firstOrCreate(
                        ['name' => $section->name, 'grade' => $newGrade, 'year' => $toYear],
                        ['turno' => $section->turno, 'cupo_maximo' => $section->cupo_maximo]
                    );
                    if ($newSection->wasRecentlyCreated) {
                        $sectionsCreated++;
                    }

                    // Evitar duplicar si ya existe matrícula en el año destino
                    $alreadyEnrolled = Enrollment::where('student_id', $enrollment->student_id)
                        ->whereHas('section', fn($q) => $q->where('year', $toYear))
                        ->exists();

                    if (!$alreadyEnrolled) {
                        Enrollment::create([
                            'student_id' => $enrollment->student_id,
                            'section_id' => $newSection->id,
                            'year'       => $toYear,
                        ]);
                        $promoted++;
                    }
                }
            }

            // Marcar el año de origen como Finalizado
            AcademicYear::where('year', $fromYear)->update(['status' => AcademicYear::STATUS_FINISHED]);

            // Crear registro del año destino si no existe
            AcademicYear::firstOrCreate(
                ['year' => $toYear],
                ['status' => AcademicYear::STATUS_PLANNING, 'default_capacity' => 30]
            );
        });

        $msg = "Promoción {$fromYear} → {$toYear} completada: " .
               "{$promoted} alumno(s) promovido(s), " .
               "{$retained} repitente(s) (quedan pendientes de matrícula en {$toYear}), " .
               "{$graduated} egresado(s) de 5°. " .
               "{$sectionsCreated} sección(es) nueva(s) creada(s). " .
               "El año {$fromYear} quedó marcado como Finalizado.";

        return redirect()->route('promocion.index', ['from_year' => $fromYear])
            ->with('success', $msg);
    }
}
