<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Course;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\AcademicYear;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\StoreCourseRequest;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $availableYears = Section::select('year')->distinct()->orderByDesc('year')->pluck('year');
        $selectedYear   = $request->filled('year') ? (int) $request->year : $availableYears->first();

        $query = Section::withCount(['courses', 'students'])->orderBy('grade')->orderBy('name');
        if ($selectedYear) {
            $query->where('year', $selectedYear);
        }
        $sections = $query->get();

        // Badge de estado por año
        $yearStatuses = AcademicYear::whereIn('year', $availableYears)->get()->keyBy('year');

        return view('secciones.index', compact('sections', 'availableYears', 'selectedYear', 'yearStatuses'));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'exists:academic_years,year'],
        ]);

        $academicYear = AcademicYear::where('year', $data['year'])->first();

        if ($academicYear->isFinished()) {
            return redirect()->route('anios.index')
                ->with('error', "El año {$data['year']} está finalizado; no se pueden generar secciones.");
        }
        if ($academicYear->isEnrollmentOpen()) {
            return redirect()->route('anios.index')
                ->with('error', "El año {$data['year']} ya tiene la matrícula habilitada; genera secciones solo mientras está en Planificación.");
        }

        $cupo    = $academicYear->default_capacity ?? 30;
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        $created = 0;

        foreach (range(1, 5) as $grade) {
            foreach ($letters as $index => $letter) {
                $turno = $index < 5 ? 'mañana' : 'tarde';

                $exists = Section::where('name', $letter)
                                   ->where('grade', $grade)
                                   ->where('year', $data['year'])
                                   ->exists();
                if ($exists) {
                    continue;
                }

                Section::create([
                    'name'        => $letter,
                    'grade'       => $grade,
                    'year'        => $data['year'],
                    'turno'       => $turno,
                    'cupo_maximo' => $cupo,
                ]);
                $created++;
            }
        }

        $msg = $created > 0
            ? "Se generaron {$created} secciones para {$data['year']} (cupo {$cupo} por sección, 5 mañana + 5 tarde por grado)."
            : "El año {$data['year']} ya tenía todas las secciones generadas. No se crearon nuevas.";

        return redirect()->route('anios.index')->with('success', $msg);
    }
    public function edit(Section $seccione)
    {
        $enrolledCount = Enrollment::where('section_id', $seccione->id)->count();
        $academicYear  = AcademicYear::where('year', $seccione->year)->first();
        return view('secciones.edit', compact('seccione', 'enrolledCount', 'academicYear'));
    }

    public function update(StoreSectionRequest $request, Section $seccione)
    {
        $academicYear  = AcademicYear::where('year', $seccione->year)->first();
        $enrolledCount = Enrollment::where('section_id', $seccione->id)->count();
        $data          = $request->validated();

        if ($academicYear?->isFinished()) {
            return redirect()->route('secciones.index')
                ->with('error', "El año {$seccione->year} está finalizado. No se puede editar la sección {$seccione->grade}° {$seccione->name}.");
        }

        if ($enrolledCount > 0) {
            $cambiaClave = $data['year'] != $seccione->year
                        || (int)$data['grade'] !== (int)$seccione->grade
                        || $data['name'] !== $seccione->name;
            if ($cambiaClave) {
                return redirect()->route('secciones.edit', $seccione)
                    ->withInput()
                    ->withErrors(['name' => "Hay {$enrolledCount} alumno(s) matriculado(s): no se puede cambiar el año, grado ni nombre. Solo puedes ajustar el cupo o el turno."]);
            }
        }

        $seccione->update($data);

        return redirect()->route('secciones.index')
                         ->with('success', 'Sección actualizada correctamente.');
    }

    public function destroy(Section $seccione)
    {
        $enrolledCount = Enrollment::where('section_id', $seccione->id)->count();
        if ($enrolledCount > 0) {
            return redirect()->route('secciones.index')
                ->with('error', "No se puede eliminar {$seccione->grade}° {$seccione->name}: tiene {$enrolledCount} alumno(s) matriculado(s). Retira las matrículas antes de eliminar.");
        }

        $seccione->delete();

        return redirect()->route('secciones.index')
                         ->with('success', "Sección {$seccione->grade}° {$seccione->name} eliminada correctamente.");
    }

    public function courses(Section $seccione)
    {
        $seccione->load('courses.teacher');
        $teachers = User::with('teacherProfile')->where('role', 'teacher')->orderBy('name')->get()
            ->map(function ($t) use ($seccione) {
                $t->carga_actual = Course::where('teacher_id', $t->id)
                    ->whereHas('section', fn ($q) => $q->where('year', $seccione->year))
                    ->sum('hours_per_week');
                $t->carga_maxima = $t->teacherProfile->max_horas_semanales ?? 30;
                $t->turno_docente = $t->teacherProfile->turno ?? null;
                return $t;
            });

        return view('secciones.courses', compact('seccione', 'teachers'));
    }

    public function storeCourse(StoreCourseRequest $request, Section $seccione)
    {
        if ($request->filled('teacher_id')) {
            $error = $this->validateTeacherAssignment($request->teacher_id, $seccione, $request->hours_per_week ?? 4);
            if ($error) {
                return back()->withInput()->withErrors(['teacher_id' => $error]);
            }
        }

        Course::create([
            'name'           => $request->name,
            'section_id'     => $seccione->id,
            'teacher_id'     => $request->teacher_id ?: null,
            'hours_per_week' => $request->hours_per_week ?? 4,
        ]);

        return redirect()->route('secciones.courses', $seccione)
                         ->with('success', 'Curso agregado correctamente.');
    }

    public function destroyCourse(Section $seccione, Course $curso)
    {
        $curso->delete();

        return redirect()->route('secciones.courses', $seccione)
                         ->with('success', 'Curso eliminado correctamente.');
    }

    public function updateCourse(Request $request, Section $seccione, Course $curso)
    {
        $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'teacher_id'     => ['nullable', 'exists:users,id,role,teacher'],
            'hours_per_week' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        if ($request->filled('teacher_id')) {
            // Calcular horas excluyendo las actuales del curso para no contarlas doble
            $horasActuales = Course::where('teacher_id', $request->teacher_id)
                ->whereHas('section', fn($q) => $q->where('year', $seccione->year))
                ->where('id', '!=', $curso->id)
                ->sum('hours_per_week');

            $teacher = User::with('teacherProfile')->find($request->teacher_id);
            $maxHoras = $teacher->teacherProfile->max_horas_semanales ?? 30;
            $turnoDocente = $teacher->teacherProfile->turno ?? null;

            if ($turnoDocente && $turnoDocente !== 'ambos' && $turnoDocente !== $seccione->turno) {
                return back()->withInput()
                    ->with('error', "{$teacher->name} solo dicta en turno " . ucfirst($turnoDocente) . ".");
            }
            if (($horasActuales + $request->hours_per_week) > $maxHoras) {
                return back()->withInput()
                    ->with('error', "{$teacher->name} superaría su tope de {$maxHoras}h/semana.");
            }
        }

        $curso->update([
            'name'           => $request->name,
            'teacher_id'     => $request->teacher_id ?: null,
            'hours_per_week' => $request->hours_per_week,
        ]);

        return redirect()->route('secciones.courses', $seccione)
                         ->with('success', 'Curso actualizado correctamente.');
    }

    private function validateTeacherAssignment(int $teacherId, Section $seccione, int $hoursRequested): ?string
    {
        $teacher = User::with('teacherProfile')->find($teacherId);
        $profile = $teacher?->teacherProfile;

        $turnoDocente = $profile->turno ?? null;
        if ($turnoDocente && $turnoDocente !== 'ambos' && $turnoDocente !== $seccione->turno) {
            return "{$teacher->name} solo dicta en turno " . ucfirst($turnoDocente) . ", pero la sección es de turno " . ucfirst($seccione->turno) . ".";
        }

        $maxHoras = $profile->max_horas_semanales ?? 30;
        $horasActuales = Course::where('teacher_id', $teacherId)
            ->whereHas('section', fn ($q) => $q->where('year', $seccione->year))
            ->sum('hours_per_week');

        if (($horasActuales + $hoursRequested) > $maxHoras) {
            return "{$teacher->name} ya tiene {$horasActuales}h/semana asignadas; agregar {$hoursRequested}h superaría su tope de {$maxHoras}h/semana.";
        }

        return null;
    }

    public function enroll(Request $request, Section $seccione)
    {
        $request->validate(['student_id' => 'required|exists:users,id,role,student']);

        if (!\App\Models\AcademicYear::isYearEnrollmentOpen($seccione->year)) {
            return back()->with('error', "La matrícula para el año {$seccione->year} no está habilitada.");
        }

        $ocupados = Enrollment::where('section_id', $seccione->id)->count();
        if ($seccione->cupo_maximo && $ocupados >= $seccione->cupo_maximo
            && !Enrollment::where('section_id', $seccione->id)->where('student_id', $request->student_id)->exists()) {
            return back()->with('error', "La sección {$seccione->name} ya alcanzó su cupo máximo ({$seccione->cupo_maximo}).");
        }

        // Remove any existing enrollment for this student in the same year (preserving history from other years)
        Enrollment::where('student_id', $request->student_id)
            ->whereHas('section', fn ($q) => $q->where('year', $seccione->year))
            ->delete();

        Enrollment::create([
            'student_id' => $request->student_id,
            'section_id' => $seccione->id,
            'year'       => $seccione->year,
        ]);

        return redirect()->back()->with('success', 'Alumno matriculado correctamente.');
    }
}
