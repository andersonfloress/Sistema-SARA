<?php

namespace App\Http\Controllers;

use App\Models\TeacherProfile;
use App\Models\Grade;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $user          = auth()->user();
        $teacherProfile = null;
        $coursesByYear  = collect();
        $currentYear    = null;
        $studentProfile = null;
        $studentEnrollments = collect();
        $studentParents     = collect();
        $parentProfile      = null;
        $parentChildren     = collect();

        if ($user->isTeacher()) {
            $teacherProfile = $user->teacherProfile
                ?? new TeacherProfile(['teacher_id' => $user->id]);

            $coursesByYear = $user->courses()
                ->with('section')
                ->orderBy('name')
                ->get()
                ->toBase()
                ->groupBy(fn($c) => $c->section?->year ?? '—')
                ->sortKeysDesc();

            $currentYear = now()->year;
        }

        if ($user->isStudent()) {
            $studentProfile = $user->studentProfile;

            $studentEnrollments = $user->enrollments()
                ->with(['section.courses.teacher'])
                ->orderByDesc('created_at')
                ->get();

            $studentParents = $user->parents()
                ->with('parentProfile')
                ->get();
        }

        if ($user->isParent()) {
            $parentProfile  = $user->parentProfile;
            $parentChildren = $user->children()
                ->with('enrollments.section.courses', 'studentProfile')
                ->get()
                ->map(function ($child) {
                    $grades  = Grade::where('student_id', $child->id)->get();
                    $scores  = $grades->map(fn($g) => (float) $g->score)->filter(fn($v) => !is_nan($v));
                    $avgGrade = $scores->count() > 0 ? round($scores->avg(), 1) : null;

                    $atts      = Attendance::where('student_id', $child->id)->get();
                    $total     = $atts->count();
                    $effective = $atts->whereIn('status', ['present', 'justified'])->count();
                    $absent    = $atts->where('status', 'absent')->count();
                    $attPct    = $total > 0 ? round(($effective / $total) * 100) : 100;
                    $absentPct = $total > 0 ? round(($absent / $total) * 100) : 0;

                    return [
                        'student'   => $child,
                        'section'   => $child->enrollments->first()?->section,
                        'avgGrade'  => $avgGrade,
                        'attPct'    => $attPct,
                        'atRisk'    => ($avgGrade !== null && $avgGrade < 11) || $absentPct > 30,
                        'parentesco'=> $child->pivot?->parentesco,
                    ];
                });
        }

        return view('perfil.edit', compact(
            'teacherProfile', 'coursesByYear', 'currentYear',
            'studentProfile', 'studentEnrollments', 'studentParents',
            'parentProfile', 'parentChildren'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
        ]);

        auth()->user()->update(['name' => $request->name]);

        return redirect()->route('perfil.edit')
                         ->with('success', 'Nombre actualizado correctamente.');
    }

    /**
     * Self-service update of low-risk contact fields (phone, address, photo).
     * Official/administrative fields (DNI, código, especialidad, condición laboral, etc.)
     * remain admin-only via TeacherController::updateProfile.
     */
    public function updateContactInfo(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isTeacher(), 403);

        $data = $request->validate([
            'telefono'    => ['nullable', 'string', 'max:20'],
            'direccion'   => ['nullable', 'string', 'max:500'],
            'foto_perfil' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
        ]);

        $profile = $user->teacherProfile;
        $oldFoto = $profile?->foto_perfil;

        if ($request->hasFile('foto_perfil')) {
            // Store the new file first; only delete the old one once the new one is safely on disk.
            $data['foto_perfil'] = $request->file('foto_perfil')->store('docentes/fotos', 'public');
            if ($oldFoto) {
                Storage::disk('public')->delete($oldFoto);
            }
        } else {
            unset($data['foto_perfil']);
        }

        TeacherProfile::updateOrCreate(['teacher_id' => $user->id], $data);

        return redirect()->route('perfil.edit')
                         ->with('success', 'Datos de contacto actualizados correctamente.');
    }

    /** Página completa de edición de perfil — solo para docentes. */
    public function editFull()
    {
        $user = auth()->user();
        abort_unless($user->isTeacher(), 403);

        $teacherProfile = $user->teacherProfile
            ?? new TeacherProfile(['teacher_id' => $user->id]);

        return view('perfil.editar', compact('teacherProfile'));
    }

    /** Guarda todos los campos que el docente puede modificar por sí mismo. */
    public function updateFull(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isTeacher(), 403);

        $data = $request->validate([
            'telefono'                     => ['nullable', 'string', 'max:20'],
            'direccion'                    => ['nullable', 'string', 'max:500'],
            'foto_perfil'                  => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
            'correo_alternativo'           => ['nullable', 'email', 'max:150'],
            'contacto_emergencia_nombre'   => ['nullable', 'string', 'max:150'],
            'contacto_emergencia_telefono' => ['nullable', 'string', 'max:20'],
            'especialidad'                 => ['nullable', 'string', 'max:100'],
            'nivel_academico'              => ['nullable', 'in:bachiller,licenciado,magister,doctor'],
            'numero_colegiatura'           => ['nullable', 'string', 'max:30'],
            'cv_file'                      => ['nullable', 'file', 'mimes:pdf', 'max:4096'],
        ]);

        $profile = $user->teacherProfile;

        if ($request->hasFile('foto_perfil')) {
            if ($profile?->foto_perfil) {
                Storage::disk('public')->delete($profile->foto_perfil);
            }
            $data['foto_perfil'] = $request->file('foto_perfil')->store('docentes/fotos', 'public');
        } else {
            unset($data['foto_perfil']);
        }

        if ($request->hasFile('cv_file')) {
            if ($profile?->cv_path) {
                Storage::disk('public')->delete($profile->cv_path);
            }
            $data['cv_path'] = $request->file('cv_file')->store('docentes/cv', 'public');
        }
        unset($data['cv_file']);

        TeacherProfile::updateOrCreate(['teacher_id' => $user->id], $data);

        return redirect()->route('perfil.edit')
                         ->with('success', 'Perfil actualizado correctamente.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'password.required'         => 'La nueva contraseña es obligatoria.',
            'password.min'              => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'        => 'La confirmación no coincide.',
        ]);

        if (!Hash::check($request->current_password, auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta.']);
        }

        auth()->user()->update(['password' => Hash::make($request->password)]);

        return redirect()->route('perfil.edit')
                         ->with('success', 'Contraseña actualizada correctamente.');
    }
}
