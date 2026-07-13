@extends('layouts.app')
@section('title', 'Registrar Calificaciones')
@section('page-title', 'Registrar Calificaciones')

@section('content')
@if($errors->any())
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
    @foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
</div>
@endif

{{-- Step filters --}}
<div class="bg-white rounded shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="font-semibold text-gray-800 mb-4">Seleccionar Sección, Curso y Trimestre</h3>
    <form method="GET" action="{{ route('calificaciones.create') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sección</label>
            <select name="section_id" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="">Seleccionar sección</option>
                @foreach($sections as $s)
                <option value="{{ $s->id }}" {{ $selectedSection?->id == $s->id ? 'selected' : '' }}>
                    {{ $s->name }}
                </option>
                @endforeach
            </select>
        </div>
        @if($selectedSection)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Curso</label>
            <select name="course_id" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="">Seleccionar curso</option>
                @foreach($courses as $c)
                <option value="{{ $c->id }}" {{ $selectedCourse?->id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @if($selectedCourse)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Trimestre</label>
            <select name="period" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="I"   {{ $selectedPeriod === 'I'   ? 'selected' : '' }}>Trimestre I</option>
                <option value="II"  {{ $selectedPeriod === 'II'  ? 'selected' : '' }}>Trimestre II</option>
                <option value="III" {{ $selectedPeriod === 'III' ? 'selected' : '' }}>Trimestre III</option>
            </select>
        </div>
        @endif
    </form>
</div>

{{-- Bulk grade entry --}}
@if($selectedCourse && $students->count())
<div class="bg-white rounded shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">
            {{ $selectedCourse->name }} — {{ $selectedSection->name }} —
            <span class="text-indigo-600">Trimestre {{ $selectedPeriod }}</span>
        </h3>
        <p class="text-xs text-gray-400 mt-0.5">Notas de 0 a 20. Dejar en blanco para no registrar.</p>
    </div>

    <form method="POST" action="{{ route('calificaciones.store') }}">
        @csrf
        <input type="hidden" name="course_id" value="{{ $selectedCourse->id }}">
        <input type="hidden" name="period" value="{{ $selectedPeriod }}">

        <div class="divide-y divide-gray-100">
            @foreach($students as $student)
            @php $existing = $existingGrades[$student->id] ?? null; @endphp
            <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div class="w-9 h-9 bg-blue-100 rounded-full flex items-center justify-center text-sm font-bold text-blue-700 flex-shrink-0">
                        {{ strtoupper(substr($student->name, 0, 1)) }}
                    </div>
                    <span class="font-medium text-gray-800 truncate">{{ $student->name }}</span>
                    @if($existing)
                    <span class="text-xs text-indigo-500 flex-shrink-0">Nota actual: {{ number_format($existing->score, 1) }}</span>
                    @endif
                </div>
                <div class="flex gap-3 items-center">
                    <div>
                        <input type="number" name="grades[{{ $student->id }}][score]"
                               value="{{ $existing ? number_format($existing->score, 1) : '' }}"
                               min="0" max="20" step="0.5"
                               class="w-24 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 text-center"
                               placeholder="0–20">
                    </div>
                    <div>
                        <input type="text" name="grades[{{ $student->id }}][observation]"
                               value="{{ $existing?->observation ?? '' }}"
                               class="w-48 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                               placeholder="Observación (opc.)">
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
            <a href="{{ route('calificaciones.index') }}"
               class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                Cancelar
            </a>
            <button type="submit"
                    class="flex items-center gap-2 px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                <i data-lucide="save" class="w-4 h-4"></i> Guardar Calificaciones
            </button>
        </div>
    </form>
</div>
@elseif($selectedCourse)
<div class="bg-white rounded p-8 text-center text-gray-400 border border-gray-100">
    <i data-lucide="users" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
    <p>No hay alumnos matriculados en esta sección.</p>
</div>
@endif
@endsection
