<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'teacher']);
    }

    public function rules(): array
    {
        $rules = [
            'title'       => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:document,video,link'],
            'description' => ['nullable', 'string', 'max:1000'],
            'course_id'   => ['required', 'exists:courses,id'],
        ];

        if ($this->input('type') === 'document') {
            $rules['file'] = ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip', 'max:10240'];
        } else {
            $rules['url'] = ['required', 'url', 'max:1000'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required'     => 'El título es obligatorio.',
            'type.required'      => 'El tipo de material es obligatorio.',
            'type.in'            => 'El tipo debe ser documento, video o enlace.',
            'course_id.required' => 'Debes seleccionar un curso.',
            'course_id.exists'   => 'El curso seleccionado no es válido.',
            'file.required'      => 'Debes adjuntar un archivo.',
            'file.mimes'         => 'El archivo debe ser PDF, Word, PowerPoint, Excel o ZIP.',
            'file.max'           => 'El archivo no debe superar 10MB.',
            'url.required'       => 'La URL es obligatoria.',
            'url.url'            => 'Ingresa una URL válida (debe empezar con http:// o https://).',
        ];
    }
}
