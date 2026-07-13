@extends('layouts.app')
@section('title', 'Años Escolares')
@section('page-title', 'Gestión de Años Escolares')

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
@if($errors->any())
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
    <ul class="list-disc list-inside">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Crear nuevo año --}}
<div class="bg-white rounded shadow-sm border border-gray-100 p-6 mb-5">
    <h2 class="text-lg font-semibold text-gray-800 mb-1">Crear nuevo año escolar</h2>
    <p class="text-sm text-gray-500 mb-4">
        Un año nuevo empieza en estado <strong>Planificación</strong>: se pueden crear secciones y asignar docentes,
        pero la matrícula de alumnos permanece bloqueada hasta que la habilites.
    </p>

    <form method="POST" action="{{ route('anios.store') }}" class="flex items-end gap-3 flex-wrap">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
            <input type="number" name="year" value="{{ old('year', $missingYears->first() ?? now()->year + 1) }}"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-32 focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cupo máximo por sección</label>
            <input type="number" name="default_capacity" value="{{ old('default_capacity', 30) }}"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-40 focus:ring-2 focus:ring-indigo-500">
        </div>
        <button type="submit"
                class="bg-crimson-700 hover:bg-crimson-800 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            Crear año
        </button>
    </form>

    @if($missingYears->isNotEmpty())
    <p class="text-xs text-gray-400 mt-3">
        Ya existen secciones para: {{ $missingYears->implode(', ') }} — sin registro de año escolar todavía.
        Créalos aquí para poder controlar su estado de matrícula.
    </p>
    @endif
</div>

{{-- Lista de años --}}
<div class="bg-white rounded shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Años escolares registrados</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-6 py-3">Año</th>
                    <th class="px-6 py-3">Estado</th>
                    <th class="px-6 py-3 text-center">Secciones</th>
                    <th class="px-6 py-3">Cupo por sección</th>
                    <th class="px-6 py-3">Matrícula habilitada desde</th>
                    <th class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($academicYears as $anio)
                @php $secCount = $sectionCounts[$anio->year] ?? 0; @endphp
                <tr class="hover:bg-gray-50 transition {{ $anio->isFinished() ? 'opacity-70' : '' }}">
                    <td class="px-6 py-4 font-semibold text-gray-800 text-base">{{ $anio->year }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $anio->statusBadgeClass() }}">
                            {{ $anio->statusLabel() }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($secCount > 0)
                            <a href="{{ route('secciones.index') }}"
                               class="text-indigo-600 hover:underline font-medium">{{ $secCount }}</a>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $anio->default_capacity }} alumnos</td>
                    <td class="px-6 py-4 text-gray-500">
                        {{ $anio->enrollment_opened_at?->format('d/m/Y H:i') ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2 flex-wrap">

                        @if($anio->isFinished())
                            <span class="text-xs text-gray-400 italic">Año cerrado</span>

                        @elseif($anio->isEnrollmentOpen())
                            {{-- Matrícula abierta: cerrar o finalizar --}}
                            <form method="POST" action="{{ route('anios.closeEnrollment', $anio) }}"
                                  onsubmit="return confirm('¿Volver el año {{ $anio->year }} a Planificación? Esto bloqueará nuevas matrículas.');">
                                @csrf
                                <button type="submit" class="text-amber-700 hover:text-amber-900 text-sm font-medium">
                                    Volver a Planificación
                                </button>
                            </form>
                            <span class="text-gray-300">|</span>
                            <form method="POST" action="{{ route('anios.finish', $anio) }}"
                                  onsubmit="return confirm('¿Finalizar el año {{ $anio->year }}? Quedará cerrado definitivamente. Asegúrate de que todos los resultados estén registrados en Promoción.');">
                                @csrf
                                <button type="submit" class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                                    Finalizar año
                                </button>
                            </form>

                        @else
                            {{-- Planificación: generar secciones + habilitar matrícula --}}
                            @if($secCount === 0)
                            {{-- Sin secciones: mostrar botón de generar de forma prominente --}}
                            <form method="POST" action="{{ route('secciones.generate') }}"
                                  onsubmit="return confirm('¿Generar 50 secciones (A–J) para los 5 grados del año {{ $anio->year }}?\n\nCupo: {{ $anio->default_capacity }} alumnos por sección\n5 secciones mañana + 5 tarde por grado.')">
                                @csrf
                                <input type="hidden" name="year" value="{{ $anio->year }}">
                                <button type="submit"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Generar secciones
                                </button>
                            </form>
                            <span class="text-gray-300">|</span>
                            @else
                            {{-- Ya tiene secciones: regenerar (agrega las que falten) --}}
                            <form method="POST" action="{{ route('secciones.generate') }}"
                                  onsubmit="return confirm('¿Completar secciones faltantes para {{ $anio->year }}? Solo se crearán las que no existan todavía.')">
                                @csrf
                                <input type="hidden" name="year" value="{{ $anio->year }}">
                                <button type="submit"
                                        class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                                    Completar secciones
                                </button>
                            </form>
                            <span class="text-gray-300">|</span>
                            @endif
                            <form method="POST" action="{{ route('anios.openEnrollment', $anio) }}">
                                @csrf
                                <button type="submit" class="text-green-700 hover:text-green-900 text-sm font-medium">
                                    Habilitar matrícula
                                </button>
                            </form>
                        @endif

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-6 text-center text-gray-400">No hay años escolares registrados todavía.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Leyenda de estados --}}
<div class="mt-4 flex items-center gap-4 text-xs text-gray-500 flex-wrap">
    <span class="flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>
        Planificación: crea secciones y asigna docentes, matrícula bloqueada
    </span>
    <span class="flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
        Matrícula habilitada: se pueden matricular alumnos
    </span>
    <span class="flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-gray-400 inline-block"></span>
        Finalizado: año cerrado, datos históricos preservados
    </span>
</div>

@endsection
