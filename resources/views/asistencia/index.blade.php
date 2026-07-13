@extends('layouts.app')
@section('title', 'Asistencia')
@section('page-title', 'Asistencia')

@push('styles')
<style>
/* ── Grilla mensual ────────────────────────────────────── */
.att-table { border-collapse: separate; border-spacing: 0; }
.att-table th, .att-table td { border-right: 1px solid #f3f4f6; }
.att-table tr > *:last-child { border-right: none; }

/* Sticky primera columna */
.col-sticky {
    position: sticky; left: 0; z-index: 2;
    background: white;
    border-right: 2px solid #e5e7eb !important;
    min-width: 180px; max-width: 220px;
}
thead .col-sticky { background: #f9fafb; z-index: 3; }

/* Celdas de día */
.day-cell { width: 34px; min-width: 34px; text-align: center; padding: 4px 2px; }
.day-head { width: 34px; min-width: 34px; text-align: center; padding: 6px 2px; }

/* Weekend */
.day-weekend { background: #fafafa; }
thead .day-weekend { background: #f3f4f6; }

/* Hoy */
.day-today { background: rgba(139,28,48,0.05) !important; }
thead .day-today { background: rgba(139,28,48,0.12) !important; }

/* Badges de estado */
.badge { display:inline-flex; align-items:center; justify-content:center;
         width:26px; height:22px; border-radius:5px; font-size:11px; font-weight:700; }
.badge-present   { background:#dcfce7; color:#15803d; }
.badge-absent    { background:#fee2e2; color:#b91c1c; }
.badge-justified { background:#dbeafe; color:#1d4ed8; }
.badge-late      { background:#fef9c3; color:#a16207; }
.badge-empty     { background:#f3f4f6; color:#d1d5db; font-size:10px; }

/* Resumen al final de fila */
.summary-cell { background:#f9fafb; border-left: 2px solid #e5e7eb !important; }
</style>
@endpush

@section('content')

{{-- ── Selector de hijo (solo para padres con más de un hijo) ───────── --}}
@include('partials.parent_child_selector', [
    'children'     => $children     ?? collect(),
    'selectedChild'=> $selectedChild ?? null,
])

{{-- ── Barra de filtros ────────────────────────────────────────────────── --}}
<div class="bg-white rounded shadow-sm border border-gray-100 p-5 mb-5">
    <form method="GET" action="{{ route('asistencia.index') }}" class="flex flex-wrap items-end gap-4">

        {{-- Preservar child_id al cambiar filtros --}}
        @if(auth()->user()->isParent() && ($selectedChild ?? null))
        <input type="hidden" name="child_id" value="{{ $selectedChild->id }}">
        @endif

        {{-- Año académico --}}
        @if(($years ?? collect())->count() > 1)
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Año</label>
            <select name="year" onchange="this.form.submit()"
                    class="border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm focus:outline-none"
                    onfocus="this.style.borderColor='#8b1c30'" onblur="this.style.borderColor=''">
                @foreach($years as $y)
                <option value="{{ $y }}" {{ (int)$selectedYear === (int)$y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Grado --}}
        @if(($grades ?? collect())->count() > 1)
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Grado</label>
            <select name="grado" onchange="this.form.submit()"
                    class="border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm focus:outline-none"
                    onfocus="this.style.borderColor='#8b1c30'" onblur="this.style.borderColor=''">
                <option value="">Todos</option>
                @foreach($grades as $g)
                <option value="{{ $g }}" {{ $selectedGrade === $g ? 'selected' : '' }}>{{ $g }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Sección --}}
        @if(($sections ?? collect())->count() > 1)
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Sección</label>
            <select name="section_id" onchange="this.form.submit()"
                    class="border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm focus:outline-none"
                    onfocus="this.style.borderColor='#8b1c30'" onblur="this.style.borderColor=''">
                <option value="">Todas</option>
                @foreach($sections as $sec)
                <option value="{{ $sec->id }}" {{ (int)($selectedSectionId ?? 0) === $sec->id ? 'selected' : '' }}>
                    {{ $sec->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        @if(auth()->user()->isStudent() || auth()->user()->isParent())
        {{-- ── Mes (alumno / padre) ─────────────────────────────────────── --}}
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Mes</label>
            <input type="month" name="month" value="{{ $month->format('Y-m') }}"
                   onchange="this.form.submit()"
                   class="border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm focus:outline-none"
                   onfocus="this.style.borderColor='#8b1c30'" onblur="this.style.borderColor=''">
        </div>
        @else
        {{-- ── Curso (dropdown para admin / docente) ────────────────────── --}}
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Curso</label>
            <select name="course_id" onchange="this.form.submit()"
                    class="border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm focus:outline-none"
                    onfocus="this.style.borderColor='#8b1c30'" onblur="this.style.borderColor=''">
                <option value="">— Seleccionar curso —</option>
                @foreach($courses as $c)
                <option value="{{ $c->id }}" {{ $selectedCourse?->id == $c->id ? 'selected' : '' }}>
                    {{ $c->name }}
                    @if($c->section) · {{ $c->section->name }} @endif
                </option>
                @endforeach
            </select>
        </div>

        {{-- Mes (admin / docente) --}}
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Mes</label>
            <input type="month" name="month" value="{{ $month->format('Y-m') }}"
                   onchange="this.form.submit()"
                   class="border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm focus:outline-none"
                   onfocus="this.style.borderColor='#8b1c30'" onblur="this.style.borderColor=''">
        </div>

        @if($selectedCourse)
        <div class="self-end">
            <span class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium"
                  style="background:#fce7eb; color:#8b1c30;">
                <i data-lucide="book-open" style="width:14px;height:14px;"></i>
                {{ $selectedCourse->name }}
                @if($selectedCourse->section) — {{ $selectedCourse->section->name }} @endif
            </span>
        </div>
        @endif
        @endif

        {{-- Botón de registrar (admin/docente) --}}
        @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
        <div class="self-end ml-auto">
            @php
                $regParams = $selectedCourse ? ['course_id' => $selectedCourse->id] : [];
                if (!empty($registerWeekStart)) $regParams['week_start'] = $registerWeekStart;
            @endphp
            <a href="{{ route('asistencia.create', $regParams) }}"
               class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm font-semibold shadow-sm transition"
               style="background: linear-gradient(135deg, #8b1c30, #6b1427);"
               onmouseover="this.style.background='linear-gradient(135deg,#a8183b,#8b1c30)'"
               onmouseout="this.style.background='linear-gradient(135deg,#8b1c30,#6b1427)'">
                <i data-lucide="edit-3" style="width:15px;height:15px;"></i>
                Registrar asistencia
            </a>
        </div>
        @endif
    </form>
</div>

{{-- ── Botones de curso (alumno / padre) ──────────────────────────────── --}}
@if((auth()->user()->isStudent() || auth()->user()->isParent()) && $courses->isNotEmpty())
@php
    $baseParams = array_filter([
        'month'    => $month->format('Y-m'),
        'child_id' => request('child_id'),
    ]);
@endphp
<div class="flex flex-wrap gap-2 mb-5">
    @foreach($courses->sortBy('name') as $c)
    @php $isActive = $selectedCourse?->id == $c->id; @endphp
    <a href="{{ route('asistencia.index', array_merge($baseParams, ['course_id' => $c->id])) }}"
       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border transition-all duration-150
              {{ $isActive
                  ? 'text-white border-transparent shadow-sm'
                  : 'bg-white text-gray-600 border-gray-200 hover:border-[#8b1c30] hover:text-[#8b1c30]' }}"
       style="{{ $isActive ? 'background:#8b1c30;' : '' }}">
        <i data-lucide="book-open" style="width:13px;height:13px;flex-shrink:0;
           {{ $isActive ? 'color:white;' : 'color:#8b1c30;' }}"></i>
        {{ $c->name }}
    </a>
    @endforeach
</div>
@endif

@if($selectedCourse && $students->count())

{{-- ── Cabecera del mes ─────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-3">
    <div>
        <h2 class="text-base font-bold text-gray-800">
            {{ ucfirst($month->isoFormat('MMMM [de] YYYY')) }}
        </h2>
        <p class="text-xs text-gray-400 mt-0.5">
            {{ $students->count() }} alumno(s) · {{ $selectedCourse->name }}
        </p>
    </div>

    {{-- Leyenda --}}
    <div class="flex items-center gap-2 text-xs">
        <span class="badge badge-present">✓</span><span class="text-gray-500">Presente</span>
        <span class="badge badge-absent ml-2">✗</span><span class="text-gray-500">Ausente</span>
        <span class="badge badge-justified ml-2">J</span><span class="text-gray-500">Justificado</span>
        <span class="badge badge-late ml-2">T</span><span class="text-gray-500">Tardanza</span>
        <span class="badge badge-empty ml-2">—</span><span class="text-gray-500">Sin registro</span>
    </div>
</div>

{{-- ── Grilla mensual ───────────────────────────────────────────────────── --}}
<div class="bg-white rounded shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="att-table w-full text-sm">
            <thead>
                <tr style="background:#f9fafb; border-bottom: 2px solid #e5e7eb;">

                    {{-- Columna nombre --}}
                    <th class="col-sticky px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        Nombres y Apellidos
                    </th>

                    {{-- Columnas de días --}}
                    @for($d = 1; $d <= $daysInMonth; $d++)
                    @php
                        $dayDate   = $monthStart->copy()->setDay($d);
                        $dow       = $dayDate->dayOfWeek; // 0=Sun, 6=Sat
                        $isWeekend = $dow === 0 || $dow === 6;
                        $isToday   = $dayDate->isToday();
                        $dayLabel  = ['D','L','M','M','J','V','S'][$dow];
                    @endphp
                    <th class="day-head {{ $isWeekend ? 'day-weekend' : '' }} {{ $isToday ? 'day-today' : '' }}"
                        title="{{ $dayDate->isoFormat('dddd D [de] MMMM') }}">
                        <div class="text-xs {{ $isToday ? 'font-extrabold' : 'font-semibold' }}
                                    {{ $isWeekend ? 'text-gray-300' : ($isToday ? '' : 'text-gray-500') }}"
                             style="{{ $isToday ? 'color:#8b1c30;' : '' }}">
                            {{ $dayLabel }}
                        </div>
                        <div class="text-sm font-bold {{ $isWeekend ? 'text-gray-300' : ($isToday ? '' : 'text-gray-700') }}"
                             style="{{ $isToday ? 'color:#6b1427; background:rgba(139,28,48,0.12); border-radius:50%; width:22px; height:22px; display:flex; align-items:center; justify-content:center; margin:0 auto;' : '' }}">
                            {{ $d }}
                        </div>
                    </th>
                    @endfor

                    {{-- Resumen --}}
                    <th class="summary-cell px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">P</th>
                    <th class="summary-cell px-3 py-3 text-center text-xs font-semibold text-red-400 uppercase tracking-wide whitespace-nowrap">A</th>
                    <th class="summary-cell px-3 py-3 text-center text-xs font-semibold text-blue-400 uppercase tracking-wide whitespace-nowrap">J</th>
                    <th class="summary-cell px-3 py-3 text-center text-xs font-semibold text-yellow-500 uppercase tracking-wide whitespace-nowrap">T</th>
                    <th class="summary-cell px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">%</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-50">
                @foreach($students as $i => $student)
                @php
                    $row      = $attendanceMatrix[$student->id] ?? [];
                    $cPresent = collect($row)->filter(fn($s) => $s === 'present')->count();
                    $cAbsent  = collect($row)->filter(fn($s) => $s === 'absent')->count();
                    $cJust    = collect($row)->filter(fn($s) => $s === 'justified')->count();
                    $cLate    = collect($row)->filter(fn($s) => $s === 'late')->count();
                @endphp
                <tr class="{{ $i % 2 === 0 ? '' : 'bg-gray-50/40' }} hover:bg-crimson-50/20 transition-colors"
                    style="height:38px;">

                    {{-- Nombre --}}
                    <td class="col-sticky px-4 py-2">
                        <div class="flex items-center gap-2.5">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                 style="background: linear-gradient(135deg,#8b1c30,#6b1427); font-size:10px;">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
                            </div>
                            <span class="font-medium text-gray-800 text-xs truncate">{{ $student->name }}</span>
                        </div>
                    </td>

                    {{-- Celda por día --}}
                    @for($d = 1; $d <= $daysInMonth; $d++)
                    @php
                        $dayDate   = $monthStart->copy()->setDay($d);
                        $dow       = $dayDate->dayOfWeek;
                        $isWeekend = $dow === 0 || $dow === 6;
                        $isToday   = $dayDate->isToday();
                        $status    = $row[$d] ?? null;
                    @endphp
                    <td class="day-cell {{ $isWeekend ? 'day-weekend' : '' }} {{ $isToday ? 'day-today' : '' }}">
                        @if($isWeekend)
                            {{-- Fin de semana --}}
                            <span class="text-gray-200 text-xs">·</span>
                        @elseif($status)
                            @php
                                $badgeMap = [
                                    'present'   => ['✓', 'badge-present'],
                                    'absent'    => ['✗', 'badge-absent'],
                                    'justified' => ['J',  'badge-justified'],
                                    'late'      => ['T',  'badge-late'],
                                ];
                                [$lbl, $cls] = $badgeMap[$status] ?? ['?', 'badge-empty'];
                            @endphp
                            @php $creatorName = $creatorMatrix[$student->id][$d] ?? null; @endphp
                            <span class="badge {{ $cls }}"
                                  title="{{ ucfirst($status) }}{{ $creatorName ? ' · ' . $creatorName : '' }}">{{ $lbl }}</span>
                        @else
                            {{-- Sin registro --}}
                            <span class="badge badge-empty">—</span>
                        @endif
                    </td>
                    @endfor

                    {{-- Totales fila --}}
                    <td class="summary-cell day-cell text-center">
                        <span class="text-xs font-bold text-green-700">{{ $cPresent ?: '—' }}</span>
                    </td>
                    <td class="summary-cell day-cell text-center">
                        <span class="text-xs font-bold {{ $cAbsent ? 'text-red-600' : 'text-gray-300' }}">{{ $cAbsent ?: '—' }}</span>
                    </td>
                    <td class="summary-cell day-cell text-center">
                        <span class="text-xs font-bold {{ $cJust ? 'text-blue-600' : 'text-gray-300' }}">{{ $cJust ?: '—' }}</span>
                    </td>
                    <td class="summary-cell day-cell text-center">
                        <span class="text-xs font-bold {{ $cLate ? 'text-yellow-600' : 'text-gray-300' }}">{{ $cLate ?: '—' }}</span>
                    </td>
                    <td class="summary-cell day-cell text-center">
                        @php
                            $rowTotal = $cPresent + $cAbsent + $cJust + $cLate;
                            $rowPct   = $rowTotal > 0 ? round(($cPresent + $cJust) / $rowTotal * 100) : null;
                        @endphp
                        @if($rowPct !== null)
                        <span class="text-xs font-bold {{ $rowPct >= 70 ? 'text-green-600' : 'text-red-600' }}">{{ $rowPct }}%</span>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>

            {{-- Totales columna --}}
            <tfoot>
                <tr style="border-top: 2px solid #e5e7eb; background:#f9fafb;">
                    <td class="col-sticky px-4 py-2 text-xs font-bold text-gray-500 uppercase tracking-wide">
                        Totales del día
                    </td>
                    @for($d = 1; $d <= $daysInMonth; $d++)
                    @php
                        $dayDate   = $monthStart->copy()->setDay($d);
                        $isWeekend = in_array($dayDate->dayOfWeek, [0, 6]);
                        $isToday   = $dayDate->isToday();
                        $dayTotal  = 0; $dayAbsent = 0;
                        foreach ($students as $st) {
                            $s = ($attendanceMatrix[$st->id][$d] ?? null);
                            if ($s === 'present' || $s === 'late') $dayTotal++;
                            if ($s === 'absent') $dayAbsent++;
                        }
                    @endphp
                    <td class="day-cell {{ $isWeekend ? 'day-weekend' : '' }} {{ $isToday ? 'day-today' : '' }} text-center py-2">
                        @if(!$isWeekend)
                            @if($dayTotal > 0 || $dayAbsent > 0)
                                <div class="text-xs font-bold text-green-700 leading-none">{{ $dayTotal }}</div>
                                @if($dayAbsent > 0)
                                <div class="text-xs font-bold text-red-500 leading-none">-{{ $dayAbsent }}</div>
                                @endif
                            @else
                                <span class="text-gray-200 text-xs">·</span>
                            @endif
                        @else
                            <span class="text-gray-200 text-xs">·</span>
                        @endif
                    </td>
                    @endfor
                    <td class="summary-cell day-cell text-center py-2">
                        <span class="text-xs font-bold text-green-700">
                            {{ collect($attendanceMatrix)->flatten()->filter(fn($s) => $s === 'present')->count() }}
                        </span>
                    </td>
                    <td class="summary-cell day-cell text-center py-2">
                        <span class="text-xs font-bold text-red-600">
                            {{ collect($attendanceMatrix)->flatten()->filter(fn($s) => $s === 'absent')->count() }}
                        </span>
                    </td>
                    <td class="summary-cell day-cell text-center py-2">
                        <span class="text-xs font-bold text-blue-600">
                            {{ collect($attendanceMatrix)->flatten()->filter(fn($s) => $s === 'justified')->count() }}
                        </span>
                    </td>
                    <td class="summary-cell day-cell text-center py-2">
                        <span class="text-xs font-bold text-yellow-600">
                            {{ collect($attendanceMatrix)->flatten()->filter(fn($s) => $s === 'late')->count() }}
                        </span>
                    </td>
                    <td class="summary-cell day-cell text-center py-2">
                        @php
                            $grandAll = collect($attendanceMatrix)->flatten()
                                ->filter(fn($s) => in_array($s, ['present','absent','late','justified']))->count();
                            $grandEff = collect($attendanceMatrix)->flatten()
                                ->filter(fn($s) => in_array($s, ['present','justified']))->count();
                            $grandPct = $grandAll > 0 ? round($grandEff / $grandAll * 100) : null;
                        @endphp
                        @if($grandPct !== null)
                        <span class="text-xs font-bold {{ $grandPct >= 70 ? 'text-green-600' : 'text-red-600' }}">{{ $grandPct }}%</span>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@elseif($selectedCourse)
<div class="bg-white rounded p-10 text-center text-gray-400 border border-gray-100 shadow-sm">
    <i data-lucide="users" style="width:40px;height:40px;" class="mx-auto mb-3 opacity-40"></i>
    <p class="font-medium">No hay alumnos matriculados en la sección de este curso.</p>
</div>

@else
{{-- Estado vacío: ningún curso seleccionado --}}
<div class="bg-white rounded p-12 text-center border border-gray-100 shadow-sm">
    <div class="w-16 h-16 rounded flex items-center justify-center mx-auto mb-4"
         style="background:#fce7eb;">
        <i data-lucide="clipboard-list" style="width:28px;height:28px;color:#8b1c30;"></i>
    </div>
    <h3 class="text-base font-semibold text-gray-700 mb-1">Selecciona un curso y un mes</h3>
    <p class="text-sm text-gray-400">Verás la grilla de asistencia completa del mes seleccionado.</p>
</div>
@endif

@endsection
