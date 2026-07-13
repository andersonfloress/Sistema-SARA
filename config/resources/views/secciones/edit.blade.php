@extends('layouts.app')
@section('title', 'Editar Sección')
@section('page-title', 'Editar Sección')

@section('content')
<div class="max-w-md mx-auto">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 mb-5 text-sm text-gray-500">
        <a href="{{ route('secciones.index') }}" class="hover:text-indigo-600 transition">Secciones</a>
        <span>›</span>
        <span class="text-gray-800 font-medium">Editar {{ $seccione->name }} ({{ $seccione->year }})</span>
    </div>

    {{-- Bloqueo total: año finalizado --}}
    @if($academicYear?->isFinished())
    <div class="mb-5 p-4 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-600">
        <strong>Año {{ $seccione->year }} finalizado.</strong>
        Esta sección no puede modificarse porque su año escolar está cerrado.
    </div>
    <a href="{{ route('secciones.index') }}"
       class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
        Volver a Secciones
    </a>

    @else

    {{-- Aviso si tiene alumnos --}}
    @if($enrolledCount > 0)
    <div class="mb-5 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
        <strong>⚠ {{ $enrolledCount }} alumno(s) matriculado(s)</strong> en esta sección.<br>
        El <strong>año, grado y nombre</strong> están bloqueados. Solo puedes ajustar el <strong>cupo máximo</strong> y el <strong>turno</strong>.
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        @foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
    </div>
    @endif

    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('secciones.update', $seccione) }}" class="space-y-4">
            @csrf @method('PUT')

            {{-- Nombre (letra) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sección (letra)</label>
                @if($enrolledCount > 0)
                    <input type="hidden" name="name" value="{{ $seccione->name }}">
                    <input type="text" value="{{ $seccione->name }}" disabled
                           class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-gray-50 text-gray-400 cursor-not-allowed">
                @else
                    <select name="name" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        @foreach(range('A', 'J') as $letra)
                        <option value="{{ $letra }}" {{ old('name', $seccione->name) === $letra ? 'selected' : '' }}>{{ $letra }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            {{-- Grado --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Grado</label>
                @if($enrolledCount > 0)
                    <input type="hidden" name="grade" value="{{ $seccione->grade }}">
                    <input type="text" value="{{ $seccione->grade }}° grado" disabled
                           class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-gray-50 text-gray-400 cursor-not-allowed">
                @else
                    <input type="number" name="grade" value="{{ old('grade', $seccione->grade) }}" min="1" max="5" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                @endif
            </div>

            {{-- Turno --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Turno</label>
                <select name="turno" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="mañana" {{ old('turno', $seccione->turno) === 'mañana' ? 'selected' : '' }}>Mañana</option>
                    <option value="tarde"  {{ old('turno', $seccione->turno) === 'tarde'  ? 'selected' : '' }}>Tarde</option>
                </select>
            </div>

            {{-- Cupo máximo --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cupo máximo</label>
                <input type="number" name="cupo_maximo" value="{{ old('cupo_maximo', $seccione->cupo_maximo) }}"
                       min="{{ $enrolledCount }}" max="100" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                @if($enrolledCount > 0)
                <p class="text-xs text-gray-400 mt-1">No puede ser menor a {{ $enrolledCount }} (alumnos actuales).</p>
                @endif
            </div>

            {{-- Año --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                @if($enrolledCount > 0)
                    <input type="hidden" name="year" value="{{ $seccione->year }}">
                    <input type="text" value="{{ $seccione->year }}" disabled
                           class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-gray-50 text-gray-400 cursor-not-allowed">
                @else
                    <input type="number" name="year" value="{{ old('year', $seccione->year) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                @endif
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                    Guardar Cambios
                </button>
                <a href="{{ route('secciones.index') }}"
                   class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    @endif
</div>
@endsection
