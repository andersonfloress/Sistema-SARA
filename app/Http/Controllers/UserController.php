<?php

namespace App\Http\Controllers;

use App\Models\ParentProfile;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateParentProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['studentProfile', 'teacherProfile', 'parentProfile']);

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->orderBy('name')->paginate(25)->withQueryString();

        $counts = User::selectRaw("role, count(*) as total")
                      ->groupBy('role')
                      ->pluck('total', 'role');

        return view('usuarios.index', compact('users', 'counts'));
    }

    public function create()
    {
        return view('usuarios.create');
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => strtolower($request->email),
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        // ── Crear perfil según el rol ─────────────────────────────────────────
        match ($request->role) {
            'student' => StudentProfile::create(array_filter([
                'student_id'        => $user->id,
                'codigo_estudiante' => $request->codigo_estudiante,
                'dni'               => $request->dni,
                'fecha_nacimiento'  => $request->fecha_nacimiento,
                'sexo'              => $request->sexo,
                'nacionalidad'      => $request->nacionalidad,
                'tipo_sangre'       => $request->tipo_sangre,
                'grado'             => $request->grado,
                'turno'             => $request->turno,
                'anio_ingreso'      => $request->anio_ingreso,
                'direccion'         => $request->p_direccion,
                'telefono'          => $request->p_telefono,
                'nombre_apoderado'  => $request->nombre_apoderado,
                'dni_apoderado'     => $request->dni_apoderado,
                'telefono_emergencia' => $request->telefono_emergencia,
                'condicion_especial'  => $request->condicion_especial,
            ], fn($v) => $v !== null && $v !== '')),

            'parent' => ParentProfile::create(array_filter([
                'parent_id'         => $user->id,
                'dni'               => $request->p_dni,
                'telefono'          => $request->p_telefono,
                'direccion'         => $request->p_direccion,
                'ocupacion'         => $request->ocupacion,
                'grado_instruccion' => $request->grado_instruccion,
            ], fn($v) => $v !== null && $v !== '')),

            'teacher' => TeacherProfile::create(array_filter([
                'teacher_id'   => $user->id,
                'dni'          => $request->t_dni,
                'especialidad' => $request->especialidad,
                'telefono'     => $request->t_telefono,
            ], fn($v) => $v !== null && $v !== '')),

            default => null,
        };

        return redirect()->route('usuarios.index')
                         ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        return view('usuarios.edit', compact('usuario'));
    }

    public function update(UpdateUserRequest $request, User $usuario)
    {
        $data = [
            'name'  => $request->name,
            'email' => strtolower($request->email),
            'role'  => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $usuario->update($data);

        return redirect()->route('usuarios.index')
                         ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')
                         ->with('success', 'Usuario eliminado correctamente.');
    }

    // ── Perfil de padre (edición por admin) ───────────────────────────────────

    public function editParentProfile(User $padre)
    {
        abort_unless($padre->role === 'parent', 404);
        $profile = $padre->parentProfile ?? new ParentProfile(['parent_id' => $padre->id]);
        return view('padres.edit-profile', compact('padre', 'profile'));
    }

    public function updateParentProfile(UpdateParentProfileRequest $request, User $padre)
    {
        abort_unless($padre->role === 'parent', 404);

        ParentProfile::updateOrCreate(
            ['parent_id' => $padre->id],
            $request->validated()
        );

        return redirect()->route('usuarios.index')
                         ->with('success', 'Perfil del padre/madre actualizado correctamente.');
    }
}
