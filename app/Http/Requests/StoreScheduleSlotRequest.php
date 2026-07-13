<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleSlotRequest extends FormRequest
{
    public function authorize(): bool { return auth()->user()?->isAdmin() ?? false; }

    public function rules(): array
    {
        return [
            'course_id'   => ['required', 'exists:courses,id'],
            'day_of_week' => ['required', 'in:lunes,martes,miercoles,jueves,viernes'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'after:start_time'],
            'classroom'   => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required'   => 'El curso es obligatorio.',
            'course_id.exists'     => 'El curso seleccionado no existe.',
            'day_of_week.required' => 'El día es obligatorio.',
            'day_of_week.in'       => 'Día inválido.',
            'start_time.required'  => 'La hora de inicio es obligatoria.',
            'end_time.required'    => 'La hora de fin es obligatoria.',
            'end_time.after'       => 'La hora de fin debe ser posterior a la de inicio.',
        ];
    }
}
