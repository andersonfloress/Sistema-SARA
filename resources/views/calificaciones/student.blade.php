@extends('layouts.app')
@section('title', 'Mis Calificaciones')
@section('page-title', 'Mis Calificaciones')

@section('content')

{{-- ── Selector de hijo (solo para padres con más de un hijo) ───────── --}}
@include('partials.parent_child_selector', [
    'children'     => $children     ?? collect(),
    'selectedChild'=> $selectedChild ?? null,
])

{{-- ── Cabecera ─────────────────────────────────────────────────────── --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-lg font-bold text-gray-800">Boleta de calificaciones</h2>
        @if($section)
        <p class="text-sm text-gray-500 mt-0.5">
            {{ $section->name }}
            &nbsp;·&nbsp; Año {{ $section->year }}
        </p>
        @endif
    </div>

    @if($overallAvg !== null)
    <div class="flex items-center gap-3 px-5 py-3 rounded border"
         style="{{ $overallAvg >= 11
             ? 'background:#f0fdf4; border-color:#bbf7d0;'
             : 'background:#fef2f2; border-color:#fecaca;' }}">
        <i data-lucide="{{ $overallAvg >= 11 ? 'trending-up' : 'trending-down' }}"
           class="w-5 h-5 {{ $overallAvg >= 11 ? 'text-green-500' : 'text-red-500' }}"></i>
        <div>
            <p class="text-xs font-medium {{ $overallAvg >= 11 ? 'text-green-600' : 'text-red-600' }}">
                Promedio General — Año actual
            </p>
            <p class="text-2xl font-extrabold leading-none {{ $overallAvg >= 11 ? 'text-green-700' : 'text-red-700' }}">
                {{ $overallAvg }}
                <span class="text-sm font-normal text-gray-400">/ 20</span>
            </p>
        </div>
    </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- AÑO ACTUAL                                                          --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}

@if($courses->isEmpty())
<div class="bg-white rounded border border-gray-100 shadow-sm p-14 text-center mb-6">
    <i data-lucide="clipboard-list" class="w-10 h-10 mx-auto mb-3 text-gray-300"></i>
    <p class="text-gray-500 font-medium">No hay calificaciones registradas todavía.</p>
</div>
@else

@include('calificaciones._grade_table', [
    'courses'     => $courses,
    'gradeMatrix' => $gradeMatrix,
    'periods'     => $periods,
    'overallAvg'  => $overallAvg,
])

@endif

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- HISTORIAL DE GRADOS ANTERIORES                                      --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}

@if(!empty($history))
<div class="mt-8">
    <div class="flex items-center gap-3 mb-4">
        <i data-lucide="history" class="w-5 h-5 text-gray-400"></i>
        <h3 class="text-base font-bold text-gray-700">Historial académico</h3>
        <span class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-500 rounded-full">
            {{ count($history) }} año(s) anterior(es)
        </span>
    </div>

    <div class="space-y-3">
        @foreach($history as $idx => $hist)
        @php
            $histSec = $hist['section'];
            $histAvg = $hist['overallAvg'];
            $approved = $histAvg !== null && $histAvg >= 11;
        @endphp

        {{-- Acordeón con Alpine.js --}}
        <div x-data="{ open: {{ $idx === 0 ? 'true' : 'false' }} }"
             class="bg-white rounded border border-gray-100 shadow-sm overflow-hidden">

            {{-- Cabecera del acordeón --}}
            <button @click="open = !open"
                    class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    {{-- Año badge --}}
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded text-sm font-bold text-white flex-shrink-0"
                          style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                        {{ $histSec->year }}
                    </span>
                    <div>
                        <p class="font-semibold text-gray-800">
                            {{ $histSec->name }}
                        </p>
                        <p class="text-xs text-gray-400">Año escolar {{ $histSec->year }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    {{-- Promedio badge --}}
                    @if($histAvg !== null)
                    <div class="text-right hidden sm:block">
                        <p class="text-xs text-gray-400">Promedio</p>
                        <p class="text-lg font-extrabold {{ $approved ? 'text-green-600' : 'text-red-600' }}">
                            {{ $histAvg }}
                        </p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold hidden sm:inline-flex
                        {{ $approved ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $approved ? 'Promovido' : 'Observado' }}
                    </span>
                    @endif
                    {{-- Chevron --}}
                    <i data-lucide="chevron-down"
                       class="w-5 h-5 text-gray-400 transition-transform duration-200"
                       :class="open ? 'rotate-180' : ''"></i>
                </div>
            </button>

            {{-- Tabla de notas históricas --}}
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-cloak>
                <div class="border-t border-gray-100">
                    @include('calificaciones._grade_table', [
                        'courses'     => $hist['courses'],
                        'gradeMatrix' => $hist['gradeMatrix'],
                        'periods'     => $periods,
                        'overallAvg'  => $hist['overallAvg'],
                        'compact'     => true,
                    ])
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Leyenda --}}
<div class="mt-5 flex flex-wrap gap-4 text-xs text-gray-400">
    <span class="flex items-center gap-1.5">
        <span class="inline-block w-3 h-3 rounded-full bg-green-400"></span> Aprobado (≥ 11)
    </span>
    <span class="flex items-center gap-1.5">
        <span class="inline-block w-3 h-3 rounded-full bg-red-400"></span> Desaprobado (< 11)
    </span>
    <span class="flex items-center gap-1.5">
        <span class="inline-block w-3 h-3 rounded-full bg-gray-200"></span> Sin nota registrada
    </span>
    <span class="ml-auto">Escala: 0 – 20 puntos</span>
</div>

@endsection
