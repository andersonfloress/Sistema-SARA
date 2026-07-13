@extends('layouts.app')
@section('title', 'Gestionar Horarios')
@section('page-title', 'Gestionar Horarios')

@php
$days = ['lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes'];

// Paleta de colores por índice de curso (hasta 11 cursos)
$palette = [
    ['bg' => 'bg-indigo-100',  'border' => 'border-indigo-300',  'text' => 'text-indigo-800',  'badge' => 'bg-indigo-500'],
    ['bg' => 'bg-emerald-100', 'border' => 'border-emerald-300', 'text' => 'text-emerald-800', 'badge' => 'bg-emerald-500'],
    ['bg' => 'bg-amber-100',   'border' => 'border-amber-300',   'text' => 'text-amber-800',   'badge' => 'bg-amber-500'],
    ['bg' => 'bg-rose-100',    'border' => 'border-rose-300',    'text' => 'text-rose-800',    'badge' => 'bg-rose-500'],
    ['bg' => 'bg-sky-100',     'border' => 'border-sky-300',     'text' => 'text-sky-800',     'badge' => 'bg-sky-500'],
    ['bg' => 'bg-violet-100',  'border' => 'border-violet-300',  'text' => 'text-violet-800',  'badge' => 'bg-violet-500'],
    ['bg' => 'bg-orange-100',  'border' => 'border-orange-300',  'text' => 'text-orange-800',  'badge' => 'bg-orange-500'],
    ['bg' => 'bg-teal-100',    'border' => 'border-teal-300',    'text' => 'text-teal-800',    'badge' => 'bg-teal-500'],
    ['bg' => 'bg-fuchsia-100', 'border' => 'border-fuchsia-300', 'text' => 'text-fuchsia-800', 'badge' => 'bg-fuchsia-500'],
    ['bg' => 'bg-lime-100',    'border' => 'border-lime-300',    'text' => 'text-lime-800',    'badge' => 'bg-lime-500'],
    ['bg' => 'bg-cyan-100',    'border' => 'border-cyan-300',    'text' => 'text-cyan-800',    'badge' => 'bg-cyan-500'],
];

// Mapear course_id → índice de color
$courseColorMap = [];
if ($selectedSection) {
    foreach ($selectedSection->courses as $i => $c) {
        $courseColorMap[$c->id] = $palette[$i % count($palette)];
    }
}
@endphp

@section('content')

{{-- ── Filtros: Año + Sección ──────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" action="{{ route('horarios.admin') }}" class="flex flex-wrap gap-3 items-center">

        {{-- Selector de año --}}
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-600 whitespace-nowrap">Año:</label>
            <select name="year"
                    onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                @foreach($availableYears as $yr)
                <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>
                    {{ $yr }}{{ $yr == $activeYear ? ' (activo)' : '' }}
                </option>
                @endforeach
            </select>
        </div>

        <span class="text-gray-200 hidden sm:inline">|</span>

        {{-- Selector de sección (filtrado por año ya desde el controller) --}}
        <div class="flex items-center gap-2 flex-1 min-w-0">
            <label class="text-sm font-medium text-gray-600 whitespace-nowrap">Sección:</label>
            <select name="section_id"
                    onchange="this.form.submit()"
                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="">— Seleccionar —</option>
                @foreach($sections as $s)
                <option value="{{ $s->id }}" {{ $selectedSection?->id == $s->id ? 'selected' : '' }}>
                    {{ $s->name }} · {{ ucfirst($s->turno) }}
                </option>
                @endforeach
            </select>
        </div>

        {{-- Badge si se está viendo un año histórico --}}
        @if($selectedYear != $activeYear)
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700 font-medium">
            <i data-lucide="history" class="w-3.5 h-3.5"></i>
            Viendo año histórico {{ $selectedYear }}
        </span>
        @endif

    </form>
</div>

@if($selectedSection)

{{-- ── Alertas ──────────────────────────────────────────────────────────── --}}
@if(session('success'))
<div class="mb-4 flex items-center gap-2 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
    <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>{{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
    @foreach($errors->all() as $e)<p class="flex items-start gap-1.5"><i data-lucide="alert-circle" class="w-3.5 h-3.5 mt-0.5 shrink-0"></i>{{ $e }}</p>@endforeach
</div>
@endif

<div class="flex flex-col xl:flex-row gap-5">

    {{-- ════════════════════════════════════════════════════════════════════
         COLUMNA IZQUIERDA — Cuadrícula visual del horario
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="flex-1 min-w-0 space-y-4">

        {{-- Título + leyenda de cursos --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex flex-wrap items-start gap-3">
                <div class="flex-1 min-w-0">
                    <h2 class="font-semibold text-gray-800">
                        Horario — {{ $selectedSection->name }}
                        <span class="text-gray-400 font-normal text-sm ml-1">· {{ $selectedSection->year }} · {{ ucfirst($selectedSection->turno) }}</span>
                    </h2>
                    <p class="text-xs text-gray-400 mt-0.5">Haz clic en una celda vacía para pre-llenar el formulario. Usa ✕ para eliminar un bloque.</p>
                </div>
                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 rounded-md">
                        <span class="font-semibold text-gray-700">{{ $slots->count() }}</span> / {{ $selectedSection->courses->sum('hours_per_week') }} bloques
                    </span>
                </div>
            </div>

            {{-- Leyenda de colores por curso --}}
            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach($selectedSection->courses as $i => $c)
                @php $color = $palette[$i % count($palette)]; $assigned = $courseSlotCounts[$c->id] ?? 0; @endphp
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full border {{ $color['bg'] }} {{ $color['border'] }} {{ $color['text'] }}">
                    <span class="font-medium">{{ $c->name }}</span>
                    <span class="opacity-60">({{ $assigned }}/{{ $c->hours_per_week }}h)</span>
                </span>
                @endforeach
            </div>
        </div>

        {{-- Cuadrícula --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
            <table class="w-full text-xs min-w-[560px] border-collapse">
                <thead>
                    <tr>
                        <th class="px-3 py-2.5 bg-gray-50 text-gray-500 font-semibold text-left border-b border-r border-gray-100 w-20 uppercase text-[10px] tracking-wide">Hora</th>
                        @foreach($days as $key => $label)
                        <th class="px-3 py-2.5 bg-gray-50 text-gray-500 font-semibold border-b border-gray-100 {{ !$loop->last ? 'border-r' : '' }} uppercase text-[10px] tracking-wide text-center">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($times as $time)
                    @if($receso = ($recesos[$time] ?? null))
                    {{-- Fila de recreo --}}
                    <tr class="bg-amber-50/70 border-b border-amber-100">
                        <td class="px-3 py-1.5 text-amber-500 font-medium border-r border-amber-100 whitespace-nowrap text-[11px]">{{ $time }}</td>
                        <td colspan="{{ count($days) }}" class="px-3 py-1.5 text-center">
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-600 uppercase tracking-wide">
                                🍎 Recreo · {{ $time }} – {{ $receso['fin'] }}
                            </span>
                        </td>
                    </tr>
                    @else
                    {{-- Fila de periodo --}}
                    <tr class="border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                        <td class="px-3 py-2 text-gray-400 font-medium border-r border-gray-100 whitespace-nowrap align-top">{{ $time }}</td>
                        @foreach($days as $day => $label)
                        @php $slot = $grid[$day][$time] ?? null; @endphp
                        <td class="px-1.5 py-1.5 border-gray-100 {{ !$loop->last ? 'border-r' : '' }} align-top">
                            @if($slot)
                            @php $color = $courseColorMap[$slot->course_id] ?? $palette[0]; @endphp
                            <div class="relative group rounded-lg border p-1.5 {{ $color['bg'] }} {{ $color['border'] }} {{ $color['text'] }}">
                                <p class="font-semibold leading-tight pr-4 truncate">{{ $slot->course?->name }}</p>
                                @if($slot->course?->teacher)
                                <p class="opacity-60 truncate leading-tight mt-0.5">{{ $slot->course->teacher->name }}</p>
                                @endif
                                @if($slot->classroom)
                                <p class="opacity-50 leading-tight">{{ $slot->classroom }}</p>
                                @endif
                                <p class="opacity-50 leading-tight">{{ $slot->start_time }} – {{ $slot->end_time }}</p>
                                {{-- Botón eliminar --}}
                                <form method="POST" action="{{ route('horarios.destroy', $slot) }}"
                                      onsubmit="return confirmDelete(event)"
                                      class="absolute top-1 right-1">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            title="Eliminar bloque"
                                            class="w-5 h-5 flex items-center justify-center rounded opacity-0 group-hover:opacity-100 transition bg-white/70 hover:bg-red-100 hover:text-red-600 text-gray-500 text-[10px] font-bold">
                                        ✕
                                    </button>
                                </form>
                            </div>
                            @else
                            {{-- Celda vacía — clic pre-llena formulario --}}
                            <button type="button"
                                    onclick="selectCell('{{ $day }}', '{{ $time }}')"
                                    title="Asignar curso: {{ $label }} {{ $time }}"
                                    class="w-full h-10 rounded-lg border-2 border-dashed border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-colors flex items-center justify-center text-gray-300 hover:text-indigo-400 text-base empty-cell"
                                    data-day="{{ $day }}" data-time="{{ $time }}">
                                +
                            </button>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Resumen de cursos (tabla compacta) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Resumen de horas asignadas</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($selectedSection->courses as $i => $c)
                @php
                    $color    = $palette[$i % count($palette)];
                    $assigned = $courseSlotCounts[$c->id] ?? 0;
                    $pct      = $c->hours_per_week > 0 ? min(100, round($assigned / $c->hours_per_week * 100)) : 0;
                    $complete = $assigned >= $c->hours_per_week;
                @endphp
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full shrink-0 {{ $color['badge'] }}"></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between text-xs mb-0.5">
                            <span class="text-gray-700 truncate">{{ $c->name }}</span>
                            <span class="{{ $complete ? 'text-green-600 font-semibold' : 'text-gray-500' }}">{{ $assigned }}/{{ $c->hours_per_week }}h</span>
                        </div>
                        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all {{ $complete ? 'bg-green-400' : $color['badge'] }}"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>{{-- fin columna izquierda --}}

    {{-- ════════════════════════════════════════════════════════════════════
         COLUMNA DERECHA — Formulario de asignación
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="w-full xl:w-72 shrink-0">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sticky top-4">
            <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4 text-indigo-500"></i>
                Agregar Bloque
            </h3>

            {{-- Indicador de celda seleccionada --}}
            <div id="cell-hint"
                 class="hidden mb-3 p-2 bg-indigo-50 border border-indigo-200 rounded-lg text-xs text-indigo-700 flex items-center gap-1.5">
                <i data-lucide="mouse-pointer-click" class="w-3.5 h-3.5 shrink-0"></i>
                <span id="cell-hint-text"></span>
            </div>

            <form method="POST" action="{{ route('horarios.store') }}" class="space-y-3" id="add-slot-form">
                @csrf
                <input type="hidden" name="section_id_redirect" value="{{ $selectedSection->id }}">
                <input type="hidden" name="year_redirect" value="{{ $selectedYear }}">

                {{-- Curso --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Curso *</label>
                    <select name="course_id" id="sel-course" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar</option>
                        @foreach($selectedSection->courses as $i => $c)
                        @php $assigned = $courseSlotCounts[$c->id] ?? 0; $complete = $assigned >= $c->hours_per_week; @endphp
                        <option value="{{ $c->id }}" {{ old('course_id') == $c->id ? 'selected' : '' }}
                                class="{{ $complete ? 'text-gray-400' : '' }}">
                            {{ $c->name }} ({{ $assigned }}/{{ $c->hours_per_week }}h){{ $complete ? ' ✓' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Día --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Día *</label>
                    <select name="day_of_week" id="sel-day" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        @foreach($days as $key => $label)
                        <option value="{{ $key }}" {{ old('day_of_week') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Hora inicio --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Hora inicio *</label>
                    <select name="start_time" id="sel-start" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar</option>
                        @foreach($times as $t)
                        @if(!isset($recesos[$t]))
                        <option value="{{ $t }}" {{ old('start_time') == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endif
                        @endforeach
                    </select>
                </div>

                {{-- Hora fin --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Hora fin *</label>
                    <select name="end_time" id="sel-end" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar</option>
                        @foreach($times as $t)
                        @if(!isset($recesos[$t]))
                        <option value="{{ $t }}" {{ old('end_time') == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endif
                        @endforeach
                    </select>
                </div>

                {{-- Aula --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Aula <span class="text-gray-400">(opcional)</span></label>
                    <input type="text" name="classroom" value="{{ old('classroom') }}"
                           placeholder="Ej: Aula 101"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <button type="submit"
                        class="w-full py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition flex items-center justify-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Agregar Bloque
                </button>
            </form>
        </div>
    </div>

</div>{{-- fin flex --}}

@else
{{-- Estado vacío — sin sección seleccionada --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-16 text-center">
    <i data-lucide="calendar-days" class="w-12 h-12 text-gray-200 mx-auto mb-4"></i>
    <p class="text-gray-500 text-sm">Selecciona una sección para ver y gestionar su horario.</p>
</div>
@endif

@endsection

@push('scripts')
<script>
// ── Pre-llenar formulario al hacer clic en celda vacía ──────────────────────
function selectCell(day, startTime) {
    const daySelect   = document.getElementById('sel-day');
    const startSelect = document.getElementById('sel-start');
    const endSelect   = document.getElementById('sel-end');
    const hint        = document.getElementById('cell-hint');
    const hintText    = document.getElementById('cell-hint-text');

    if (daySelect)   daySelect.value   = day;
    if (startSelect) startSelect.value = startTime;

    // Auto-seleccionar hora fin: el siguiente tiempo disponible en la lista
    if (endSelect && startSelect) {
        const opts  = Array.from(startSelect.options).map(o => o.value).filter(v => v);
        const idx   = opts.indexOf(startTime);
        const next  = idx >= 0 && idx + 1 < opts.length ? opts[idx + 1] : '';
        if (next) endSelect.value = next;
    }

    // Mostrar indicador
    const dayLabels = { lunes:'Lunes', martes:'Martes', miercoles:'Miércoles', jueves:'Jueves', viernes:'Viernes' };
    if (hint && hintText) {
        hintText.textContent = 'Celda seleccionada: ' + (dayLabels[day] || day) + ' ' + startTime;
        hint.classList.remove('hidden');
    }

    // Hacer scroll al formulario en móvil
    const form = document.getElementById('add-slot-form');
    if (form) form.scrollIntoView({ behavior: 'smooth', block: 'start' });

    // Resaltar celda seleccionada
    document.querySelectorAll('.empty-cell').forEach(b => b.classList.remove('border-indigo-400','bg-indigo-50'));
    const target = document.querySelector(`.empty-cell[data-day="${day}"][data-time="${startTime}"]`);
    if (target) target.classList.add('border-indigo-400', 'bg-indigo-50');
}

// ── Confirmar eliminación ────────────────────────────────────────────────────
function confirmDelete(event) {
    event.preventDefault();
    const form = event.currentTarget;
    Swal.fire({
        title: '¿Eliminar bloque?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
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
