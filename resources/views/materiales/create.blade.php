@extends('layouts.app')
@section('title', 'Publicar Material')
@section('page-title', 'Publicar Material Educativo')

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-5">Nuevo Material</h2>

    @if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        @foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('materiales.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
            <input type="text" name="title" value="{{ old('title') }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Curso *</label>
            <select name="course_id" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="">Seleccionar curso</option>
                @foreach($courses as $c)
                <option value="{{ $c->id }}" {{ old('course_id') == $c->id ? 'selected' : '' }}>
                    {{ $c->name }} — {{ $c->section->name }}
                </option>
                @endforeach
            </select>
            @if($courses->isEmpty())
            <p class="text-xs text-amber-600 mt-1">No tienes cursos asignados todavía.</p>
            @endif
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
            <select name="type" id="type-select" required onchange="toggleFields()"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="">Seleccionar</option>
                <option value="document" {{ old('type') === 'document' ? 'selected' : '' }}>Documento (archivo)</option>
                <option value="video"    {{ old('type') === 'video'    ? 'selected' : '' }}>Video (enlace)</option>
                <option value="link"     {{ old('type') === 'link'     ? 'selected' : '' }}>Enlace</option>
            </select>
        </div>
        <div id="file-field" class="hidden">
            <label class="block text-sm font-medium text-gray-700 mb-1">Archivo *</label>
            <input type="file" name="file"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
            <p class="text-xs text-gray-400 mt-1">PDF, Word, PowerPoint, Excel o ZIP — máx. 10MB.</p>
        </div>
        <div id="url-field" class="hidden">
            <label class="block text-sm font-medium text-gray-700 mb-1">URL *</label>
            <input type="url" name="url" value="{{ old('url') }}" placeholder="https://..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
            <textarea name="description" rows="3"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                Publicar
            </button>
            <a href="{{ route('materiales.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                Cancelar
            </a>
        </div>
    </form>
</div>

<script>
function toggleFields() {
    const type = document.getElementById('type-select').value;
    document.getElementById('file-field').classList.toggle('hidden', type !== 'document');
    document.getElementById('url-field').classList.toggle('hidden', type === 'document' || type === '');
}
document.addEventListener('DOMContentLoaded', toggleFields);
</script>
@endsection
