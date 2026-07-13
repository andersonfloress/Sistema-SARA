<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherProfileRequest extends FormRequest
{
    public function authorize(): bool { return auth()->user()?->isAdmin() ?? false; }

    public function rules(): array
    {
        return [
            'codigo_docente'   => ['nullable', 'string', 'max:50'],
            'dni'              => ['nullable', 'string', 'max:20'],
            'especialidad'     => ['nullable', 'string', 'max:100'],
            'telefono'         => ['nullable', 'string', 'max:20'],
            'direccion'        => ['nullable', 'string', 'max:500'],

            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'sexo'             => ['nullable', 'in:M,F'],
            'foto_perfil'      => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],

            'correo_alternativo'          => ['nullable', 'email', 'max:150'],
            'contacto_emergencia_nombre'  => ['nullable', 'string', 'max:150'],
            'contacto_emergencia_telefono' => ['nullable', 'string', 'max:20'],

            'fecha_ingreso'      => ['nullable', 'date'],
            'condicion_laboral'  => ['nullable', 'in:nombrado,contratado'],
            'nivel_academico'    => ['nullable', 'in:bachiller,licenciado,magister,doctor'],
            'numero_colegiatura' => ['nullable', 'string', 'max:30'],
            'turno'              => ['nullable', 'in:mañana,tarde,ambos'],
            'max_horas_semanales' => ['nullable', 'integer', 'min:1', 'max:60'],
            'cv_file'            => ['nullable', 'file', 'mimes:pdf', 'max:4096'],
        ];
    }
}
