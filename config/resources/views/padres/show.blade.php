@extends('layouts.app')
@section('title', $alumno->name)
@section('page-title', 'Detalle de Alumno')

@section('content')
<div class="flex items-center justify-between mb-4">
    <a href="{{ route('padres.index') }}" class="flex items-center gap-1 text-indigo-600 hover:underline text-sm w-fit">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver al portal
    </a>
    <a href="{{ route('padres.show.pdf', $alumno) }}"
       class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
        <i data-lucide="file-down" class="w-4 h-4"></i> Descargar reporte PDF
    </a>
</div>

@if($atRisk)
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded flex items-center gap-3">
    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500 flex-shrink-0"></i>
    <p class="text-sm text-red-700 font-medium">Este alumno está en riesgo académico.</p>
</div>
@endif

{{-- ══════════════════════════════════════════════════
     SELECTOR DE HIJOS (visible solo si hay más de uno)
══════════════════════════════════════════════════ --}}
@if($siblings->count() > 1)
<div class="mb-5">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Mis hijos</p>
    <div class="flex flex-wrap gap-2">
        @foreach($siblings as $sib)
        @php
            $sibEnrollment = $sib->enrollments->sortByDesc(fn($e) => $e->section->year ?? 0)->first();
            $sibSection    = $sibEnrollment?->section;
            $isCurrent     = $sib->id === $alumno->id;
        @endphp
        <a href="{{ route('padres.show', $sib) }}"
           class="flex items-center gap-2.5 px-4 py-2.5 rounded border text-sm font-medium transition
               {{ $isCurrent
                   ? 'border-indigo-400 bg-indigo-50 text-indigo-700 shadow-sm'
                   : 'border-gray-200 bg-white text-gray-600 hover:border-indigo-300 hover:text-indigo-600' }}">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold
                {{ $isCurrent ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-500' }}">
                {{ strtoupper(substr($sib->name, 0, 1)) }}
            </span>
            <div class="text-left">
                <p class="leading-tight">{{ Str::words($sib->name, 2, '') }}</p>
                @if($sibSection)
                <p class="text-xs {{ $isCurrent ? 'text-indigo-400' : 'text-gray-400' }} leading-none mt-0.5">
                    {{ $sibSection->name }}
                </p>
                @endif
            </div>
            @if($isCurrent)
            <i data-lucide="check" class="w-3.5 h-3.5 text-indigo-500 ml-1"></i>
            @endif
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════
     HEADER CARD
══════════════════════════════════════════════════ --}}
<div class="bg-white rounded shadow-sm border border-gray-100 p-6 mb-6">
    <div class="flex items-center gap-4">
        <div class="w-16 h-16 bg-indigo-100 rounded flex items-center justify-center text-2xl font-bold text-indigo-600 flex-shrink-0">
            {{ strtoupper(substr($alumno->name, 0, 1)) }}
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-800">{{ $alumno->name }}</h2>
            <p class="text-sm text-gray-500">{{ $alumno->email }}</p>
            @if($currentSection)
            <span class="mt-1 px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded-full inline-block">
                {{ $currentSection->name }} · {{ $currentSection->year }}
            </span>
            @endif
        </div>
        @if($overallAvg !== null)
        <div class="ml-auto text-right">
            <p class="text-xs text-gray-500">Promedio Actual</p>
            <p class="text-4xl font-bold {{ $overallAvg >= 11 ? 'text-green-600' : 'text-red-600' }}">{{ $overallAvg }}</p>
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════
     TAB NAVIGATION
══════════════════════════════════════════════════ --}}
<div x-data="{ tab: 'notas' }" class="mb-6">
    <div class="flex gap-1 bg-gray-100 rounded p-1 mb-6 overflow-x-auto">
        @foreach([
            ['id'=>'notas',      'icon'=>'clipboard-list', 'label'=>'Notas'],
            ['id'=>'asistencia', 'icon'=>'check-square',   'label'=>'Asistencia'],
            ['id'=>'horario',    'icon'=>'calendar',       'label'=>'Horario'],
            ['id'=>'materiales', 'icon'=>'book-open',      'label'=>'Materiales'],
            ['id'=>'docentes',   'icon'=>'user-check',     'label'=>'Docentes'],
            ['id'=>'calendario', 'icon'=>'calendar-days',  'label'=>'Calendario'],
        ] as $t)
        <button @click="tab = '{{ $t['id'] }}'"
            :class="tab === '{{ $t['id'] }}' ? 'bg-white shadow text-indigo-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
            class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm transition whitespace-nowrap flex-shrink-0">
            <i data-lucide="{{ $t['icon'] }}" class="w-4 h-4"></i>
            {{ $t['label'] }}
        </button>
        @endforeach
    </div>

    {{-- ── TAB: NOTAS ────────────────────────────────────────────────── --}}
    <div x-show="tab === 'notas'" x-transition>

        {{-- Cabecera del año actual --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
                <h3 class="text-base font-bold text-gray-800">Calificaciones año actual</h3>
                @if($currentSection)
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $currentSection->name }} · Año {{ $currentSection->year }}
                </p>
                @endif
            </div>
            @if($overallAvg !== null)
            <div class="flex items-center gap-2 px-4 py-2.5 rounded border
                {{ $overallAvg >= 11 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                <i data-lucide="{{ $overallAvg >= 11 ? 'trending-up' : 'trending-down' }}"
                   class="w-4 h-4 {{ $overallAvg >= 11 ? 'text-green-500' : 'text-red-500' }}"></i>
                <div>
                    <p class="text-xs {{ $overallAvg >= 11 ? 'text-green-600' : 'text-red-600' }}">Promedio general</p>
                    <p class="text-xl font-extrabold {{ $overallAvg >= 11 ? 'text-green-700' : 'text-red-700' }}">
                        {{ $overallAvg }}<span class="text-xs font-normal text-gray-400"> / 20</span>
                    </p>
                </div>
            </div>
            @endif
        </div>

        {{-- Tabla año actual --}}
        @if($currentCourses->isEmpty())
        <div class="bg-white rounded border border-gray-100 shadow-sm p-12 text-center mb-6">
            <i data-lucide="clipboard-list" class="w-10 h-10 mx-auto mb-3 text-gray-300"></i>
            <p class="text-gray-500">Sin calificaciones registradas.</p>
        </div>
        @else
        <div class="bg-white rounded shadow-sm border border-gray-100 overflow-hidden mb-6">
            @include('calificaciones._grade_table', [
                'courses'     => $currentCourses,
                'gradeMatrix' => $gradeMatrix,
                'periods'     => $periods,
                'overallAvg'  => $overallAvg,
            ])
        </div>
        @endif

        {{-- Historial académico --}}
        @if(!empty($history))
        <div class="mt-6">
            <div class="flex items-center gap-3 mb-3">
                <i data-lucide="history" class="w-5 h-5 text-gray-400"></i>
                <h4 class="text-sm font-bold text-gray-700">Historial académico</h4>
                <span class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-500 rounded-full">
                    {{ count($history) }} año(s) anterior(es)
                </span>
            </div>
            <div class="space-y-3">
                @foreach($history as $idx => $hist)
                @php
                    $histSec  = $hist['section'];
                    $histAvg  = $hist['overallAvg'];
                    $approved = $histAvg !== null && $histAvg >= 11;
                @endphp
                <div x-data="{ open: {{ $idx === 0 ? 'true' : 'false' }} }"
                     class="bg-white rounded border border-gray-100 shadow-sm overflow-hidden">
                    <button @click="open = !open"
                            class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-11 h-11 rounded text-xs font-bold text-white flex-shrink-0"
                                  style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
                                {{ $histSec->year }}
                            </span>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">
                                    {{ $histSec->name }}
                                </p>
                                <p class="text-xs text-gray-400">Año escolar {{ $histSec->year }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($histAvg !== null)
                            <div class="text-right hidden sm:block">
                                <p class="text-xs text-gray-400">Promedio</p>
                                <p class="text-base font-extrabold {{ $approved ? 'text-green-600' : 'text-red-600' }}">{{ $histAvg }}</p>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold hidden sm:inline-flex
                                {{ $approved ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $approved ? 'Promovido' : 'Observado' }}
                            </span>
                            @endif
                            <i data-lucide="chevron-down"
                               class="w-4 h-4 text-gray-400 transition-transform duration-200"
                               :class="open ? 'rotate-180' : ''"></i>
                        </div>
                    </button>
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
            <div class="mt-4 flex flex-wrap gap-4 text-xs text-gray-400">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-400 inline-block"></span>Aprobado (≥ 11)</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-400 inline-block"></span>Desaprobado (< 11)</span>
                <span class="ml-auto">Escala: 0 – 20 puntos</span>
            </div>
        </div>
        @endif
    </div>

    {{-- ── TAB: ASISTENCIA ───────────────────────────────────────────── --}}
    <div x-show="tab === 'asistencia'" x-transition>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-green-50 border border-green-100 rounded p-4 text-center">
                <p class="text-3xl font-bold text-green-600">{{ $present }}</p>
                <p class="text-xs text-green-700 mt-1">Presente</p>
            </div>
            <div class="bg-red-50 border border-red-100 rounded p-4 text-center">
                <p class="text-3xl font-bold text-red-500">{{ $absent }}</p>
                <p class="text-xs text-red-700 mt-1">Ausente</p>
            </div>
            <div class="bg-yellow-50 border border-yellow-100 rounded p-4 text-center">
                <p class="text-3xl font-bold text-yellow-600">{{ $late }}</p>
                <p class="text-xs text-yellow-700 mt-1">Tardanza</p>
            </div>
            <div class="bg-blue-50 border border-blue-100 rounded p-4 text-center">
                <p class="text-3xl font-bold text-blue-600">{{ $justified }}</p>
                <p class="text-xs text-blue-700 mt-1">Justificado</p>
            </div>
        </div>
        <div class="bg-white rounded shadow-sm border border-gray-100 p-5 mb-4 flex items-center gap-4">
            <div class="flex-1">
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">Porcentaje de asistencia</span>
                    <span class="font-bold {{ $attPct >= 70 ? 'text-green-600' : 'text-red-600' }}">{{ $attPct }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    <div class="h-2.5 rounded-full {{ $attPct >= 70 ? 'bg-green-500' : 'bg-red-500' }}" style="width: {{ $attPct }}%"></div>
                </div>
            </div>
        </div>
        {{-- Cuadrícula semanal por curso, igual estructura que el Horario ────── --}}
        @php
            $dayLabels   = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Miércoles','jueves'=>'Jueves','viernes'=>'Viernes'];
            $attStatusMap = [
                'present'   => ['label'=>'Presente',    'short'=>'P', 'class'=>'bg-green-50 border-green-200 text-green-700'],
                'absent'    => ['label'=>'Ausente',     'short'=>'F', 'class'=>'bg-red-50 border-red-300 text-red-700'],
                'late'      => ['label'=>'Tardanza',    'short'=>'T', 'class'=>'bg-yellow-50 border-yellow-300 text-yellow-700'],
                'justified' => ['label'=>'Justificado', 'short'=>'J', 'class'=>'bg-blue-50 border-blue-300 text-blue-700'],
            ];
        @endphp
        <div class="bg-white rounded shadow-sm border border-gray-100 mb-4">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800 text-sm">Asistencia de la semana</h3>
                <div class="flex items-center gap-2">
                    @if($hasPrevAttendance)
                    <a href="{{ route('padres.show', ['alumno' => $alumno, 'semana' => $weekStart->copy()->subWeek()->toDateString()]) }}"
                       class="p-1.5 rounded hover:bg-gray-100 text-gray-500">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </a>
                    @else
                    <span class="p-1.5 text-gray-200"><i data-lucide="chevron-left" class="w-4 h-4"></i></span>
                    @endif
                    <span class="text-xs font-medium text-gray-600 whitespace-nowrap">
                        {{ $weekStart->format('d/m') }} – {{ $weekEnd->format('d/m/Y') }}
                    </span>
                    @if($hasNextAttendance)
                    <a href="{{ route('padres.show', ['alumno' => $alumno, 'semana' => $weekStart->copy()->addWeek()->toDateString()]) }}"
                       class="p-1.5 rounded hover:bg-gray-100 text-gray-500">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                    @else
                    <span class="p-1.5 text-gray-200"><i data-lucide="chevron-right" class="w-4 h-4"></i></span>
                    @endif
                </div>
            </div>

            @php $hasAnySlot = collect($scheduleGrid)->flatten()->filter()->isNotEmpty(); @endphp
            @if(!$hasAnySlot)
            <div class="p-12 text-center text-gray-400">
                <i data-lucide="check-square" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
                <p>No hay horario registrado para esta sección.</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse min-w-[600px]">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-3 py-3 text-gray-500 font-medium border-b border-gray-100 w-20">Hora</th>
                            @foreach($days as $day)
                            <th class="px-2 py-3 text-gray-700 font-semibold border-b border-gray-100 text-center">{{ $dayLabels[$day] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($times as $time)
                        @if($receso = ($recesos[$time] ?? null))
                        <tr class="bg-amber-50/60">
                            <td class="px-3 py-2 text-xs text-amber-500 font-medium border-r border-amber-100 whitespace-nowrap">{{ $time }}</td>
                            <td colspan="{{ count($days) }}" class="px-3 py-2 text-center">
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-600 tracking-wide uppercase">
                                    🍎 Recreo · {{ $time }} – {{ $receso['fin'] }}
                                </span>
                            </td>
                        </tr>
                        @else
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-3 py-2.5 text-gray-400 font-mono font-medium border-r border-gray-100">{{ $time }}</td>
                            @foreach($days as $day)
                            <td class="px-1.5 py-1.5 text-center">
                                @php $slot = $scheduleGrid[$day][$time] ?? null; @endphp
                                @if(!$slot)
                                <span class="text-gray-100">·</span>
                                @else
                                @php $att = $attendanceWeekGrid[$day][$time] ?? null; @endphp
                                @if($att)
                                @php $s = $attStatusMap[$att->status] ?? ['label'=>$att->status,'short'=>'?','class'=>'bg-gray-50 border-gray-200 text-gray-600']; @endphp
                                <span title="{{ $slot->course->name }} · {{ $s['label'] }} · {{ \Carbon\Carbon::parse($att->date)->format('d/m/Y') }}"
                                      class="inline-flex flex-col items-center justify-center w-14 h-9 rounded-lg border text-[9px] font-bold leading-tight {{ $s['class'] }}">
                                    <span class="truncate max-w-[52px]">{{ Str::limit($slot->course->name, 8, '') }}</span>
                                    <span>{{ $s['short'] }}</span>
                                </span>
                                @else
                                <span title="{{ $slot->course->name }} · Sin registrar"
                                      class="inline-flex flex-col items-center justify-center w-14 h-9 rounded-lg border border-dashed border-gray-200 text-gray-400 text-[9px] leading-tight">
                                    <span class="truncate max-w-[52px]">{{ Str::limit($slot->course->name, 8, '') }}</span>
                                    <span>—</span>
                                </span>
                                @endif
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap gap-4 text-[11px] text-gray-400 px-5 py-3 border-t border-gray-50">
                <span class="flex items-center gap-1.5"><span class="w-4 h-4 rounded bg-green-50 border border-green-200 inline-flex items-center justify-center text-green-700 font-bold text-[9px]">P</span>Presente</span>
                <span class="flex items-center gap-1.5"><span class="w-4 h-4 rounded bg-red-50 border border-red-300 inline-flex items-center justify-center text-red-700 font-bold text-[9px]">F</span>Falta</span>
                <span class="flex items-center gap-1.5"><span class="w-4 h-4 rounded bg-yellow-50 border border-yellow-300 inline-flex items-center justify-center text-yellow-700 font-bold text-[9px]">T</span>Tardanza</span>
                <span class="flex items-center gap-1.5"><span class="w-4 h-4 rounded bg-blue-50 border border-blue-300 inline-flex items-center justify-center text-blue-700 font-bold text-[9px]">J</span>Justificado</span>
                <span class="border border-dashed border-gray-300 px-1 rounded text-gray-400">—</span>
                <span>Con clase, sin registrar aún</span>
                <span class="text-gray-200">·</span>
                <span>Sin clase esa hora</span>
            </div>
            @endif
        </div>

        {{-- Resumen de lo que necesita atención esa semana ────────────────────── --}}
        @if($weekAlerts->isNotEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded overflow-hidden">
            <div class="px-5 py-3 border-b border-amber-100 flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600"></i>
                <h3 class="font-semibold text-amber-800 text-sm">Para revisar esta semana</h3>
            </div>
            <div class="divide-y divide-amber-100">
                @foreach($weekAlerts as $alert)
                @php $s = $attStatusMap[$alert->status] ?? ['label'=>$alert->status,'class'=>'bg-gray-100 text-gray-700']; @endphp
                <div class="px-5 py-2.5 flex items-center justify-between text-sm bg-white/50">
                    <div class="flex items-center gap-3">
                        <span class="text-gray-500 text-xs w-28">{{ $alert->dayLabel }}, {{ \Carbon\Carbon::parse($alert->date)->format('d/m') }}</span>
                        <span class="text-gray-700">{{ $alert->course->name }}</span>
                    </div>
                    <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $s['class'] }}">{{ $s['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="bg-green-50 border border-green-100 rounded p-4 flex items-center gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-green-600 flex-shrink-0"></i>
            <p class="text-sm text-green-700">Sin faltas, tardanzas ni justificaciones esta semana.</p>
        </div>
        @endif
    </div>

    {{-- ── TAB: HORARIO ──────────────────────────────────────────────── --}}
    <div x-show="tab === 'horario'" x-transition>
        @php
            $dayLabels = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Miércoles','jueves'=>'Jueves','viernes'=>'Viernes'];
            $hasSlots  = collect($scheduleGrid)->flatten()->filter()->isNotEmpty();
        @endphp
        @if($hasSlots)
        <div class="bg-white rounded shadow-sm border border-gray-100 overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-3 py-3 text-gray-500 font-medium border-b border-gray-100 w-20">Hora</th>
                        @foreach($days as $day)
                        <th class="px-3 py-3 text-gray-700 font-semibold border-b border-gray-100 text-center">{{ $dayLabels[$day] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($times as $time)
                    @if($receso = ($recesos[$time] ?? null))
                    <tr class="bg-amber-50/60">
                        <td class="px-3 py-2 text-xs text-amber-500 font-medium border-r border-amber-100 whitespace-nowrap">{{ $time }}</td>
                        <td colspan="{{ count($days) }}" class="px-3 py-2 text-center">
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-600 tracking-wide uppercase">
                                🍎 Recreo · {{ $time }} – {{ $receso['fin'] }}
                            </span>
                        </td>
                    </tr>
                    @else
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-3 py-2.5 text-gray-400 font-mono font-medium border-r border-gray-100">{{ $time }}</td>
                        @foreach($days as $day)
                        <td class="px-2 py-2 text-center">
                            @if($slot = $scheduleGrid[$day][$time] ?? null)
                            <div class="bg-indigo-50 border border-indigo-100 rounded-lg px-2 py-1.5 text-left">
                                <p class="font-semibold text-indigo-800 leading-tight">{{ $slot->course->name }}</p>
                                @if($slot->classroom)
                                <p class="text-indigo-400 text-[10px] mt-0.5">{{ $slot->classroom }}</p>
                                @endif
                            </div>
                            @else
                            <span class="text-gray-100">—</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="bg-white rounded p-12 text-center text-gray-400 border border-gray-100">
            <i data-lucide="calendar" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
            <p>No hay horario registrado para esta sección.</p>
        </div>
        @endif
    </div>

    {{-- ── TAB: MATERIALES ───────────────────────────────────────────── --}}
    <div x-show="tab === 'materiales'" x-transition>
        @if($materials->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($materials as $mat)
            @php
                $iconMap  = ['document'=>'file-text','video'=>'play-circle','link'=>'link'];
                $colorMap = ['document'=>'blue','video'=>'purple','link'=>'green'];
                $icon  = $iconMap[$mat->type]  ?? 'file';
                $color = $colorMap[$mat->type] ?? 'gray';
            @endphp
            <div class="bg-white rounded shadow-sm border border-gray-100 p-5 flex flex-col gap-3">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 bg-{{ $color }}-100 rounded flex items-center justify-center flex-shrink-0">
                        <i data-lucide="{{ $icon }}" class="w-5 h-5 text-{{ $color }}-600"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-800 text-sm leading-snug">{{ $mat->title }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $mat->course->name }} · {{ $mat->teacher->name ?? '—' }}</p>
                    </div>
                </div>
                @if($mat->description)
                <p class="text-xs text-gray-500 leading-relaxed">{{ Str::limit($mat->description, 100) }}</p>
                @endif
                @if($mat->url)
                <a href="{{ $mat->url }}" target="_blank"
                   class="mt-auto flex items-center gap-1.5 text-xs text-indigo-600 hover:underline font-medium">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Abrir material
                </a>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <div class="bg-white rounded p-12 text-center text-gray-400 border border-gray-100">
            <i data-lucide="book-open" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
            <p>No hay materiales publicados para esta sección.</p>
        </div>
        @endif
    </div>

    {{-- ── TAB: DOCENTES ─────────────────────────────────────────────── --}}
    <div x-show="tab === 'docentes'" x-transition>
        @if($teachers->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($teachers as $teacher)
            <div class="bg-white rounded shadow-sm border border-gray-100 p-5 flex items-start gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded flex items-center justify-center text-lg font-bold text-purple-600 flex-shrink-0">
                    {{ strtoupper(substr($teacher['name'], 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-gray-800 text-sm">{{ $teacher['name'] }}</p>
                    <p class="text-xs text-indigo-600 font-medium mb-2">{{ $teacher['course'] }}</p>
                    @if($teacher['especialidad'])
                    <p class="text-xs text-gray-500 mb-1">
                        <span class="font-medium">Especialidad:</span> {{ $teacher['especialidad'] }}
                    </p>
                    @endif
                    <a href="mailto:{{ $teacher['email'] }}"
                       class="flex items-center gap-1 text-xs text-gray-500 hover:text-indigo-600 transition">
                        <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                        {{ $teacher['email'] }}
                    </a>
                    @if($teacher['phone'])
                    <p class="flex items-center gap-1 text-xs text-gray-500 mt-1">
                        <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                        {{ $teacher['phone'] }}
                    </p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="bg-white rounded p-12 text-center text-gray-400 border border-gray-100">
            <i data-lucide="user-check" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
            <p>No se encontraron docentes para esta sección.</p>
        </div>
        @endif
    </div>

    {{-- ── TAB: CALENDARIO ──────────────────────────────────────────── --}}
    <div x-show="tab === 'calendario'" x-transition>
        @if($calendarEvents->isNotEmpty())
        @php
            $upcoming = $calendarEvents->filter(fn($e) => !$e->isPast());
            $past     = $calendarEvents->filter(fn($e) =>  $e->isPast());
        @endphp
        @if($upcoming->isNotEmpty())
        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">Próximos eventos</h3>
        <div class="space-y-3 mb-6">
            @foreach($upcoming as $event)
            <div class="bg-white rounded shadow-sm border border-gray-100 p-4 flex items-start gap-4">
                <div class="flex-shrink-0 text-center bg-indigo-50 border border-indigo-100 rounded px-3 py-2 min-w-[56px]">
                    <p class="text-xs text-indigo-500 font-medium uppercase">{{ $event->event_date->format('M') }}</p>
                    <p class="text-2xl font-bold text-indigo-700 leading-none">{{ $event->event_date->format('d') }}</p>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-gray-800 text-sm">{{ $event->title }}</p>
                    @if($event->description)
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $event->description }}</p>
                    @endif
                    <span class="mt-2 inline-block px-2 py-0.5 text-xs rounded-full font-medium {{ $event->targetRoleClass() }}">
                        {{ $event->targetRoleLabel() }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
        @endif
        @if($past->isNotEmpty())
        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">Eventos pasados</h3>
        <div class="space-y-2">
            @foreach($past->take(5) as $event)
            <div class="bg-gray-50 rounded border border-gray-100 p-4 flex items-center gap-4 opacity-70">
                <div class="flex-shrink-0 text-center bg-gray-100 rounded px-3 py-2 min-w-[56px]">
                    <p class="text-xs text-gray-500 font-medium uppercase">{{ $event->event_date->format('M') }}</p>
                    <p class="text-2xl font-bold text-gray-600 leading-none">{{ $event->event_date->format('d') }}</p>
                </div>
                <p class="font-medium text-gray-600 text-sm">{{ $event->title }}</p>
            </div>
            @endforeach
        </div>
        @endif
        @else
        <div class="bg-white rounded p-12 text-center text-gray-400 border border-gray-100">
            <i data-lucide="calendar-days" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
            <p>No hay eventos programados.</p>
        </div>
        @endif
    </div>

</div>
@endsection
