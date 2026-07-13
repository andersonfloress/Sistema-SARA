<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool { return auth()->user()?->isAdmin() ?? false; }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:150'],
            'section_id'    => ['required', 'exists:sections,id'],
            'teacher_id'    => ['nullable', 'exists:users,id', 'exists:users,id,role,teacher'],
            'hours_per_week'=> ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'El nombre del curso es obligatorio.',
            'section_id.required' => 'La sección es obligatoria.',
            'section_id.exists'   => 'La sección seleccionada no existe.',
            'teacher_id.exists'   => 'El docente seleccionado no existe.',
        ];
    }
}
