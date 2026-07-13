@extends('layouts.app')
@section('title', 'Cursos — '.$seccione->name)
@section('page-title', 'Cursos de la Sección')

@section('content')

@if(session('success'))
<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{{ session('error') }}</div>
@endif

<div class="mb-4 flex items-center gap-3">
    <a href="{{ route('secciones.index') }}" class="text-indigo-600 hover:underline text-sm flex items-center gap-1">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Secciones
    </a>
    <span class="text-gray-400">/</span>
    <span class="text-sm font-medium text-gray-700">
        {{ $seccione->name }} — {{ ucfirst($seccione->turno) }} ({{ $seccione->year }})
    </span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Lista de cursos con edición inline --}}
    <div class="lg:col-span-2 bg-white rounded shadow-sm border border-gray-100"
         x-data="{ editando: null }">

        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Cursos ({{ $seccione->courses->count() }})</h2>
            <p class="text-xs text-gray-400 mt-0.5">Haz clic en ✏ para editar nombre, docente u horas de un curso.</p>
        </div>

        @if ($errors->any())
        <div class="mx-6 mt-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
            @foreach ($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
        </div>
        @endif

        <div class="divide-y divide-gray-100">
            @forelse($seccione->courses as $c)
            <div x-show="editando !== {{ $c->id }}" class="flex items-center gap-3 px-6 py-3 hover:bg-gray-50 transition">
                {{-- Vista normal --}}
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-800 text-sm">{{ $c->name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $c->teacher?->name ?? 'Sin docente' }}
                        &nbsp;·&nbsp; {{ $c->hours_per_week }}h/sem
                    </p>
                </div>
                <button @click="editando = {{ $c->id }}"
                        class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg transition flex-shrink-0" title="Editar">
                    <i data-lucide="pencil" class="w-4 h-4"></i>
                </button>
                <form method="POST" action="{{ route('secciones.destroyCourse', [$seccione, $c]) }}"
                      onsubmit="return confirmDeleteCourse(event, '{{ addslashes($c->name) }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="p-1.5 text-red-400 hover:bg-red-50 rounded-lg transition flex-shrink-0" title="Eliminar">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>

            {{-- Fila de edición inline --}}
            <div x-show="editando === {{ $c->id }}" x-cloak class="px-6 py-4 bg-indigo-50/50 border-l-4 border-indigo-400">
                <form method="POST" action="{{ route('secciones.updateCourse', [$seccione, $c]) }}" class="space-y-3">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nombre del curso</label>
                            <input type="text" name="name" value="{{ old('name', $c->name) }}" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div class="sm:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Docente</label>
                            <select name="teacher_id"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                                <option value="">Sin asignar</option>
                                @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ $c->teacher_id == $t->id ? 'selected' : '' }}>
                                    {{ $t->name }} ({{ $t->carga_actual }}/{{ $t->carga_maxima }}h)
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Horas/sem</label>
                            <input type="number" name="hours_per_week" value="{{ old('hours_per_week', $c->hours_per_week) }}"
                                   min="1" max="20" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit"
                                class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition">
                            Guardar
                        </button>
                        <button type="button" @click="editando = null"
                                class="px-4 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-medium rounded-lg transition">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>

            @empty
            <div class="px-6 py-8 text-center text-gray-400 text-sm">Sin cursos asignados aún.</div>
            @endforelse
        </div>
    </div>

    {{-- Agregar curso --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-800 mb-4">Agregar Curso</h3>
        <form method="POST" action="{{ route('secciones.storeCourse', $seccione) }}" class="space-y-3">
            @csrf
            <input type="hidden" name="section_id" value="{{ $seccione->id }}">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                <input type="text" name="name" required placeholder="Ej: Comunicación"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Docente</label>
                <select name="teacher_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">Sin asignar</option>
                    @foreach($teachers as $t)
                    <option value="{{ $t->id }}">
                        {{ $t->name }} — {{ ucfirst($t->turno_docente ?? 'sin turno') }}, {{ $t->carga_actual }}/{{ $t->carga_maxima }}h
                    </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">
                    Sección turno <strong>{{ ucfirst($seccione->turno) }}</strong>.
                    Solo docentes de ese turno (o "ambos"), sin superar su tope.
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Horas/semana</label>
                <input type="number" name="hours_per_week" value="4" min="1" max="20"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit"
                    class="w-full py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                Agregar Curso
            </button>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
function confirmDeleteCourse(event, name) {
    event.preventDefault();
    const form = event.target;
    Swal.fire({
        title: '¿Eliminar curso?',
        text: `Se eliminará "${name}" y todas sus calificaciones registradas.`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
    }).then(r => { if (r.isConfirmed) form.submit(); });
    return false;
}
</script>
@endpush
