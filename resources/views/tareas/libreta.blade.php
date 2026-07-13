@extends('layouts.app')
@section('title', 'Calificaciones de Tareas')
@section('page-title', 'Calificaciones de Tareas')

@section('content')
@php $user = auth()->user(); @endphp

{{-- ── Paso 1: Selector de Sección ────────────────────────────────── --}}
<div class="mb-5">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">
        <i data-lucide="layers" class="w-3.5 h-3.5 inline-block mr-1"></i> Sección
    </p>
    @if($sections->isEmpty())
    <p class="text-sm text-gray-400">No tienes secciones asignadas este año.</p>
    @else
    <div class="flex flex-wrap gap-2">
        @foreach($sections as $sec)
        <a href="{{ route('tareas.libreta', ['section_id' => $sec->id]) }}"
           class="flex items-center gap-1.5 px-4 py-2 rounded-full border text-sm font-medium transition-all duration-150
                  {{ $selectedSection?->id == $sec->id
                      ? 'bg-[#8b1c30] text-white border-[#8b1c30] shadow-sm'
                      : 'bg-white text-gray-600 border-gray-200 hover:border-[#8b1c30] hover:text-[#8b1c30]' }}">
            <i data-lucide="users" class="w-3.5 h-3.5"></i>
            {{ $sec->grade }}° {{ $sec->name }}
        </a>
        @endforeach
    </div>
    @endif
</div>

{{-- ── Paso 2: Selector de Curso ───────────────────────────────────── --}}
@if($selectedSection)
<div class="mb-5">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">
        <i data-lucide="book" class="w-3.5 h-3.5 inline-block mr-1"></i> Curso en {{ $selectedSection->grade }}° {{ $selectedSection->name }}
    </p>
    @if($courses->isEmpty())
    <div class="p-3 bg-amber-50 text-amber-700 text-sm rounded-lg border border-amber-100 inline-flex items-center gap-2">
        <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
        No tienes cursos en esta sección o no hay tareas publicadas.
    </div>
    @else
    <div class="flex flex-wrap gap-2">
        @foreach($courses as $c)
        <a href="{{ route('tareas.libreta', ['section_id' => $selectedSection->id, 'course_id' => $c->id]) }}"
           class="flex items-center gap-1.5 px-4 py-2 rounded-full border text-sm font-medium transition-all duration-150
                  {{ $selectedCourse?->id == $c->id
                      ? 'bg-[#8b1c30] text-white border-[#8b1c30] shadow-sm'
                      : 'bg-white text-gray-600 border-gray-200 hover:border-[#8b1c30] hover:text-[#8b1c30]' }}">
            <i data-lucide="book-open" class="w-3.5 h-3.5"></i>
            {{ $c->name }}
        </a>
        @endforeach
    </div>
    @endif
</div>
@endif

{{-- ── Paso 3: Tabla libreta ───────────────────────────────────────── --}}
@if($selectedCourse)
<div class="bg-white rounded shadow-sm border border-gray-100 overflow-hidden"
     x-data="{ search: '' }">

    {{-- Cabecera de tabla --}}
    <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-gray-100 flex-wrap">
        <div>
            <h3 class="font-semibold text-gray-800">
                {{ $selectedCourse->name }}
                <span class="text-gray-400 font-normal">— {{ $selectedSection->grade }}° {{ $selectedSection->name }}</span>
            </h3>
            <p class="text-xs text-gray-400 mt-0.5">
                {{ $students->count() }} alumno(s) · {{ $tasks->count() }} tarea(s)
            </p>
        </div>
        {{-- Buscador de alumno --}}
        <div class="relative">
            <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input type="text" x-model="search" placeholder="Buscar alumno..."
                   class="pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#8b1c30] w-52">
        </div>
    </div>

    @if($tasks->isEmpty())
    <div class="p-12 text-center text-gray-400">
        <i data-lucide="clipboard-x" class="w-10 h-10 mx-auto mb-3 opacity-30"></i>
        <p>No hay tareas publicadas para este curso.</p>
        <a href="{{ route('tareas.create') }}" class="text-sm text-[#8b1c30] font-medium mt-2 inline-block hover:underline">
            + Publicar primera tarea
        </a>
    </div>
    @elseif($students->isEmpty())
    <div class="p-12 text-center text-gray-400">
        <i data-lucide="users-x" class="w-10 h-10 mx-auto mb-3 opacity-30"></i>
        <p>No hay alumnos matriculados en esta sección.</p>
    </div>
    @else

    {{-- Tabla con scroll horizontal --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="sticky left-0 bg-gray-50 z-10 text-left px-5 py-3 font-semibold text-gray-600 min-w-48 border-r border-gray-100">
                        Alumno
                    </th>
                    @foreach($tasks as $t)
                    <th class="text-center px-3 py-3 font-medium text-gray-600 min-w-36 border-r border-gray-50 last:border-r-0">
                        <a href="{{ route('tareas.show', $t) }}"
                           class="hover:text-[#8b1c30] transition-colors block">
                            <span class="block truncate max-w-32 mx-auto" title="{{ $t->title }}">{{ $t->title }}</span>
                            <span class="block text-xs font-normal mt-0.5
                                         {{ $t->isExpired() ? 'text-red-400' : 'text-gray-400' }}">
                                {{ $t->deadline->format('d/m/Y') }}
                            </span>
                        </a>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($students as $st)
                <tr class="hover:bg-gray-50 transition-colors"
                    x-show="search === '' || '{{ strtolower($st->name) }}'.includes(search.toLowerCase())">
                    <td class="sticky left-0 bg-white hover:bg-gray-50 z-10 px-5 py-3 border-r border-gray-100 font-medium text-gray-700">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                 style="background: linear-gradient(135deg, #8b1c30, #6b1427);">
                                {{ strtoupper(substr($st->name, 0, 1)) }}
                            </div>
                            <span class="truncate max-w-36">{{ $st->name }}</span>
                        </div>
                    </td>
                    @foreach($tasks as $t)
                    @php $sub = $matrix[$st->id][$t->id] ?? null; @endphp
                    <td class="text-center px-2 py-2 border-r border-gray-50 last:border-r-0">
                        @if($sub)
                            {{-- Tiene entrega --}}
                            <div x-data="{ editing: false }" class="flex flex-col items-center gap-1">
                                <span x-show="!editing"
                                      @click="editing = true"
                                      title="Clic para editar nota"
                                      class="cursor-pointer inline-flex items-center gap-1">
                                    @if($sub->grade !== null)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold
                                                 {{ $sub->grade >= 11 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ number_format($sub->grade, 1) }}
                                    </span>
                                    @else
                                    <span class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center" title="Entregado — sin nota">
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-blue-600"></i>
                                    </span>
                                    @endif
                                </span>
                                {{-- Mini formulario inline --}}
                                <form x-show="editing" x-cloak
                                      method="POST"
                                      action="{{ route('tareas.grade', [$t, $sub]) }}"
                                      class="flex items-center gap-1"
                                      @click.away="editing = false">
                                    @csrf @method('PATCH')
                                    <input type="number" name="grade" step="0.1" min="0" max="20"
                                           value="{{ $sub->grade ?? '' }}"
                                           placeholder="—"
                                           class="w-16 border border-gray-300 rounded px-1.5 py-1 text-xs text-center focus:ring-1 focus:ring-[#8b1c30] focus:outline-none">
                                    <button type="submit"
                                            class="w-6 h-6 bg-[#8b1c30] text-white rounded flex items-center justify-center hover:bg-[#6b1427] transition flex-shrink-0">
                                        <i data-lucide="check" class="w-3 h-3"></i>
                                    </button>
                                </form>
                            </div>
                        @elseif($t->isExpired())
                            {{-- Venció sin entregar --}}
                            <span class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center mx-auto" title="No entregó">
                                <i data-lucide="x" class="w-3.5 h-3.5 text-red-500"></i>
                            </span>
                        @else
                            {{-- Aún activa, no entregó --}}
                            <span class="w-6 h-6 rounded-full bg-amber-50 border border-amber-200 flex items-center justify-center mx-auto" title="Pendiente">
                                <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-400"></i>
                            </span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Leyenda --}}
    <div class="flex flex-wrap items-center gap-4 px-5 py-3 border-t border-gray-100 text-xs text-gray-400">
        <span class="flex items-center gap-1.5">
            <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-bold text-xs">14.5</span> Calificado
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center">
                <i data-lucide="check" class="w-3 h-3 text-blue-600"></i>
            </span> Entregado (sin nota)
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center">
                <i data-lucide="x" class="w-3 h-3 text-red-500"></i>
            </span> No entregó (vencida)
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-5 h-5 rounded-full bg-amber-50 border border-amber-200 flex items-center justify-center">
                <i data-lucide="clock" class="w-3 h-3 text-amber-400"></i>
            </span> Pendiente
        </span>
        <span class="ml-auto text-gray-300">Clic en ✓ azul para poner nota rápida</span>
    </div>
    @endif
</div>
@elseif(!$selectedSection)
<div class="bg-white rounded border border-gray-100 shadow-sm p-14 text-center text-gray-400">
    <i data-lucide="mouse-pointer-click" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
    <p class="font-medium">Selecciona una sección para comenzar</p>
    <p class="text-sm mt-1">Elige la sección y el curso para ver la libreta de tareas.</p>
</div>
@elseif(!$selectedCourse)
<div class="bg-white rounded border border-gray-100 shadow-sm p-14 text-center text-gray-400">
    <i data-lucide="book-open" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
    <p class="font-medium">Ahora elige un curso</p>
    <p class="text-sm mt-1">Selecciona el curso de {{ $selectedSection->grade }}° {{ $selectedSection->name }} que quieres revisar.</p>
</div>
@endif

@endsection

@push('scripts')
<script>
document.querySelectorAll('details').forEach(d => {
    d.addEventListener('toggle', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
});
</script>
@endpush
