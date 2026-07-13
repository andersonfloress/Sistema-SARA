<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'event_date'  => ['required', 'date'],
            'target_role' => ['required', 'in:all,student,teacher,admin,parent'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'      => 'El título es obligatorio.',
            'event_date.required' => 'La fecha del evento es obligatoria.',
            'event_date.date'     => 'La fecha no es válida.',
            'target_role.required' => 'El destinatario es obligatorio.',
            'target_role.in'      => 'Destinatario inválido.',
        ];
    }
}
