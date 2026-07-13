<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return auth()->user()?->isAdmin() ?? false; }

    public function rules(): array
    {
        return [
            // ── Cuenta de usuario ─────────────────────────────────────────────
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', 'in:admin,teacher,student,parent'],

            // ── Perfil alumno ─────────────────────────────────────────────────
            'codigo_estudiante'   => ['nullable', 'string', 'max:50'],
            'dni'                 => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento'    => ['nullable', 'date'],
            'sexo'                => ['nullable', 'in:M,F'],
            'nacionalidad'        => ['nullable', 'string', 'max:80'],
            'tipo_sangre'         => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'grado'               => ['nullable', 'integer', 'min:1', 'max:5'],
            'turno'               => ['nullable', 'string', 'max:50'],
            'anio_ingreso'        => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'nombre_apoderado'    => ['nullable', 'string', 'max:200'],
            'dni_apoderado'       => ['nullable', 'string', 'max:20'],
            'telefono_emergencia' => ['nullable', 'string', 'max:20'],
            'condicion_especial'  => ['nullable', 'string', 'max:1000'],

            // ── Perfil padre (prefijo p_ para evitar colisión con campos alumno) ─
            'p_dni'             => ['nullable', 'string', 'max:20'],
            'p_telefono'        => ['nullable', 'string', 'max:20'],
            'p_direccion'       => ['nullable', 'string', 'max:500'],
            'ocupacion'         => ['nullable', 'string', 'max:150'],
            'grado_instruccion' => ['nullable', 'in:sin_instruccion,primaria,secundaria,tecnico,universitario,posgrado'],

            // ── Perfil docente ────────────────────────────────────────────────
            't_dni'        => ['nullable', 'string', 'max:20'],
            'especialidad' => ['nullable', 'string', 'max:100'],
            't_telefono'   => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'El nombre es obligatorio.',
            'email.required'     => 'El correo electrónico es obligatorio.',
            'email.email'        => 'Ingresa un correo electrónico válido.',
            'email.unique'       => 'Este correo ya está registrado.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'role.required'      => 'El rol es obligatorio.',
            'role.in'            => 'El rol seleccionado no es válido.',
        ];
    }
}
