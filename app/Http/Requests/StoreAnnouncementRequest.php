<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'teacher']);
    }

    public function rules(): array
    {
        // Los administradores pueden dirigir comunicados a cualquier rol,
        // incluido 'admin'. Los docentes no deben poder enviar comunicados
        // dirigidos solo a administradores (opción oculta en la vista, pero
        // sin esta restricción un docente podría bypassear la UI con un POST directo).
        $allowedTargets = auth()->user()?->role === 'admin'
            ? ['all', 'student', 'teacher', 'admin', 'parent']
            : ['all', 'student', 'teacher', 'parent'];

        return [
            'title'       => ['required', 'string', 'max:255'],
            'content'     => ['required', 'string'],
            'target_role' => ['required', Rule::in($allowedTargets)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'El título es obligatorio.',
            'content.required'     => 'El contenido es obligatorio.',
            'target_role.required' => 'El destinatario es obligatorio.',
            'target_role.in'       => 'Destinatario inválido.',
        ];
    }
}
