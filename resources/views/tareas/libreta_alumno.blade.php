@extends('layouts.app')
@section('title', 'Calificaciones de Tareas')
@section('page-title', 'Calificaciones de Tareas')

@section('content')
@php $user = auth()->user(); @endphp

{{-- Selector de hijo para padres --}}
@if($user->isParent() && isset($children) && $children->count() > 1)
<div class="mb-5 flex flex-wrap gap-2 items-center">
    <span class="text-sm text-gray-500 font-medium">Ver libreta de:</span>
    @foreach($children as $c)
    <a href="{{ route('tareas.libreta', ['child_id' => $c->id]) }}"
       class="px-3 py-1.5 rounded-full text-sm font-medium border transition-all
              {{ ($student?->id ?? null) == $c->id
                  ? 'bg-[#8b1c30] text-white border-[#8b1c30]'
                  : 'bg-white text-gray-600 border-gray-200 hover:border-[#8b1c30]' }}">
        {{ $c->name }}
    </a>
    @endforeach
</div>
@endif

@if(!isset($student) || !$student)
<div class="bg-white rounded border border-gray-100 shadow-sm p-14 text-center text-gray-400">
    <i data-lucide="user-x" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
    <p>No hay alumno seleccionado.</p>
</div>
@elseif($grouped->isEmpty())
<div class="bg-white rounded border border-gray-100 shadow-sm p-14 text-center text-gray-400">
    <i data-lucide="clipboard-list" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
    <p class="font-medium">No hay tareas publicadas aún</p>
    <p class="text-sm mt-1">Los docentes no han publicado tareas todavía.</p>
</div>
@else

{{-- Resumen general --}}
@php
    $allItems   = $grouped->flatten(1);
    $totalTasks = $allItems->count();
    $submitted  = $allItems->filter(fn($i) => $i['submission'] !== null)->count();
    $graded     = $allItems->filter(fn($i) => $i['submission']?->grade !== null)->count();
    $avg        = $graded > 0
        ? $allItems->filter(fn($i) => $i['submission']?->grade !== null)
              ->avg(fn($i) => $i['submission']->grade)
        : null;

    // Pendiente = activa y no entregada | No entregó = vencida y no entregada
    $pending   = $allItems->filter(fn($i) => $i['submission'] === null && !$i['task']->isExpired())->count();
    $missed    = $allItems->filter(fn($i) => $i['submission'] === null && $i['task']->isExpired())->count();
@endphp

<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
    <div class="bg-white rounded border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-gray-800">{{ $totalTasks }}</p>
        <p class="text-xs text-gray-400 mt-1">Tareas totales</p>
    </div>
    <div class="bg-white rounded border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-blue-600">{{ $submitted }}</p>
        <p class="text-xs text-gray-400 mt-1">Entregadas</p>
    </div>
    <div class="bg-white rounded border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-amber-500">{{ $pending }}</p>
        <p class="text-xs text-gray-400 mt-1">Pendientes</p>
    </div>
    <div class="bg-white rounded border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-red-500">{{ $missed }}</p>
        <p class="text-xs text-gray-400 mt-1">No entregadas</p>
    </div>
    <div class="bg-white rounded border border-gray-100 shadow-sm p-4 text-center col-span-2 sm:col-span-1">
        <p class="text-2xl font-bold {{ $avg !== null ? ($avg >= 11 ? 'text-green-600' : 'text-red-600') : 'text-gray-300' }}">
            {{ $avg !== null ? number_format($avg, 1) : '—' }}
        </p>
        <p class="text-xs text-gray-400 mt-1">Promedio notas</p>
    </div>
</div>

{{-- Tabla por curso --}}
<div class="space-y-4">
    @foreach($grouped as $courseId => $items)
    @php $course = $items->first()['task']->course; @endphp
    <div class="bg-white rounded shadow-sm border border-gray-100 overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-3.5 border-b border-gray-100 bg-gray-50">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                 style="background:#fce7eb;">
                <i data-lucide="book-open" style="width:16px;height:16px;color:#8b1c30;"></i>
            </div>
            <div>
                <p class="font-semibold text-gray-800 text-sm">{{ $course->name }}</p>
                <p class="text-xs text-gray-400">{{ $course->section->name }}</p>
            </div>
            <div class="ml-auto text-xs text-gray-400">
                {{ $items->count() }} tarea(s)
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-50 text-xs text-gray-400 uppercase tracking-wide">
                        <th class="text-left px-5 py-2.5 font-medium">Tarea</th>
                        <th class="text-center px-4 py-2.5 font-medium">Fecha límite</th>
                        <th class="text-center px-4 py-2.5 font-medium">Estado</th>
                        <th class="text-center px-4 py-2.5 font-medium">Nota</th>
                        <th class="text-center px-4 py-2.5 font-medium">Intento</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($items as $item)
                    @php
                        $t   = $item['task'];
                        $sub = $item['submission'];
                        if ($sub)                         { [$sLabel, $sColor] = ['Entregada', 'bg-green-100 text-green-700']; }
                        elseif ($t->isExpired())          { [$sLabel, $sColor] = ['No entregó', 'bg-red-100 text-red-700']; }
                        else                              { [$sLabel, $sColor] = ['Pendiente',  'bg-amber-100 text-amber-700']; }
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3 font-medium text-gray-700">
                            <a href="{{ route('tareas.show', $t) }}"
                               class="hover:text-[#8b1c30] transition-colors hover:underline">
                                {{ $t->title }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-500 whitespace-nowrap">
                            <span class="{{ $t->isExpired() && !$sub ? 'text-red-500' : '' }}">
                                {{ $t->deadline->format('d/m/Y') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $sColor }}">
                                {{ $sLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($sub?->grade !== null)
                            <span class="font-bold text-sm {{ $sub->grade >= 11 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($sub->grade, 1) }}<span class="text-xs font-normal text-gray-400">/20</span>
                            </span>
                            @else
                            <span class="text-gray-300 text-sm">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-gray-400 text-xs">
                            @if($sub)
                            {{ $sub->attempt }}/{{ $t->max_attempts }}
                            @else
                            —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('tareas.show', $t) }}"
                               class="p-1.5 rounded-lg text-gray-400 hover:text-[#8b1c30] hover:bg-[#fce7eb] transition inline-flex">
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
