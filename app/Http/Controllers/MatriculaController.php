<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class MatriculaController extends Controller
{
    public function index(Request $request)
    {
        $years = AcademicYear::orderByDesc('year')->get();

        $defaultYear = $years->first()?->year;
        $year        = $request->filled('year') ? (int) $request->year : $defaultYear;

        $academicYear = $years->firstWhere('year', $year);
        $sections     = Section::where('year', $year)
            ->withCount('enrollments')
            ->orderBy('grade')->orderBy('turno')->orderBy('name')
            ->get();

        $enrolledIds = Enrollment::whereHas('section', fn($q) => $q->where('year', $year))
            ->pluck('student_id');

        $students = User::where('role', 'student')
            ->with(['studentProfile', 'enrollments' => function ($q) {
                $q->with('section')->latest('id');
            }])
            ->orderBy('name')
            ->get();

        $pendientes = $students
            ->reject(fn($s) => $enrolledIds->contains($s->id))
            ->map(function ($s) use ($year) {
                // Última matrícula previa (en un año anterior al seleccionado)
                $prevEnrollment = $s->enrollments
                    ->filter(fn($e) => $e->section && $e->section->year < $year)
                    ->sortByDesc(fn($e) => $e->section->year)
                    ->first();

                if ($prevEnrollment) {
                    // Repitente: el resultado del año anterior fue 'retained' → mismo grado
                    // Aprobado / sin resultado aún: sube de grado
                    $isRetained = $prevEnrollment->result === Enrollment::RESULT_RETAINED;

                    $s->targetGrade    = $isRetained
                        ? $prevEnrollment->section->grade
                        : $prevEnrollment->section->grade + 1;
                    $s->isGraduated    = $prevEnrollment->result === Enrollment::RESULT_GRADUATED;
                    $s->isRepitente    = $isRetained;
                    $s->prevSectionLabel = $prevEnrollment->section->grade . '° ' .
                                          $prevEnrollment->section->name .
                                          ' (' . $prevEnrollment->section->year . ')';
                } else {
                    // Alumno nuevo: entra a 1° grado
                    $s->targetGrade      = 1;
                    $s->isGraduated      = false;
                    $s->isRepitente      = false;
                    $s->prevSectionLabel = 'Nuevo ingreso';
                }

                return $s;
            })
            ->reject(fn($s) => $s->isGraduated)           // excluir egresados de 5°
            ->reject(fn($s) => $s->targetGrade > 5)        // excluir si el cálculo supera 5° (seguridad)
            ->values();

        $matriculados = User::where('role', 'student')
            ->whereIn('id', $enrolledIds)
            ->with(['enrollments' => fn($q) => $q
                ->whereHas('section', fn($q2) => $q2->where('year', $year))
                ->with('section')
            ])
            ->orderBy('name')
            ->get();

        return view('matricula.index', compact(
            'years', 'year', 'academicYear', 'sections', 'pendientes', 'matriculados'
        ));
    }

    // ── Formulario admisión de nuevo alumno ───────────────────────────────────

    public function createAdmision()
    {
        // Año con matrícula abierta (o el más reciente)
        $academicYear = AcademicYear::where('status', AcademicYear::STATUS_ENROLLMENT_OPEN)->latest('year')->first()
                     ?? AcademicYear::latest('year')->first();

        $sections = $academicYear
            ? Section::where('year', $academicYear->year)
                ->withCount('enrollments')
                ->orderBy('grade')->orderBy('turno')->orderBy('name')
                ->get()
            : collect();

        // Pre-agrupar por grado para pasarlo como JSON al frontend
        $sectionsByGrade = $sections->groupBy('grade')->map(fn($g) => $g->map(fn($s) => [
            'id'               => $s->id,
            'name'             => $s->name,
            'turno'            => $s->turno ?? '—',
            'enrollments_count'=> $s->enrollments_count,
            'cupo_maximo'      => $s->cupo_maximo,
            'lleno'            => $s->cupo_maximo && $s->enrollments_count >= $s->cupo_maximo,
        ]));

        return view('matricula.admitir', compact('academicYear', 'sections', 'sectionsByGrade'));
    }

    // ── Guardar nuevo alumno y matricularlo en una sección ────────────────────

    public function storeAdmision(Request $request)
    {
        $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'email'              => ['required', 'email', 'unique:users,email'],
            'password'           => ['required', 'string', 'min:8', 'confirmed'],
            'dni'                => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento'   => ['nullable', 'date'],
            'sexo'               => ['nullable', 'in:M,F'],
            'nacionalidad'       => ['nullable', 'string', 'max:80'],
            'nombre_apoderado'   => ['nullable', 'string', 'max:200'],
            'dni_apoderado'      => ['nullable', 'string', 'max:20'],
            'telefono_emergencia'=> ['nullable', 'string', 'max:20'],
            'condicion_especial' => ['nullable', 'string', 'max:1000'],
            'section_id'         => ['nullable', 'exists:sections,id'],
        ], [
            'name.required'      => 'El nombre completo es obligatorio.',
            'email.required'     => 'El correo electrónico es obligatorio.',
            'email.unique'       => 'Este correo ya está registrado.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación no coincide.',
        ]);

        // Verificar que la sección tenga matrícula abierta y cupo disponible
        if ($request->filled('section_id')) {
            $sectionCheck = Section::withCount('enrollments')->findOrFail($request->section_id);

            if (!AcademicYear::isYearEnrollmentOpen($sectionCheck->year)) {
                return back()->withInput()->withErrors([
                    'section_id' => "La matrícula para el año {$sectionCheck->year} no está habilitada. Actívala desde Años Académicos.",
                ]);
            }

            if ($sectionCheck->cupo_maximo && $sectionCheck->enrollments_count >= $sectionCheck->cupo_maximo) {
                return back()->withInput()->withErrors([
                    'section_id' => "La sección {$sectionCheck->grade}° {$sectionCheck->name} ya alcanzó su cupo máximo ({$sectionCheck->cupo_maximo} alumnos).",
                ]);
            }
        }

        DB::transaction(function () use ($request) {
            // 1. Crear usuario
            $user = User::create([
                'name'     => $request->name,
                'email'    => strtolower($request->email),
                'password' => Hash::make($request->password),
                'role'     => 'student',
            ]);

            // 2. Crear perfil del alumno
            $section = $request->filled('section_id')
                ? Section::withCount('enrollments')->find($request->section_id)
                : null;

            StudentProfile::create(array_filter([
                'student_id'          => $user->id,
                'dni'                 => $request->dni,
                'fecha_nacimiento'    => $request->fecha_nacimiento,
                'sexo'                => $request->sexo,
                'nacionalidad'        => $request->nacionalidad ?? 'Peruana',
                'nombre_apoderado'    => $request->nombre_apoderado,
                'dni_apoderado'       => $request->dni_apoderado,
                'telefono_emergencia' => $request->telefono_emergencia,
                'condicion_especial'  => $request->condicion_especial,
                'grado'               => $section?->grade ?? 1,
                'turno'               => $section?->turno,
                'anio_ingreso'        => now()->year,
            ], fn($v) => $v !== null && $v !== ''));

            // 3. Matricular en la sección si fue seleccionada
            if ($section) {
                Enrollment::create([
                    'student_id' => $user->id,
                    'section_id' => $section->id,
                    'year'       => $section->year,
                ]);
            }

            session(['admision_student_name' => $user->name]);
            session(['admision_enrolled'     => $section !== null]);
        });

        $name     = session()->pull('admision_student_name', 'El alumno');
        $enrolled = session()->pull('admision_enrolled', false);

        $msg = $enrolled
            ? "{$name} fue admitido y matriculado correctamente."
            : "{$name} fue admitido. Puedes matricularlo en una sección desde esta pantalla.";

        return redirect()->route('matricula.index')->with('success', $msg);
    }
}
