@extends('layouts.app')
@section('title', 'Boletín Individual')
@section('page-title', 'Boletín Individual del Alumno')

@section('content')
@php
$alumnosJson = $students->map(function($alumno) {
    $sec = $alumno->enrollments->first()?->section;
    return [
        'id'           => $alumno->id,
        'nombre'       => strtolower($alumno->name),
        'grado'        => (string) ($sec?->grade ?? ''),
        'seccion_id'   => (string) ($sec?->id ?? ''),
        'seccion_nombre' => $sec?->name ?? '',
    ];
})->values()->toJson();
$totalAlumnos = $students->count();
@endphp

<div class="mb-6 bg-white rounded shadow-sm border border-gray-100 p-5"
     x-data="{
         busqueda: '',
         gradoBoletin: '',
         seccionBoletin: '',
         total: {{ $totalAlumnos }},
         alumnos: {{ $alumnosJson }},

         get seccionesDisponibles() {
             const base = this.gradoBoletin
                 ? this.alumnos.filter(a => a.grado === this.gradoBoletin)
                 : this.alumnos;
             const seen = new Set();
             return base
                 .filter(a => { if (!a.seccion_id || seen.has(a.seccion_id)) return false; seen.add(a.seccion_id); return true; })
                 .map(a => ({ id: a.seccion_id, nombre: a.seccion_nombre }))
                 .sort((a, b) => a.nombre.localeCompare(b.nombre));
         },
         get visibles() {
             return this.alumnos.filter(a =>
                 (!this.busqueda      || a.nombre.includes(this.busqueda.toLowerCase())) &&
                 (!this.gradoBoletin  || a.grado === this.gradoBoletin) &&
                 (!this.seccionBoletin || a.seccion_id === this.seccionBoletin)
             ).length;
         },
         filaVisible(nombre, grado, seccionId) {
             return (!this.busqueda       || nombre.includes(this.busqueda.toLowerCase())) &&
                    (!this.gradoBoletin   || grado === this.gradoBoletin) &&
                    (!this.seccionBoletin || seccionId === this.seccionBoletin);
         },
         resetGrado()   { this.seccionBoletin = ''; },
         limpiar()      { this.busqueda = ''; this.gradoBoletin = ''; this.seccionBoletin = ''; },
     }">

    {{-- Cabecera: año + descripción --}}
    <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
        <p class="text-sm text-gray-500">
            Filtra y elige un alumno para generar su boletín de notas en PDF (formato oficial).
        </p>
        <form method="GET" action="{{ route('reportes.boletin') }}" class="flex items-center gap-2">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Año</label>
            <select name="year" onchange="this.form.submit()"
                    class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-indigo-300 outline-none">
                @foreach($availableYears as $y)
                <option value="{{ $y }}" {{ (int) $selectedYear === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Filtros en cascada --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-3">

        {{-- Buscador --}}
        <div class="xl:col-span-1">
            <label class="block text-xs font-medium text-gray-500 mb-1">Buscar por nombre</label>
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text"
                       x-model="busqueda"
                       placeholder="Escribe el nombre..."
                       class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
        </div>

        {{-- Grado --}}
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">
                <span class="inline-flex items-center gap-1">
                    <span class="w-4 h-4 rounded-full bg-indigo-100 text-indigo-600 text-[10px] font-bold flex items-center justify-center">1</span>
                    Grado
                </span>
            </label>
            <select x-model="gradoBoletin" @change="resetGrado()"
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
                <option value="">Todos los grados</option>
                @foreach(range(1,5) as $g)
                <option value="{{ $g }}">{{ $g }}° Grado</option>
                @endforeach
            </select>
        </div>

        {{-- Sección (se activa al elegir grado) --}}
        <div>
            <label class="block text-xs font-medium mb-1" :class="gradoBoletin ? 'text-gray-500' : 'text-gray-300'">
                <span class="inline-flex items-center gap-1">
                    <span class="w-4 h-4 rounded-full text-[10px] font-bold flex items-center justify-center"
                          :class="gradoBoletin ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400'">2</span>
                    Sección
                </span>
            </label>
            <select x-model="seccionBoletin"
                    :disabled="!gradoBoletin"
                    class="w-full text-sm border rounded-lg px-3 py-2 outline-none transition"
                    :class="gradoBoletin
                        ? 'border-gray-200 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400'
                        : 'border-gray-100 bg-gray-50 text-gray-300 cursor-not-allowed'">
                <option value="">Todas las secciones</option>
                <template x-for="s in seccionesDisponibles" :key="s.id">
                    <option :value="s.id" x-text="s.nombre"></option>
                </template>
            </select>
        </div>

        {{-- Contador + Limpiar --}}
        <div class="flex flex-col justify-end gap-1">
            <div class="text-sm font-semibold"
                 :class="visibles < total ? 'text-indigo-600' : 'text-gray-700'">
                <span x-text="visibles"></span>
                <span class="font-normal text-gray-400 text-xs" x-text="' de ' + total + ' alumnos'"></span>
            </div>
            <button @click="limpiar()"
                    x-show="busqueda || gradoBoletin || seccionBoletin"
                    class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-red-500 transition">
                <i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Limpiar filtros
            </button>
        </div>
    </div>

    {{-- Tabla de alumnos --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-100">
                    <th class="py-2 pr-4">Alumno</th>
                    <th class="py-2 pr-4">Grado y Sección</th>
                    <th class="py-2 pr-4 text-right">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($students as $alumno)
                @php
                    $seccion   = $alumno->enrollments->first()?->section;
                    $gradeStr  = (string) ($seccion?->grade ?? '');
                    $seccionId = (string) ($seccion?->id ?? '');
                    $nombreLow = strtolower($alumno->name);
                @endphp
                <tr x-show="filaVisible({{ json_encode($nombreLow) }}, '{{$gradeStr}}', '{{$seccionId}}')">
                    <td class="py-2.5 pr-4 font-medium text-gray-800">{{ $alumno->name }}</td>
                    <td class="py-2.5 pr-4 text-gray-500">
                        @if($seccion)
                            <span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-700 rounded text-xs font-semibold">{{ $seccion->name }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="py-2.5 text-right">
                        <a href="{{ route('reportes.boletin.pdf', $alumno) }}?year={{ $selectedYear }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 text-red-700 rounded-lg text-xs font-medium hover:bg-red-100 transition">
                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Generar PDF
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="py-6 text-center text-gray-400">
                        No hay alumnos matriculados en el año {{ $selectedYear }}.
                    </td>
                </tr>
                @endforelse

                {{-- Sin resultados tras filtrar --}}
                @if($students->count() > 0)
                <tr x-show="visibles === 0 && (busqueda || gradoBoletin || seccionBoletin)">
                    <td colspan="3" class="py-8 text-center text-gray-400 text-sm">
                        <i data-lucide="search-x" class="w-6 h-6 mx-auto mb-2 text-gray-300"></i>
                        Sin resultados para los filtros aplicados.
                        <button @click="limpiar()" class="ml-2 text-indigo-500 hover:underline text-xs">Limpiar filtros</button>
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
