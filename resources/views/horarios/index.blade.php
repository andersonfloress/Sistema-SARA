@extends('layouts.app')
@section('title', 'Horarios')
@section('page-title', 'Horarios')

@section('content')

{{-- ── Selector de hijo (solo para padres con más de un hijo) ───────── --}}
@include('partials.parent_child_selector', [
    'children'     => $children     ?? collect(),
    'selectedChild'=> $selectedChild ?? null,
])

<div class="flex justify-between items-center mb-4">
    @if(auth()->user()->isAdmin())
    <form method="GET" class="flex gap-2">
        <select name="section_id" onchange="this.form.submit()"
                class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500">
            <option value="">Todas las secciones</option>
            @foreach($sections as $s)
            <option value="{{ $s->id }}" {{ request('section_id') == $s->id ? 'selected' : '' }}>
                {{ $s->name }}
            </option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('horarios.admin') }}"
       class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
        <i data-lucide="settings" class="w-4 h-4"></i> Gestionar Horarios
    </a>
    @else
    <div></div>
    @endif
</div>

{{-- Weekly grid --}}
<div class="bg-white rounded shadow-sm border border-gray-100 overflow-x-auto">
    @php $days = ['lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes']; @endphp
    <table class="w-full text-sm min-w-[640px]">
        <thead>
            <tr>
                <th class="px-4 py-3 bg-gray-50 text-gray-600 text-xs uppercase border-b border-r border-gray-100 w-20">Hora</th>
                @foreach($days as $key => $label)
                <th class="px-4 py-3 bg-gray-50 text-gray-600 text-xs uppercase border-b border-gray-100 {{ !$loop->last ? 'border-r' : '' }}">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($times as $time)
            @if($receso = ($recesos[$time] ?? null))
            <tr class="bg-amber-50/60 border-b border-amber-100">
                <td class="px-4 py-2 text-xs text-amber-500 font-medium border-r border-amber-100 whitespace-nowrap">{{ $time }}</td>
                <td colspan="{{ count($days) }}" class="px-4 py-2 text-center">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-600 tracking-wide uppercase">
                        🍎 Recreo · {{ $time }} – {{ $receso['fin'] }}
                    </span>
                </td>
            </tr>
            @else
            <tr class="border-b border-gray-100">
                <td class="px-4 py-3 text-xs text-gray-400 font-medium border-r border-gray-100 whitespace-nowrap">{{ $time }}</td>
                @foreach($days as $day => $label)
                <td class="px-2 py-2 border-gray-100 {{ !$loop->last ? 'border-r' : '' }} align-top">
                    @if($slot = $grid[$day][$time] ?? null)
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-2 text-xs">
                        <p class="font-semibold text-indigo-700 truncate">{{ $slot->course?->name }}</p>
                        <p class="text-gray-500 truncate">{{ $slot->course?->section?->name }}</p>
                        @if($slot->classroom)
                        <p class="text-gray-400 truncate">{{ $slot->classroom }}</p>
                        @endif
                        <p class="text-gray-400">{{ $slot->start_time }} – {{ $slot->end_time }}</p>
                    </div>
                    @endif
                </td>
                @endforeach
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>
</div>
@endsection
