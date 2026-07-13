<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TeacherProfile;
use App\Http\Requests\UpdateTeacherProfileRequest;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $especialidades = TeacherProfile::whereNotNull('especialidad')
                            ->distinct()
                            ->orderBy('especialidad')
                            ->pluck('especialidad');

        $query = User::where('role', 'teacher')
                     ->with('teacherProfile', 'courses.section')
                     ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhereHas('teacherProfile', fn($qp) =>
                      $qp->where('dni', 'like', '%' . $search . '%')
                  );
            });
        }

        if ($request->filled('especialidad')) {
            $query->whereHas('teacherProfile', fn($q) =>
                $q->where('especialidad', $request->especialidad)
            );
        }

        $teachers = $query->paginate(20)->withQueryString();

        return view('docentes.index', compact('teachers', 'especialidades'));
    }

    public function show(User $docente)
    {
        abort_unless($docente->role === 'teacher', 404);

        $docente->load('teacherProfile', 'courses.section', 'courses.scheduleSlots');

        return view('docentes.show', compact('docente'));
    }

    public function editProfile(User $docente)
    {
        abort_unless($docente->role === 'teacher', 404);

        $profile = $docente->teacherProfile ?? new TeacherProfile(['teacher_id' => $docente->id]);

        return view('docentes.edit-profile', compact('docente', 'profile'));
    }

    public function updateProfile(UpdateTeacherProfileRequest $request, User $docente)
    {
        abort_unless($docente->role === 'teacher', 404);

        $data = $request->validated();
        unset($data['cv_file'], $data['foto_perfil']);

        $profile = $docente->teacherProfile;

        if ($request->hasFile('foto_perfil')) {
            if ($profile?->foto_perfil) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($profile->foto_perfil);
            }
            $data['foto_perfil'] = $request->file('foto_perfil')->store('docentes/fotos', 'public');
        }

        if ($request->hasFile('cv_file')) {
            if ($profile?->cv_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($profile->cv_path);
            }
            $data['cv_path'] = $request->file('cv_file')->store('docentes/cv', 'public');
        }

        TeacherProfile::updateOrCreate(
            ['teacher_id' => $docente->id],
            $data
        );

        return redirect()->route('docentes.show', $docente)
                         ->with('success', 'Perfil del docente actualizado.');
    }
}
