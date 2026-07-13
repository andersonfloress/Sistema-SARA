@extends('layouts.app')
@section('title', 'Secciones')
@section('page-title', 'Secciones y Cursos')

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

<div class="flex justify-between items-center mb-4">
    <p class="text-sm text-gray-500">
        Las secciones se generan desde
        <a href="{{ route('anios.index') }}" class="text-indigo-600 hover:underline font-medium">Años Escolares</a>
        durante la etapa de Planificación.
    </p>
</div>

<div class="bg-white rounded shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3 flex-wrap">
        <h2 class="font-semibold text-gray-800">Lista de Secciones ({{ $sections->count() }})</h2>
        {{-- Filtro por año --}}
        <form method="GET" action="{{ route('secciones.index') }}" class="flex items-center gap-2">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Año</label>
            <select name="year" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="">Todos los años</option>
                @foreach($availableYears as $y)
                <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>
                    {{ $y }}
                    @if(isset($yearStatuses[$y]))
                        — {{ $yearStatuses[$y]->statusLabel() }}
                    @endif
                </option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-6 py-3">Sección</th>
                    <th class="px-6 py-3">Grado</th>
                    <th class="px-6 py-3">Turno</th>
                    <th class="px-6 py-3">Año</th>
                    <th class="px-6 py-3 text-center">Cursos</th>
                    <th class="px-6 py-3 text-center">Alumnos / Cupo</th>
                    <th class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sections as $s)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-3 font-medium text-gray-800">{{ $s->name }}</td>
                    <td class="px-6 py-3 text-gray-600">{{ $s->grade }}°</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $s->turno === 'mañana' ? 'bg-sky-100 text-sky-700' : 'bg-orange-100 text-orange-700' }}">
                            {{ ucfirst($s->turno) }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-gray-600">{{ $s->year }}</td>
                    <td class="px-6 py-3 text-center text-gray-600">{{ $s->courses_count }}</td>
                    <td class="px-6 py-3 text-center text-gray-600">
                        {{ $s->students_count }} / {{ $s->cupo_maximo ?? '∞' }}
                        @if($s->cupo_maximo && $s->students_count >= $s->cupo_maximo)
                        <span class="ml-1 text-xs text-red-500 font-medium">(lleno)</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-right">
                        <div class="flex justify-end gap-1">
                            <a href="{{ route('secciones.courses', $s) }}"
                               class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Ver cursos y docentes">
                                <i data-lucide="book-open" class="w-4 h-4"></i>
                            </a>
                            <a href="{{ route('secciones.edit', $s) }}"
                               class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Editar">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </a>
                            <form method="POST" action="{{ route('secciones.destroy', $s) }}"
                                  onsubmit="return confirmDelete(event, '{{ $s->name }}', {{ $s->students_count }})">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition" title="Eliminar">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400">
                    No hay secciones registradas{{ request('year') ? ' para el año ' . request('year') : '' }}.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmDelete(event, name, alumnos) {
    event.preventDefault();
    const form = event.target;
    const msg = alumnos > 0
        ? `¿Eliminar la sección "${name}"?\n\n⚠ Tiene ${alumnos} alumno(s) matriculado(s). Al eliminarla se borrarán también sus matrículas y cursos.`
        : `¿Eliminar la sección "${name}"? También se eliminarán sus cursos.`;
    Swal.fire({
        title: '¿Eliminar sección?',
        text: msg,
        icon: alumnos > 0 ? 'error' : 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then(r => { if (r.isConfirmed) form.submit(); });
    return false;
}
</script>
@endpush
