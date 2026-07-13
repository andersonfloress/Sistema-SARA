@extends('layouts.app')
@section('title', 'Nuevo Comunicado')
@section('page-title', 'Nuevo Comunicado')

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-5">Publicar Comunicado</h2>

    @if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        @foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('comunicados.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
            <input type="text" name="title" value="{{ old('title') }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Contenido *</label>
            <textarea name="content" rows="5" required
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">{{ old('content') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Destinatario *</label>
            <select name="target_role" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="all"     {{ old('target_role') === 'all'     ? 'selected' : '' }}>Todos</option>
                <option value="student" {{ old('target_role') === 'student' ? 'selected' : '' }}>Solo Alumnos</option>
                <option value="teacher" {{ old('target_role') === 'teacher' ? 'selected' : '' }}>Solo Docentes</option>
                <option value="parent"  {{ old('target_role') === 'parent'  ? 'selected' : '' }}>Solo Padres</option>
                @if(auth()->user()->isAdmin())
                <option value="admin"   {{ old('target_role') === 'admin'   ? 'selected' : '' }}>Solo Administradores</option>
                @endif
            </select>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                Publicar
            </button>
            <a href="{{ route('comunicados.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
