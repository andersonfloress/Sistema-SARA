@extends('layouts.app')
@section('title', 'Matricular Alumnos')
@section('page-title', 'Matricular Alumnos')

@section('content')

@if(session('success'))
<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
    {{ session('error') }}
</div>
@endif

{{-- Selector de año + botón nuevo alumno --}}
<div class="flex items-center gap-2 mb-5 flex-wrap justify-between">
    <div class="flex items-center gap-2 flex-wrap">
        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Año escolar</label>
        <form method="GET" action="{{ route('matricula.index') }}">
            <select name="year" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500">
                @foreach($years as $y)
                <option value="{{ $y->year }}" {{ (int) $year === (int) $y->year ? 'selected' : '' }}>
                    {{ $y->year }} — {{ $y->statusLabel() }}
                </option>
                @endforeach
            </select>
        </form>
        @if($academicYear)
            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $academicYear->statusBadgeClass() }}">
                {{ $academicYear->statusLabel() }}
            </span>
        @endif
    </div>
    <a href="{{ route('matricula.admitir') }}"
       class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo alumno
    </a>
</div>

@if($academicYear?->isFinished())
<div class="mb-5 p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600">
    <strong>El año {{ $year }} está finalizado.</strong> Se muestra el registro histórico de matrículas. No se pueden hacer cambios.
</div>
@elseif(!$academicYear || !$academicYear->isEnrollmentOpen())
<div class="mb-5 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700">
    La matrícula para el año {{ $year }} no está habilitada todavía.
    @if($academicYear)
    Ve a <a href="{{ route('anios.index') }}" class="underline font-medium">Años Escolares</a> para habilitarla antes de matricular alumnos.
    @endif
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Pendientes de matrícula --}}
    @if(!$academicYear?->isFinished())
    <div class="lg:col-span-2 bg-white rounded shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
            <h2 class="font-semibold text-gray-800">
                Pendientes de matrícula
                <span class="ml-1 text-sm font-normal text-gray-500">(<span id="pendientes-count">{{ $pendientes->count() }}</span>)</span>
            </h2>
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" id="buscador-pendientes" placeholder="Buscar por nombre o DNI..."
                       class="border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm w-64 focus:ring-2 focus:ring-indigo-500"
                       oninput="filtrarPendientes(this.value)">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3">Alumno</th>
                        <th class="px-6 py-3">Situación</th>
                        <th class="px-6 py-3">Sección destino</th>
                        <th class="px-6 py-3 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" id="tbody-pendientes">
                    @forelse($pendientes as $s)
                    @php
                        $opciones = $sections->where('grade', $s->targetGrade);
                        $dni = $s->studentProfile?->dni;
                    @endphp
                    <tr class="hover:bg-gray-50 transition fila-pendiente {{ $s->isRepitente ? 'bg-red-50/40' : '' }}"
                        data-buscar="{{ \Illuminate\Support\Str::lower($s->name) }} {{ $dni }}">
                        <td class="px-6 py-3 font-medium text-gray-800">
                            {{ $s->name }}
                            @if($dni)
                            <span class="block text-xs text-gray-400 font-normal">DNI {{ $dni }}</span>
                            @endif
                            @if($s->isRepitente)
                            <span class="inline-block mt-0.5 px-1.5 py-0.5 bg-red-100 text-red-700 text-xs font-semibold rounded">
                                REPITENTE
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-gray-500">
                            @if($s->isRepitente)
                                <span class="text-red-600">{{ $s->prevSectionLabel }} → Repite {{ $s->targetGrade }}°</span>
                            @elseif($s->prevSectionLabel === 'Nuevo ingreso')
                                <span class="text-indigo-600 font-medium">Nuevo ingreso → 1°</span>
                            @else
                                {{ $s->prevSectionLabel }} → Grado {{ $s->targetGrade }}°
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            <form method="POST" id="form-{{ $s->id }}" action="#" class="flex items-center gap-2">
                                @csrf
                                <input type="hidden" name="student_id" value="{{ $s->id }}">
                                <select name="section_select"
                                        onchange="document.getElementById('form-{{ $s->id }}').action = '/secciones/' + this.value + '/matricular'"
                                        class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500"
                                        {{ $opciones->isEmpty() || !$academicYear?->isEnrollmentOpen() ? 'disabled' : '' }}>
                                    <option value="">Seleccionar sección...</option>
                                    @foreach($opciones as $sec)
                                    @php $lleno = $sec->cupo_maximo && $sec->enrollments_count >= $sec->cupo_maximo; @endphp
                                    <option value="{{ $sec->id }}" {{ $lleno ? 'disabled' : '' }}>
                                        {{ $sec->name }} — {{ ucfirst($sec->turno ?? '—') }}
                                        ({{ $sec->enrollments_count }}/{{ $sec->cupo_maximo ?? '∞' }}){{ $lleno ? ' LLENO' : '' }}
                                    </option>
                                    @endforeach
                                </select>
                                <button type="submit"
                                        class="px-3 py-1.5 bg-crimson-700 hover:bg-crimson-800 text-white text-xs font-medium rounded-lg transition disabled:opacity-40"
                                        {{ $opciones->isEmpty() || !$academicYear?->isEnrollmentOpen() ? 'disabled' : '' }}>
                                    Matricular
                                </button>
                            </form>
                            @if($opciones->isEmpty())
                            <p class="text-xs text-red-500 mt-1">
                                No hay secciones de {{ $s->targetGrade }}° creadas para {{ $year }}.
                                <a href="{{ route('secciones.index') }}" class="underline">Crear secciones</a>
                            </p>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No hay alumnos pendientes de matrícula para {{ $year }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <p id="sin-resultados" class="px-6 py-8 text-center text-gray-400 hidden">No se encontraron alumnos con ese nombre o DNI.</p>
        </div>
    </div>
    @endif

    {{-- Secciones y cupos --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-5 {{ $academicYear?->isFinished() ? 'lg:col-span-1' : '' }}">
        <h3 class="font-semibold text-gray-800 mb-4">Cupos por sección ({{ $year }})</h3>
        <div class="space-y-1.5 max-h-[520px] overflow-y-auto">
            @forelse($sections as $sec)
            @php $lleno = $sec->cupo_maximo && $sec->enrollments_count >= $sec->cupo_maximo; @endphp
            <div class="flex items-center justify-between text-xs px-3 py-2 rounded-lg {{ $lleno ? 'bg-red-50' : 'bg-gray-50' }}">
                <span class="text-gray-700 font-medium">{{ $sec->name }}
                    <span class="text-gray-400">({{ ucfirst($sec->turno ?? '—') }})</span>
                </span>
                <span class="{{ $lleno ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                    {{ $sec->enrollments_count }}/{{ $sec->cupo_maximo ?? '∞' }}
                </span>
            </div>
            @empty
            <p class="text-xs text-gray-400">No hay secciones creadas para {{ $year }}.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Ya matriculados --}}
<div class="bg-white rounded shadow-sm border border-gray-100 mt-6">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3 flex-wrap">
        <h2 class="font-semibold text-gray-800">
            Ya matriculados en {{ $year }}
            <span class="text-sm font-normal text-gray-500">({{ $matriculados->count() }})</span>
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-6 py-3">Alumno</th>
                    <th class="px-6 py-3">Sección</th>
                    @if($academicYear?->isFinished())
                    <th class="px-6 py-3">Resultado del año</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($matriculados as $s)
                @php $e = $s->enrollments->first(); @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-3 font-medium text-gray-800">{{ $s->name }}</td>
                    <td class="px-6 py-3 text-gray-600">
                        {{ $e?->section?->name }} — {{ ucfirst($e?->section?->turno ?? '—') }}
                    </td>
                    @if($academicYear?->isFinished())
                    <td class="px-6 py-3">
                        @if($e)
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $e->resultBadgeClass() }}">
                            {{ $e->resultLabel() }}
                        </span>
                        @endif
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ $academicYear?->isFinished() ? 3 : 2 }}" class="px-6 py-8 text-center text-gray-400">Aún no hay alumnos matriculados en {{ $year }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function filtrarPendientes(valor) {
    const q = valor.trim().toLowerCase();
    const filas = document.querySelectorAll('#tbody-pendientes .fila-pendiente');
    let visibles = 0;
    filas.forEach(fila => {
        const coincide = fila.dataset.buscar.includes(q);
        fila.classList.toggle('hidden', !coincide);
        if (coincide) visibles++;
    });
    document.getElementById('pendientes-count').textContent = visibles;
    document.getElementById('sin-resultados').classList.toggle('hidden', visibles !== 0 || filas.length === 0);
}
</script>

@endsection
