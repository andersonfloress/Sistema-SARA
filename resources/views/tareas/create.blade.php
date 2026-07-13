@extends('layouts.app')
@section('title', 'Nueva Tarea')
@section('page-title', 'Nueva Tarea')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background:#fce7eb;">
                <i data-lucide="clipboard-plus" style="width:20px;height:20px;color:#8b1c30;"></i>
            </div>
            <div>
                <h2 class="font-semibold text-gray-800">Publicar tarea</h2>
                <p class="text-xs text-gray-400">Los alumnos del curso podrán verla y enviar su entrega</p>
            </div>
        </div>

        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('tareas.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required maxlength="200"
                       placeholder="Ej: Resolución de ejercicios — Álgebra"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8b1c30] focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción / Instrucciones</label>
                <textarea name="description" rows="4" maxlength="2000"
                          placeholder="Describe lo que deben hacer los alumnos..."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8b1c30] focus:border-transparent resize-none">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Curso <span class="text-red-500">*</span></label>
                    <select name="course_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8b1c30]">
                        <option value="">Seleccionar curso...</option>
                        @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ old('course_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} — {{ $c->section->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha límite <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="deadline" value="{{ old('deadline') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8b1c30]">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Intentos de envío permitidos
                    <span class="text-xs text-gray-400 font-normal ml-1">(el alumno puede reenviar hasta este número de veces)</span>
                </label>
                <div class="flex items-center gap-3">
                    <input type="number" name="max_attempts" value="{{ old('max_attempts', 1) }}"
                           min="1" max="10" required
                           class="w-28 border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8b1c30]">
                    <span class="text-sm text-gray-400">máximo 10 intentos</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Archivo adjunto
                    <span class="text-xs text-gray-400 font-normal ml-1">(opcional — guía, enunciado, etc.)</span>
                </label>
                <label class="flex items-center gap-3 border-2 border-dashed border-gray-200 rounded-lg px-4 py-5 cursor-pointer hover:border-[#8b1c30] transition-colors group"
                       id="file-label">
                    <i data-lucide="upload-cloud" class="w-6 h-6 text-gray-300 group-hover:text-[#8b1c30] transition-colors flex-shrink-0"></i>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-500 group-hover:text-gray-700 truncate" id="file-name">
                            Haz clic para seleccionar un archivo
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">PDF, Word, Excel o PowerPoint — máx. 20 MB</p>
                    </div>
                    <input type="file" name="file" id="file-input" class="hidden"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                           onchange="document.getElementById('file-name').textContent = this.files[0]?.name ?? 'Haz clic para seleccionar un archivo'">
                </label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 sm:flex-none px-6 py-2.5 bg-[#8b1c30] text-white rounded-lg text-sm font-medium hover:bg-[#6b1427] transition">
                    Publicar tarea
                </button>
                <a href="{{ route('tareas.index') }}"
                   class="flex-1 sm:flex-none px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition text-center">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
