<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParentProfileRequest extends FormRequest
{
    public function authorize(): bool { return auth()->user()?->isAdmin() ?? false; }

    public function rules(): array
    {
        return [
            'dni'               => ['nullable', 'string', 'max:20'],
            'telefono'          => ['nullable', 'string', 'max:20'],
            'direccion'         => ['nullable', 'string', 'max:500'],
            'ocupacion'         => ['nullable', 'string', 'max:150'],
            'grado_instruccion' => ['nullable', 'in:sin_instruccion,primaria,secundaria,tecnico,universitario,posgrado'],
        ];
    }

    public function messages(): array
    {
        return [
            'grado_instruccion.in' => 'El grado de instrucción seleccionado no es válido.',
        ];
    }
}
