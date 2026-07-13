<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool { return auth()->user()?->isAdmin() ?? false; }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'in:A,B,C,D,E,F,G,H,I,J'],
            'grade'       => ['required', 'integer', 'min:1', 'max:5'],
            'year'        => ['required', 'integer', 'exists:academic_years,year'],
            'turno'       => ['required', 'in:mañana,tarde'],
            'cupo_maximo' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'La sección es obligatoria.',
            'name.in'        => 'El sistema soporta secciones de la A a la J.',
            'grade.required' => 'El grado es obligatorio.',
            'grade.max'      => 'La educación secundaria va de 1° a 5° grado.',
            'year.required'  => 'El año es obligatorio.',
            'year.exists'    => 'El año seleccionado no existe como año escolar registrado.',
            'turno.required' => 'El turno es obligatorio.',
            'turno.in'       => 'El turno debe ser mañana o tarde.',
            'cupo_maximo.required' => 'El cupo máximo es obligatorio.',
        ];
    }
}
