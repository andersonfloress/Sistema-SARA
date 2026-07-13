@extends('layouts.app')
@section('title', 'Calendario Académico')
@section('page-title', 'Calendario Académico')

@section('content')

@php
    use Carbon\Carbon;
    $today       = Carbon::today();
    $dayNames    = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    $startOffset = (int) $monthStart->dayOfWeek;   // 0=Dom … 6=Sáb
    $daysInMonth = (int) $monthEnd->day;
    $totalCells  = (int) ceil(($startOffset + $daysInMonth) / 7) * 7;
    $trailing    = $totalCells - $startOffset - $daysInMonth;

    // Color de chip por destinatario (inline, para evitar purge de Tailwind CDN)
    $chipStyles = [
        'all'     => 'background:#f3f4f6; color:#4b5563;',
        'student' => 'background:#dbeafe; color:#1d4ed8;',
        'teacher' => 'background:#ede9fe; color:#6d28d9;',
        'admin'   => 'background:#fee2e2; color:#b91c1c;',
        'parent'  => 'background:#dcfce7; color:#15803d;',
    ];
@endphp

{{-- ── Cabecera: navegación de mes + botón nuevo ─────────────────────── --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">

    <div class="flex items-center gap-2">
        {{-- Mes anterior --}}
        <a href="{{ route('calendario.index', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}"
           class="p-2 rounded-lg bg-white border border-gray-200 hover:bg-gray-50 transition shadow-sm text-gray-600">
            <i data-lucide="chevron-left" style="width:18px;height:18px;"></i>
        </a>

        {{-- Título del mes --}}
        <div class="px-4 py-2 bg-white border border-gray-200 rounded-lg shadow-sm min-w-[200px] text-center">
            <span class="text-sm font-bold text-gray-800">
                {{ ucfirst($month->isoFormat('MMMM [de] YYYY')) }}
            </span>
            @if($month->isSameMonth($today))
                <span class="ml-2 px-1.5 py-0.5 text-[10px] font-bold rounded-full"
                      style="background:#fce7eb; color:#8b1c30;">Mes actual</span>
            @endif
        </div>

        {{-- Mes siguiente --}}
        <a href="{{ route('calendario.index', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}"
           class="p-2 rounded-lg bg-white border border-gray-200 hover:bg-gray-50 transition shadow-sm text-gray-600">
            <i data-lucide="chevron-right" style="width:18px;height:18px;"></i>
        </a>

        {{-- Botón "Hoy" si no es el mes actual --}}
        @if(!$month->isSameMonth($today))
        <a href="{{ route('calendario.index') }}"
           class="px-3 py-2 text-xs font-semibold rounded-lg bg-white border border-gray-200
                  hover:bg-gray-50 transition shadow-sm text-gray-600">
            Hoy
        </a>
        @endif
    </div>

    {{-- Nuevo evento (solo admin) --}}
    @if(auth()->user()->isAdmin())
    <a href="{{ route('calendario.create') }}"
       class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm font-semibold shadow-sm transition"
       style="background: linear-gradient(135deg, #8b1c30, #6b1427);"
       onmouseover="this.style.background='linear-gradient(135deg,#a8183b,#8b1c30)'"
       onmouseout="this.style.background='linear-gradient(135deg,#8b1c30,#6b1427)'">
        <i data-lucide="plus" style="width:15px;height:15px;"></i> Nuevo Evento
    </a>
    @endif
</div>

{{-- ── Cuadrícula mensual ──────────────────────────────────────────────── --}}
<div class="bg-white rounded shadow-sm border border-gray-100 overflow-hidden mb-6">

    {{-- Nombres de día --}}
    <div class="grid grid-cols-7" style="background:#f9fafb; border-bottom:2px solid #e5e7eb;">
        @foreach($dayNames as $i => $dn)
        <div class="py-3 text-center text-xs font-bold uppercase tracking-wider
                    {{ $i === 0 || $i === 6 ? 'text-gray-300' : 'text-gray-500' }}
                    {{ $i < 6 ? 'border-r border-gray-100' : '' }}">
            {{ $dn }}
        </div>
        @endforeach
    </div>

    {{-- Celdas del mes --}}
    <div class="grid grid-cols-7">

        {{-- Celdas vacías iniciales --}}
        @for($i = 0; $i < $startOffset; $i++)
        <div class="border-b border-r border-gray-100 bg-gray-50/40"
             style="min-height:88px;"></div>
        @endfor

        {{-- Días del mes --}}
        @for($d = 1; $d <= $daysInMonth; $d++)
        @php
            $date      = $monthStart->copy()->setDay($d);
            $isToday   = $date->isSameDay($today);
            $isPast    = $date->lt($today) && !$isToday;
            $dow       = (int) $date->dayOfWeek; // 0=Dom, 6=Sáb
            $isWeekend = $dow === 0 || $dow === 6;
            $dayEvs    = $eventsByDay[$d] ?? collect();
            $visible   = $dayEvs->take(2);
            $extra     = max(0, $dayEvs->count() - 2);
        @endphp
        <div class="border-b border-r border-gray-100 p-1.5 flex flex-col
                    {{ $isWeekend ? 'bg-gray-50/40' : '' }}"
             style="min-height:88px;">

            {{-- Número del día --}}
            <div class="flex justify-end mb-1">
                <span class="w-6 h-6 flex items-center justify-center rounded-full
                             text-xs font-bold leading-none"
                      style="{{ $isToday
                          ? 'background:linear-gradient(135deg,#8b1c30,#6b1427); color:#fff;'
                          : ($isPast
                              ? 'color:#d1d5db;'
                              : ($isWeekend ? 'color:#9ca3af;' : 'color:#374151;')) }}">
                    {{ $d }}
                </span>
            </div>

            {{-- Chips de eventos --}}
            @foreach($visible as $ev)
            <a href="#ev-{{ $ev->id }}"
               class="block w-full text-left px-1.5 py-0.5 rounded mb-0.5
                      text-[10px] font-semibold truncate leading-snug"
               style="{{ $chipStyles[$ev->target_role] ?? $chipStyles['all'] }}"
               title="{{ $ev->title }}">
                {{ $ev->title }}
            </a>
            @endforeach

            @if($extra > 0)
            <span class="text-[10px] text-gray-400 pl-1 mt-auto">+{{ $extra }} más</span>
            @endif
        </div>
        @endfor

        {{-- Celdas vacías finales --}}
        @for($i = 0; $i < $trailing; $i++)
        <div class="border-b border-r border-gray-100 bg-gray-50/40"
             style="min-height:88px;"></div>
        @endfor
    </div>
</div>

{{-- ── Leyenda de colores ──────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center gap-3 mb-5 text-xs text-gray-500">
    <span class="font-semibold text-gray-400 uppercase tracking-wide">Destinatario:</span>
    @foreach([
        'all'     => ['Todos',           'background:#f3f4f6; color:#4b5563;'],
        'student' => ['Alumnos',         'background:#dbeafe; color:#1d4ed8;'],
        'teacher' => ['Docentes',        'background:#ede9fe; color:#6d28d9;'],
        'parent'  => ['Padres',          'background:#dcfce7; color:#15803d;'],
        'admin'   => ['Administradores', 'background:#fee2e2; color:#b91c1c;'],
    ] as [$label, $style])
    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold" style="{{ $style }}">{{ $label }}</span>
    @endforeach
</div>

{{-- ── Lista de eventos del mes ────────────────────────────────────────── --}}
@if($events->isNotEmpty())

<div class="flex items-center gap-3 mb-4">
    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wide whitespace-nowrap">
        Eventos · {{ ucfirst($month->isoFormat('MMMM')) }}
    </h3>
    <div class="flex-1 h-px bg-gray-100"></div>
    <span class="text-xs text-gray-400">{{ $events->count() }} evento(s)</span>
</div>

<div class="space-y-3">
    @foreach($events as $ev)
    <div id="ev-{{ $ev->id }}"
         class="bg-white rounded shadow-sm border border-gray-100 p-5
                flex items-start gap-4 transition
                {{ $ev->isPast() ? 'opacity-60' : '' }}">

        {{-- Badge de fecha --}}
        <div class="w-12 h-12 rounded flex flex-col items-center justify-center flex-shrink-0"
             style="background:#fce7eb;">
            <span class="text-[10px] font-bold uppercase leading-none mb-0.5"
                  style="color:#8b1c30;">{{ $ev->event_date->translatedFormat('M') }}</span>
            <span class="text-lg font-extrabold leading-none"
                  style="color:#8b1c30;">{{ $ev->event_date->format('d') }}</span>
        </div>

        {{-- Contenido --}}
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <h4 class="font-semibold text-gray-800 text-sm">{{ $ev->title }}</h4>
                <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full"
                      style="{{ $chipStyles[$ev->target_role] ?? $chipStyles['all'] }}">
                    {{ $ev->targetRoleLabel() }}
                </span>
                @if($ev->isPast())
                <span class="px-2 py-0.5 text-[11px] rounded-full bg-gray-100 text-gray-400 font-medium">
                    Finalizado
                </span>
                @elseif($ev->event_date->isToday())
                <span class="px-2 py-0.5 text-[11px] rounded-full font-semibold"
                      style="background:#fce7eb; color:#8b1c30;">Hoy</span>
                @endif
            </div>
            <p class="text-xs text-gray-400 mb-1.5">
                {{ $ev->event_date->translatedFormat('l, d \d\e F \d\e Y') }}
            </p>
            @if($ev->description)
            <p class="text-sm text-gray-600 leading-relaxed">{{ $ev->description }}</p>
            @endif
            <div class="mt-2 flex items-center gap-1.5 text-xs text-gray-400">
                <i data-lucide="user" style="width:11px;height:11px;"></i>
                <span>{{ $ev->author?->name }}</span>
            </div>
        </div>

        {{-- Eliminar (solo admin) --}}
        @if(auth()->user()->isAdmin())
        <form method="POST" action="{{ route('calendario.destroy', $ev) }}"
              onsubmit="return confirmDeleteEv(event, '{{ addslashes($ev->title) }}')">
            @csrf @method('DELETE')
            <button type="submit"
                    class="p-1.5 text-red-400 hover:bg-red-50 rounded-lg transition flex-shrink-0">
                <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
            </button>
        </form>
        @endif
    </div>
    @endforeach
</div>

@else

{{-- Estado vacío --}}
<div class="bg-white rounded p-12 text-center border border-gray-100 shadow-sm">
    <div class="w-14 h-14 rounded flex items-center justify-center mx-auto mb-4"
         style="background:#fce7eb;">
        <i data-lucide="calendar" style="width:26px;height:26px;color:#8b1c30;"></i>
    </div>
    <p class="text-sm font-medium text-gray-500">
        No hay eventos en {{ $month->isoFormat('MMMM [de] YYYY') }}.
    </p>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('calendario.create') }}"
       class="inline-block mt-3 text-sm font-semibold hover:underline"
       style="color:#8b1c30;">+ Crear primer evento del mes</a>
    @endif
</div>

@endif

@endsection

@push('scripts')
<script>
function confirmDeleteEv(event, title) {
    event.preventDefault();
    const form = event.target;
    Swal.fire({
        title: '¿Eliminar evento?',
        text: `¿Eliminar "${title}"?`,
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
