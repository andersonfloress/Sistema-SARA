@extends('layouts.app')
@section('title', 'Registrar Asistencia')
@section('page-title', 'Registrar Asistencia')

@push('styles')
<style>
/* ── Celda de estado ──────────────────────────────────── */
.cell-btn {
    width: 52px; height: 36px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 8px; font-size: 13px; font-weight: 700;
    cursor: pointer; border: 2px solid transparent;
    transition: all .15s ease; user-select: none;
}
.cell-btn:active { transform: scale(.92); }

/* Estados */
.cell-blank     { background:#f3f4f6; color:#9ca3af; border-color:#e5e7eb; }
.cell-blank:hover { background:#e5e7eb; }
.cell-present   { background:#dcfce7; color:#15803d; border-color:#86efac; }
.cell-present:hover { background:#bbf7d0; }
.cell-absent    { background:#fee2e2; color:#b91c1c; border-color:#fca5a5; }
.cell-absent:hover  { background:#fecaca; }
.cell-justified { background:#dbeafe; color:#1d4ed8; border-color:#93c5fd; }
.cell-justified:hover { background:#bfdbfe; }
.cell-late      { background:#fef9c3; color:#a16207; border-color:#fde047; }
.cell-late:hover { background:#fef08a; }

/* Días futuros */
.cell-future { background:#f9fafb; color:#d1d5db; cursor: not-allowed; border-color:#f3f4f6; }

/* Columna de hoy */
.col-today { background: rgba(139,28,48,0.04); }
thead .col-today { background: rgba(139,28,48,0.08); }

/* Sticky primera columna */
.tbl-name { position: sticky; left: 0; background: white; z-index: 2; }
thead .tbl-name { background: #f9fafb; z-index: 3; }
</style>
@endpush

@section('content')

{{-- ── Selección de curso ───────────────────────────────────────────────── --}}
<div class="bg-white rounded shadow-sm border border-gray-100 p-5 mb-5">
    <form method="GET" action="{{ route('asistencia.create') }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Curso</label>
            <select name="course_id" onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:outline-none"
                    onfocus="this.style.borderColor='#8b1c30'" onblur="this.style.borderColor=''">
                <option value="">— Seleccionar curso —</option>
                @foreach($courses as $c)
                <option value="{{ $c->id }}" {{ $selectedCourse?->id == $c->id ? 'selected' : '' }}>
                    {{ $c->name }} · {{ $c->section->name }}
                </option>
                @endforeach
            </select>
        </div>

        {{-- Semana oculta para que se mantenga al cambiar curso --}}
        @if(request('week_start'))
        <input type="hidden" name="week_start" value="{{ request('week_start') }}">
        @endif
    </form>
</div>

@if($selectedCourse)

{{-- ── Navegación de semana ─────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-3">
        {{-- Semana anterior --}}
        <a href="{{ route('asistencia.create', ['course_id' => $selectedCourse->id, 'week_start' => $weekStart->copy()->subWeek()->toDateString()]) }}"
           class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition shadow-sm">
            <i data-lucide="chevron-left" style="width:16px;height:16px;"></i> Semana anterior
        </a>

        {{-- Label de semana --}}
        <div class="px-4 py-2 bg-white border border-gray-200 rounded-lg shadow-sm text-sm font-semibold text-gray-800">
            {{ $weekStart->isoFormat('D MMM') }} — {{ $weekEnd->isoFormat('D [de] MMMM, YYYY') }}
            @if($weekStart->isSameWeek($today))
                <span class="ml-2 px-2 py-0.5 text-xs rounded-full font-semibold"
                      style="background:#fce7eb;color:#8b1c30;">Semana actual</span>
            @endif
        </div>

        {{-- Semana siguiente (solo si no es la actual) --}}
        @if($weekStart->lt($currentWeekStart))
        <a href="{{ route('asistencia.create', ['course_id' => $selectedCourse->id, 'week_start' => $weekStart->copy()->addWeek()->toDateString()]) }}"
           class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition shadow-sm">
            Semana siguiente <i data-lucide="chevron-right" style="width:16px;height:16px;"></i>
        </a>
        @endif

        {{-- Ir a semana actual (solo si no estamos ya en ella) --}}
        @if(!$weekStart->isSameWeek($today))
        <a href="{{ route('asistencia.create', ['course_id' => $selectedCourse->id]) }}"
           class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-white shadow-sm transition"
           style="background:#4f46e5;"
           onmouseover="this.style.background='#4338ca'"
           onmouseout="this.style.background='#4f46e5'">
            <i data-lucide="calendar-check" style="width:16px;height:16px;"></i> Semana actual
        </a>
        @endif
    </div>

    {{-- Botón marcar todos presente HOY --}}
    @php $todayStr = $today->toDateString(); @endphp
    @if($weekStart->lte($today) && $weekEnd->gte($today))
    <button type="button" onclick="markAllToday()"
            class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white shadow-sm transition"
            style="background: linear-gradient(135deg, #8b1c30, #6b1427);"
            onmouseover="this.style.background='linear-gradient(135deg,#a8183b,#8b1c30)'"
            onmouseout="this.style.background='linear-gradient(135deg,#8b1c30,#6b1427)'">
        <i data-lucide="check-circle-2" style="width:16px;height:16px;"></i>
        Marcar todos presentes hoy
    </button>
    @endif
</div>

{{-- Aviso: curso sin horario registrado --}}
@if($weekDays->isEmpty())
<div class="bg-amber-50 border border-amber-200 rounded-lg px-5 py-4 mb-4 flex items-start gap-3">
    <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5"></i>
    <div>
        <p class="text-sm font-semibold text-amber-800">Este curso no tiene horario registrado</p>
        <p class="text-xs text-amber-600 mt-1">
            Configura los días de clase en
            <a href="{{ route('horarios.index') }}" class="underline font-medium">Horarios</a>
            para poder tomar asistencia.
        </p>
    </div>
</div>
@elseif($students->count())

<form method="POST" action="{{ route('asistencia.store') }}" id="attForm">
    @csrf
    <input type="hidden" name="course_id"  value="{{ $selectedCourse->id }}">
    <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">

    <div class="bg-white rounded shadow-sm border border-gray-100 overflow-hidden">

        {{-- ── Leyenda ──────────────────────────────────────────────────── --}}
        <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center gap-4 text-xs">
            <span class="text-gray-400 font-medium">Clic en celda para cambiar estado:</span>
            <span class="flex items-center gap-1"><span class="cell-btn cell-blank" style="width:36px;height:28px;font-size:11px;">—</span> Sin registro</span>
            <span class="flex items-center gap-1"><span class="cell-btn cell-present" style="width:36px;height:28px;font-size:11px;">✓</span> Presente</span>
            <span class="flex items-center gap-1"><span class="cell-btn cell-absent" style="width:36px;height:28px;font-size:11px;">✗</span> Ausente</span>
            <span class="flex items-center gap-1"><span class="cell-btn cell-justified" style="width:36px;height:28px;font-size:11px;">J</span> Justificado</span>
            <span class="flex items-center gap-1"><span class="cell-btn cell-late" style="width:36px;height:28px;font-size:11px;">T</span> Tardanza</span>
        </div>

        {{-- ── Tabla ────────────────────────────────────────────────────── --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        {{-- Columna nombre --}}
                        <th class="tbl-name px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide border-r border-gray-100" style="min-width:200px;">
                            Nombres y Apellidos
                        </th>

                        {{-- Columnas de días --}}
                        @foreach($weekDays as $day)
                        @php
                            $dateStr   = $day->toDateString();
                            $isFuture  = $day->gt($today);
                            $isToday   = $day->isSameDay($today);
                            $feriado   = $feriadosByDate[$dateStr] ?? null;
                        @endphp
                        <th class="px-2 py-3 text-center {{ $isToday ? 'col-today' : '' }}"
                            style="min-width:64px;{{ $feriado ? 'background:#fef9c3;' : '' }}"
                            title="{{ $feriado ? $feriado->title : '' }}">
                            <div class="text-xs font-semibold {{ $isFuture ? 'text-gray-300' : ($isToday ? 'text-crimson-700' : 'text-gray-600') }}"
                                 style="{{ $isToday ? 'color:#8b1c30;' : '' }}">
                                {{ $day->isoFormat('ddd') }}
                            </div>
                            <div class="text-lg font-bold {{ $isFuture ? 'text-gray-300' : ($isToday ? 'text-crimson-800' : 'text-gray-800') }}"
                                 style="{{ $isToday ? 'color:#6b1427;' : '' }}">
                                {{ $day->format('d') }}
                            </div>
                            @if($isToday)
                            <div class="w-1.5 h-1.5 rounded-full mx-auto mt-0.5" style="background:#8b1c30;"></div>
                            @endif
                            @if($feriado)
                            <div class="text-[9px] font-semibold text-amber-600 mt-0.5 leading-tight truncate px-1"
                                 style="max-width:60px;">E</div>
                            @endif
                        </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @foreach($students as $i => $student)
                    <tr class="hover:bg-gray-50/60 transition-colors">

                        {{-- Nombre --}}
                        <td class="tbl-name px-5 py-3 border-r border-gray-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                     style="background: linear-gradient(135deg, #8b1c30, #6b1427);">
                                    {{ strtoupper(substr($student->name, 0, 1)) }}
                                </div>
                                <div>
                                    <span class="font-medium text-gray-800">{{ $student->name }}</span>
                                    @if(($annualAbsences[$student->id] ?? 0) > 0)
                                    <span class="ml-1 text-[10px] font-bold text-red-500"
                                          title="Faltas acumuladas en este curso">
                                        {{ $annualAbsences[$student->id] }}F
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Celdas por día --}}
                        @foreach($weekDays as $day)
                        @php
                            $dateStr  = $day->toDateString();
                            $isFuture = $day->gt($today);
                            $isToday  = $day->isSameDay($today);
                            $status   = $attendanceMatrix[$student->id][$dateStr] ?? '';
                            $cellId   = 'cell_' . $student->id . '_' . str_replace('-', '', $dateStr);
                        @endphp
                        <td class="px-2 py-2 text-center {{ $isToday ? 'col-today' : '' }}">

                            @if($isFuture)
                                {{-- Día futuro: bloqueado --}}
                                <div class="cell-btn cell-future mx-auto">—</div>

                            @elseif($feriado)
                                {{-- Evento/feriado: bloqueado, no se registra asistencia --}}
                                <div class="cell-btn mx-auto"
                                     style="background:#fef9c3;color:#a16207;border-color:#fde047;cursor:default;font-size:11px;"
                                     title="{{ $feriado->title }}">
                                    E
                                </div>

                            @else
                                {{-- Input oculto --}}
                                <input type="hidden"
                                       id="{{ $cellId }}"
                                       name="attendance[{{ $student->id }}][{{ $dateStr }}]"
                                       value="{{ $status }}">

                                {{-- Botón visual --}}
                                <div class="cell-btn {{ $status ? 'cell-'.$status : 'cell-blank' }} mx-auto"
                                     data-cell="{{ $cellId }}"
                                     data-future="0"
                                     onclick="cycleCell(this)">
                                    {{ $status === 'present' ? '✓' : ($status === 'absent' ? '✗' : ($status === 'justified' ? 'J' : ($status === 'late' ? 'T' : '—'))) }}
                                </div>
                            @endif

                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ── Acciones ─────────────────────────────────────────────────── --}}
        <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
            <a href="{{ route('asistencia.index') }}"
               class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                ← Volver al listado
            </a>

            <button type="submit"
                    class="flex items-center gap-2 px-6 py-2.5 text-white rounded-lg text-sm font-semibold shadow-sm transition"
                    style="background: linear-gradient(135deg, #8b1c30, #6b1427);"
                    onmouseover="this.style.background='linear-gradient(135deg,#a8183b,#8b1c30)'"
                    onmouseout="this.style.background='linear-gradient(135deg,#8b1c30,#6b1427)'">
                <i data-lucide="save" style="width:16px;height:16px;"></i>
                Guardar asistencia
            </button>
        </div>
    </div>
</form>

@else
<div class="bg-white rounded p-10 text-center text-gray-400 border border-gray-100 shadow-sm">
    <i data-lucide="users" style="width:40px;height:40px;" class="mx-auto mb-3 opacity-40"></i>
    <p class="font-medium">No hay alumnos matriculados en la sección de este curso.</p>
</div>
@endif

@else
<div class="bg-white rounded p-10 text-center text-gray-400 border border-gray-100 shadow-sm">
    <i data-lucide="clipboard-list" style="width:40px;height:40px;" class="mx-auto mb-3 opacity-40"></i>
    <p class="font-medium">Selecciona un curso para ver la grilla de asistencia.</p>
</div>
@endif

@endsection

@push('scripts')
<script>
// Orden de ciclo: '' → present → absent → justified → late → ''
const CYCLE   = ['', 'present', 'absent', 'justified', 'late'];
const LABELS  = { '': '—', present: '✓', absent: '✗', justified: 'J', late: 'T' };
const CLASSES = {
    '':          'cell-blank',
    present:     'cell-present',
    absent:      'cell-absent',
    justified:   'cell-justified',
    late:        'cell-late',
};

function cycleCell(btn) {
    const input   = document.getElementById(btn.dataset.cell);
    const current = CYCLE.indexOf(input.value);
    const next    = CYCLE[(current + 1) % CYCLE.length];

    // Actualizar input oculto
    input.value = next;

    // Actualizar visual
    CYCLE.forEach(s => btn.classList.remove(CLASSES[s]));
    btn.classList.add(CLASSES[next]);
    btn.textContent = LABELS[next];
}

function setCell(btn, status) {
    const input = document.getElementById(btn.dataset.cell);
    if (!input) return;
    input.value = status;
    CYCLE.forEach(s => btn.classList.remove(CLASSES[s]));
    btn.classList.add(CLASSES[status]);
    btn.textContent = LABELS[status];
}

// Marcar todos presente para HOY
function markAllToday() {
    const today = '{{ $todayStr ?? "" }}';
    if (!today) return;
    const safe = today.replace(/-/g, '');
    document.querySelectorAll('[data-cell]').forEach(btn => {
        if (btn.dataset.cell && btn.dataset.cell.includes('_' + safe)) {
            setCell(btn, 'present');
        }
    });
}
</script>
@endpush
