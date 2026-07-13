@extends('layouts.app')
@section('title', 'Promoción de Año')
@section('page-title', 'Promoción de Año Escolar')

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

{{-- Selector de año --}}
<div class="bg-white rounded shadow-sm border border-gray-100 p-5 mb-5">
    <div class="flex items-end gap-4 flex-wrap">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Año de origen</label>
            <form method="GET" action="{{ route('promocion.index') }}" id="form-year">
                <div class="flex items-center gap-2">
                    <select name="from_year" onchange="document.getElementById('form-year').submit()"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        @forelse($years as $y)
                        <option value="{{ $y }}" {{ (int)$fromYear === (int)$y ? 'selected' : '' }}>{{ $y }}</option>
                        @empty
                        <option value="">Sin años con secciones</option>
                        @endforelse
                    </select>
                    @if($academicYear)
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $academicYear->statusBadgeClass() }}">
                        {{ $academicYear->statusLabel() }}
                    </span>
                    @endif
                </div>
            </form>
        </div>
        <div class="flex items-start gap-3 flex-wrap">
            <p class="text-sm text-gray-500 max-w-lg">
                El sistema calcula automáticamente el resultado según el promedio de notas
                (mínimo <strong class="text-gray-700">11/20</strong> para aprobar).
                Puedes ajustar casos puntuales antes de ejecutar la promoción.
                Los de 5° aprobados egresan automáticamente.
            </p>
            @if(!$academicYear?->isFinished() && $fromYear)
            <form method="POST" action="{{ route('promocion.autoCalculateYear') }}"
                  onsubmit="return confirm('¿Auto-calcular resultados de TODAS las secciones del año {{ $fromYear }} según sus promedios de notas?\n\nPromedio ≥ 11 → Aprobado | Promedio < 11 → Repitente\n\nSe sobreescribirán los resultados ya registrados.')">
                @csrf
                <input type="hidden" name="from_year" value="{{ $fromYear }}">
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition shadow-sm">
                    ⚡ Auto-calcular todo el año
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

@if($fromYear && $sections->isNotEmpty())

{{-- Resumen y botón de ejecución --}}
@if($stats)
<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
    <div class="bg-white rounded-lg border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
        <p class="text-xs text-gray-500 mt-1">Total alumnos</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-green-600">{{ $stats['approved'] }}</p>
        <p class="text-xs text-gray-500 mt-1">Aprobados</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-purple-600">{{ $stats['graduated'] }}</p>
        <p class="text-xs text-gray-500 mt-1">Egresados (5°)</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-red-600">{{ $stats['retained'] }}</p>
        <p class="text-xs text-gray-500 mt-1">Repitentes</p>
    </div>
    <div class="bg-white rounded-lg border border-{{ $stats['pending'] > 0 ? 'amber' : 'gray' }}-200 shadow-sm p-4 text-center {{ $stats['pending'] > 0 ? 'bg-amber-50' : 'bg-gray-50' }}">
        <p class="text-2xl font-bold {{ $stats['pending'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">{{ $stats['pending'] }}</p>
        <p class="text-xs text-gray-500 mt-1">Sin resultado</p>
    </div>
</div>

{{-- Botón ejecutar promoción --}}
@if(!$academicYear?->isFinished())
<div class="bg-white rounded shadow-sm border border-gray-100 p-5 mb-5 flex items-center justify-between gap-4 flex-wrap">
    <div>
        @if($stats['pending'] > 0)
        <p class="text-sm text-amber-700 font-medium">
            ⚠ Faltan {{ $stats['pending'] }} alumno(s) sin resultado. Completa todos los resultados para poder ejecutar la promoción.
        </p>
        @else
        <p class="text-sm text-green-700 font-medium">
            ✓ Todos los resultados están registrados. Puedes ejecutar la promoción a {{ $fromYear + 1 }}.
        </p>
        @endif
    </div>
    <form method="POST" action="{{ route('promocion.store') }}" onsubmit="return confirmarPromocion(event)">
        @csrf
        <input type="hidden" name="from_year" value="{{ $fromYear }}">
        <button type="submit"
                {{ $stats['pending'] > 0 ? 'disabled' : '' }}
                class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition disabled:opacity-40 disabled:cursor-not-allowed">
            Ejecutar promoción {{ $fromYear }} → {{ $fromYear + 1 }}
        </button>
    </form>
</div>
@else
<div class="mb-5 p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600">
    El año {{ $fromYear }} está <strong>finalizado</strong>. La promoción ya fue ejecutada. Los resultados son históricos.
</div>
@endif
@endif

{{-- Panel de secciones --}}
<div class="grid grid-cols-1 lg:grid-cols-4 gap-5">

    {{-- Lista de secciones (columna izquierda) --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 lg:col-span-1">
        <div class="px-4 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm">Secciones — {{ $fromYear }}</h3>
        </div>
        <div class="divide-y divide-gray-50 max-h-[600px] overflow-y-auto">
            @php $prevGrade = null; @endphp
            @foreach($sections as $sec)
                @if($sec->grade !== $prevGrade)
                    <div class="px-4 py-1.5 bg-gray-50 text-xs font-bold text-gray-400 uppercase tracking-wide">
                        {{ $sec->grade }}° Grado
                    </div>
                    @php $prevGrade = $sec->grade; @endphp
                @endif
                <a href="{{ route('promocion.index', ['from_year' => $fromYear, 'section_id' => $sec->id]) }}"
                   class="flex items-center justify-between px-4 py-2.5 text-sm transition
                          {{ (int)$selectedSectionId === (int)$sec->id ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'hover:bg-gray-50 text-gray-700' }}">
                    <span>{{ $sec->name }} <span class="text-gray-400 text-xs">{{ ucfirst($sec->turno ?? '') }}</span></span>
                    <span class="flex items-center gap-1">
                        @if($sec->is_complete)
                            <span class="w-2 h-2 rounded-full bg-green-400" title="Resultados completos"></span>
                        @elseif($sec->with_result > 0)
                            <span class="w-2 h-2 rounded-full bg-amber-400" title="Resultados parciales"></span>
                        @else
                            <span class="w-2 h-2 rounded-full bg-gray-300" title="Sin resultados"></span>
                        @endif
                        <span class="text-xs text-gray-400">{{ $sec->with_result }}/{{ $sec->total_students }}</span>
                    </span>
                </a>
            @endforeach
        </div>
        {{-- Leyenda --}}
        <div class="px-4 py-3 border-t border-gray-100 space-y-1 text-xs text-gray-400">
            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span> Completo</div>
            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span> Parcial</div>
            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gray-300 inline-block"></span> Sin registrar</div>
        </div>
    </div>

    {{-- Detalle de la sección seleccionada --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 lg:col-span-3">
        @if($selectedSection && $enrollments->isNotEmpty())
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h3 class="font-semibold text-gray-800">
                    {{ $selectedSection->name }}
                    <span class="text-gray-400 font-normal text-sm">— {{ ucfirst($selectedSection->turno ?? '') }} — {{ $fromYear }}</span>
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $selectedSection->with_result }}/{{ $selectedSection->total_students }} resultados registrados
                </p>
            </div>
            @if(!$academicYear?->isFinished())
            <div class="flex items-center gap-2 flex-wrap">
                {{-- AUTO-CALCULAR por notas (botón principal) --}}
                <form method="POST" action="{{ route('promocion.autoCalculateSection') }}"
                      onsubmit="return confirm('¿Auto-calcular resultados de {{ $selectedSection->name }} según promedios de notas?\n\nPromedio ≥ 11 → Aprobado | Promedio < 11 → Repitente\n\nSe sobreescribirán los resultados ya registrados.')">
                    @csrf
                    <input type="hidden" name="section_id" value="{{ $selectedSection->id }}">
                    <button type="submit"
                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition">
                        ⚡ Auto-calcular
                    </button>
                </form>
                <span class="text-gray-300 text-xs">|</span>
                {{-- Marcar todos Aprobados --}}
                <form method="POST" action="{{ route('promocion.setBulkResult') }}"
                      onsubmit="return confirm('¿Marcar TODOS los alumnos de esta sección como Aprobados?')">
                    @csrf
                    <input type="hidden" name="section_id" value="{{ $selectedSection->id }}">
                    <input type="hidden" name="result" value="approved">
                    <button type="submit"
                            class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition">
                        ✓ Todos Aprobados
                    </button>
                </form>
                {{-- Marcar todos Repitentes --}}
                <form method="POST" action="{{ route('promocion.setBulkResult') }}"
                      onsubmit="return confirm('¿Marcar TODOS los alumnos de esta sección como Repitentes?')">
                    @csrf
                    <input type="hidden" name="section_id" value="{{ $selectedSection->id }}">
                    <input type="hidden" name="result" value="retained">
                    <button type="submit"
                            class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition">
                        ✗ Todos Repitentes
                    </button>
                </form>
                {{-- Limpiar resultados --}}
                <form method="POST" action="{{ route('promocion.clearResult') }}"
                      onsubmit="return confirm('¿Limpiar todos los resultados de esta sección? Volverán a Sin resultado.')">
                    @csrf
                    <input type="hidden" name="section_id" value="{{ $selectedSection->id }}">
                    <button type="submit"
                            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-medium rounded-lg transition">
                        Limpiar
                    </button>
                </form>
            </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-5 py-3">Alumno</th>
                        <th class="px-5 py-3 text-center">Promedio</th>
                        <th class="px-5 py-3 text-center">Resultado</th>
                        @if(!$academicYear?->isFinished())
                        <th class="px-5 py-3 text-center">Cambiar</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($enrollments as $enrollment)
                    <tr class="hover:bg-gray-50 transition
                               {{ $enrollment->result === 'retained' ? 'bg-red-50/50' : '' }}
                               {{ $enrollment->result === 'graduated' ? 'bg-purple-50/50' : '' }}">
                        <td class="px-5 py-3 font-medium text-gray-800">
                            {{ $enrollment->student->name }}
                            @if($enrollment->student->studentProfile?->dni)
                            <span class="block text-xs text-gray-400 font-normal">DNI {{ $enrollment->student->studentProfile->dni }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-center">
                            @if($enrollment->avg_score !== null)
                                <span class="font-semibold {{ $enrollment->avg_score >= 11 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($enrollment->avg_score, 1) }}
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $enrollment->resultBadgeClass() }}">
                                {{ $enrollment->resultLabel() }}
                            </span>
                        </td>
                        @if(!$academicYear?->isFinished())
                        <td class="px-5 py-3 text-center">
                            <form method="POST" action="{{ route('promocion.setResult') }}" class="inline-flex items-center gap-1">
                                @csrf
                                <input type="hidden" name="enrollment_id" value="{{ $enrollment->id }}">
                                <select name="result"
                                        onchange="this.form.submit()"
                                        class="border border-gray-200 rounded px-2 py-1 text-xs focus:ring-2 focus:ring-indigo-400 bg-white">
                                    <option value="" disabled {{ $enrollment->result === null ? 'selected' : '' }}>-- seleccionar --</option>
                                    <option value="approved" {{ in_array($enrollment->result, ['approved', 'graduated']) ? 'selected' : '' }}>
                                        {{ $selectedSection->grade >= 5 ? 'Egresa' : 'Aprobado' }}
                                    </option>
                                    <option value="retained" {{ $enrollment->result === 'retained' ? 'selected' : '' }}>
                                        Repitente
                                    </option>
                                </select>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @elseif($selectedSectionId && $enrollments->isEmpty())
        <div class="p-12 text-center text-gray-400">
            <p class="font-medium">No hay alumnos matriculados en esta sección.</p>
        </div>

        @else
        <div class="p-12 text-center text-gray-400">
            <p class="font-medium mb-1">Selecciona una sección de la lista para registrar resultados.</p>
            <p class="text-sm">Cada alumno debe tener su resultado antes de ejecutar la promoción.</p>
        </div>
        @endif
    </div>
</div>

@elseif($fromYear)
<div class="bg-white rounded border border-gray-100 shadow-sm p-14 text-center">
    <p class="text-gray-500 font-medium">No hay secciones registradas para el año {{ $fromYear }}.</p>
    <a href="{{ route('secciones.index') }}" class="mt-3 inline-block text-sm text-indigo-600 hover:underline">
        Ir a Secciones para crearlas
    </a>
</div>
@endif

@endsection

@push('scripts')
<script>
function confirmarPromocion(event) {
    event.preventDefault();
    const form = event.target;
    Swal.fire({
        title: '¿Ejecutar promoción de año?',
        html: 'Los alumnos <strong>Aprobados</strong> pasarán al grado siguiente en {{ $fromYear + 1 }}.<br>' +
              'Los <strong>Repitentes</strong> quedarán pendientes de matrícula en el mismo grado.<br>' +
              'Los de <strong>5° aprobados</strong> egresarán.<br><br>' +
              'El año {{ $fromYear }} quedará marcado como <strong>Finalizado</strong>.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, ejecutar promoción',
        cancelButtonText: 'Cancelar',
    }).then(r => { if (r.isConfirmed) form.submit(); });
    return false;
}
</script>
@endpush
