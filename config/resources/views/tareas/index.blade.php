@extends('layouts.app')
@section('title', 'Tareas')
@section('page-title', 'Tareas')

@section('content')

@php $user = auth()->user(); @endphp

{{-- Selector de hijo para padres --}}
@if($user->isParent() && isset($children) && $children->count() > 1)
<div class="mb-4">
    <form method="GET" class="flex gap-2 flex-wrap items-center">
        <label class="text-sm text-gray-500 font-medium">Ver tareas de:</label>
        @foreach($children as $c)
        <a href="?child_id={{ $c->id }}"
           class="px-3 py-1.5 rounded-full text-sm font-medium border transition-all
                  {{ ($child?->id ?? null) == $c->id
                      ? 'bg-[#8b1c30] text-white border-[#8b1c30]'
                      : 'bg-white text-gray-600 border-gray-200 hover:border-[#8b1c30]' }}">
            {{ $c->name }}
        </a>
        @endforeach
    </form>
</div>
@endif

<div class="flex justify-between items-center mb-6 flex-wrap gap-3">
    <p class="text-sm text-gray-500">
        {{ $tasks->count() }} {{ $tasks->count() === 1 ? 'tarea' : 'tareas' }}
    </p>
    @if($user->isAdmin() || $user->isTeacher())
    <a href="{{ route('tareas.create') }}"
       class="flex items-center gap-2 px-4 py-2 bg-[#8b1c30] text-white rounded-lg text-sm font-medium hover:bg-[#6b1427] transition">
        <i data-lucide="plus" class="w-4 h-4"></i> Nueva Tarea
    </a>
    @endif
</div>

@php
    $tasksByCourseId = $tasks->groupBy('course_id');
@endphp

@if($courses->isEmpty())
<div class="bg-white rounded border border-gray-100 p-14 text-center text-gray-400 shadow-sm">
    <i data-lucide="clipboard-list" class="w-12 h-12 mx-auto mb-3 opacity-30"></i>
    <p class="font-medium">No hay cursos asignados</p>
</div>
@else
<div class="space-y-2">
    @foreach($courses as $course)
    @php
        $courseTasks = $tasksByCourseId->get($course->id, collect());

        // Calcular badges de urgencia para mostrar en el header colapsado
        $pendientes = 0; $vencidas = 0; $entregadas = 0;
        foreach ($courseTasks as $t) {
            $exp = $t->isExpired();
            if ($user->isStudent()) {
                $sub = $t->latestSubmissionForStudent($user->id);
                if ($sub)       $entregadas++;
                elseif ($exp)   $vencidas++;
                else            $pendientes++;
            } elseif ($user->isParent() && isset($child)) {
                $sub = $t->latestSubmissionForStudent($child->id);
                if ($sub)       $entregadas++;
                elseif ($exp)   $vencidas++;
                else            $pendientes++;
            } else {
                if ($exp) $vencidas++; else $pendientes++;
            }
        }

        // Abrir automáticamente si tiene tareas pendientes o vencidas
        $autoOpen = $pendientes > 0 || $vencidas > 0;
    @endphp

    <div x-data="{ open: {{ $autoOpen ? 'true' : 'false' }} }"
         class="bg-white rounded border border-gray-100 shadow-sm overflow-hidden">

        {{-- ── Cabecera del acordeón ── --}}
        <button @click="open = !open"
                class="w-full flex items-center gap-3 px-4 py-3.5 text-left hover:bg-gray-50 transition-colors">

            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                 style="background:#fce7eb;">
                <i data-lucide="book-open" style="width:15px;height:15px;color:#8b1c30;"></i>
            </div>

            <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-800 text-sm truncate">{{ $course->name }}</p>
                <p class="text-xs text-gray-400">{{ $course->section->name }}</p>
            </div>

            {{-- Badges de urgencia --}}
            <div class="flex items-center gap-1.5 flex-shrink-0">
                @if($courseTasks->isEmpty())
                    <span class="text-xs text-gray-300">Sin tareas</span>
                @else
                    @if($vencidas > 0)
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                        {{ $vencidas }} vencida{{ $vencidas > 1 ? 's' : '' }}
                    </span>
                    @endif
                    @if($pendientes > 0)
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                        {{ $pendientes }} pendiente{{ $pendientes > 1 ? 's' : '' }}
                    </span>
                    @endif
                    @if($entregadas > 0 && $pendientes === 0 && $vencidas === 0)
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        Al día ✓
                    </span>
                    @endif
                    <span class="text-xs text-gray-400 ml-1">
                        {{ $courseTasks->count() }} {{ $courseTasks->count() === 1 ? 'tarea' : 'tareas' }}
                    </span>
                @endif
            </div>

            {{-- Chevron --}}
            <i data-lucide="chevron-down"
               class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200"
               :class="open ? 'rotate-180' : ''"></i>
        </button>

        {{-- ── Contenido del acordeón ── --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="border-t border-gray-100">

            @if($courseTasks->isEmpty())
            <div class="py-6 text-center text-gray-400 text-sm">
                <i data-lucide="inbox" class="w-5 h-5 mx-auto mb-1 opacity-40"></i>
                Sin tareas publicadas aún
            </div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($courseTasks->sortBy('deadline') as $t)
                @php
                    $expired  = $t->isExpired();
                    $subCount = $t->submissions->count();
                    $myLatest = $user->isStudent() ? $t->latestSubmissionForStudent($user->id) : null;

                    if ($user->isStudent()) {
                        if ($myLatest)    { $badge = ['Entregada', 'bg-green-100 text-green-700']; }
                        elseif ($expired) { $badge = ['Vencida',   'bg-red-100 text-red-700']; }
                        else              { $badge = ['Pendiente', 'bg-amber-100 text-amber-700']; }
                    } elseif ($user->isParent() && isset($child)) {
                        $childSub = $t->latestSubmissionForStudent($child->id);
                        if ($childSub)    { $badge = ['Entregada', 'bg-green-100 text-green-700']; }
                        elseif ($expired) { $badge = ['Vencida',   'bg-red-100 text-red-700']; }
                        else              { $badge = ['Pendiente', 'bg-amber-100 text-amber-700']; }
                    } else {
                        $badge = $expired
                            ? ['Vencida', 'bg-red-100 text-red-700']
                            : ['Activa',  'bg-green-100 text-green-700'];
                    }
                @endphp

                <a href="{{ route('tareas.show', $t) }}"
                   class="flex items-start gap-4 px-4 py-4 hover:bg-gray-50 transition-colors">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                         style="background:#fce7eb;">
                        <i data-lucide="clipboard-check" style="width:15px;height:15px;color:#8b1c30;"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-0.5">
                            <span class="font-medium text-gray-800 text-sm truncate">{{ $t->title }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $badge[1] }}">
                                {{ $badge[0] }}
                            </span>
                        </div>
                        @if($t->description)
                        <p class="text-xs text-gray-500 line-clamp-1">{{ $t->description }}</p>
                        @endif
                    </div>
                    <div class="text-right flex-shrink-0 hidden sm:block">
                        <p class="text-sm font-medium {{ $expired ? 'text-red-600' : 'text-gray-700' }}">
                            {{ $t->deadline->format('d/m/Y') }}
                        </p>
                        <p class="text-xs text-gray-400">{{ $t->deadline->format('H:i') }}</p>
                        @if(($user->isTeacher() || $user->isAdmin()) && $subCount > 0)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $subCount }} entrega(s)</p>
                        @endif
                        @if($user->isStudent() && $myLatest)
                        <p class="text-xs text-green-600 mt-0.5 font-medium">Intento {{ $myLatest->attempt }}/{{ $t->max_attempts }}</p>
                        @endif
                    </div>
                    {{-- fecha en móvil --}}
                    <p class="text-xs text-gray-400 sm:hidden flex-shrink-0 mt-1
                               {{ $expired ? 'text-red-500 font-medium' : '' }}">
                        {{ $t->deadline->format('d/m') }}
                    </p>
                </a>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
