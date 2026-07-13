<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentProfileRequest extends FormRequest
{
    public function authorize(): bool { return auth()->user()?->isAdmin() ?? false; }

    public function rules(): array
    {
        return [
            'codigo_estudiante'  => ['nullable', 'string', 'max:50'],
            'dni'                => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento'   => ['nullable', 'date'],
            'sexo'               => ['nullable', 'in:M,F'],
            'nacionalidad'       => ['nullable', 'string', 'max:80'],
            'tipo_sangre'        => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'foto_perfil'        => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
            'grado'              => ['nullable', 'integer', 'min:1', 'max:12'],
            'turno'              => ['nullable', 'string', 'max:50'],
            'anio_ingreso'       => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'direccion'          => ['nullable', 'string', 'max:500'],
            'telefono'           => ['nullable', 'string', 'max:20'],
            'nombre_apoderado'   => ['nullable', 'string', 'max:200'],
            'dni_apoderado'      => ['nullable', 'string', 'max:20'],
            'parentesco'         => ['nullable', 'in:padre,madre,tutor,tutora,abuelo,abuela,tio,tia,otro'],
            'telefono_emergencia'=> ['nullable', 'string', 'max:20'],
            'condicion_especial' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'sexo.in'           => 'El sexo debe ser M o F.',
            'tipo_sangre.in'    => 'El tipo de sangre seleccionado no es válido.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento no es válida.',
            'anio_ingreso.min'  => 'El año de ingreso no es válido.',
            'anio_ingreso.max'  => 'El año de ingreso no es válido.',
        ];
    }
}
