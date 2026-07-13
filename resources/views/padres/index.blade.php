@extends('layouts.app')
@section('title', 'Portal de Padres')
@section('page-title', 'Portal de Padres')

@section('content')
<div class="mb-4">
    <p class="text-gray-600">Hola, <strong>{{ auth()->user()->name }}</strong>. Aquí puedes ver el seguimiento académico de tus hijos.</p>
</div>

@if($enriched->isEmpty())
<div class="bg-white rounded p-12 text-center text-gray-400 border border-gray-100">
    <i data-lucide="users-2" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
    <p>No tienes alumnos vinculados a tu cuenta.</p>
    <p class="text-sm mt-1">Contacta al administrador para vincular a tus hijos.</p>
</div>
@else
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    @foreach($enriched as $item)
    @php $student = $item['student']; @endphp
    <div class="bg-white rounded shadow-sm border border-gray-100 overflow-hidden {{ $item['atRisk'] ? 'border-red-200' : '' }}">
        @if($item['atRisk'])
        <div class="px-5 py-2 bg-red-50 border-b border-red-100 flex items-center gap-2 text-xs text-red-700">
            <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
            <span>Alumno en riesgo académico</span>
        </div>
        @endif
        <div class="p-6">
            <div class="flex items-center gap-4 mb-5">
                <div class="w-14 h-14 bg-indigo-100 rounded flex items-center justify-center text-xl font-bold text-indigo-600 flex-shrink-0">
                    {{ strtoupper(substr($student->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">{{ $student->name }}</h3>
                    <p class="text-sm text-gray-500">{{ $item['section']?->name ?? 'Sin sección' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-5">
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-gray-500 mb-1">Promedio</p>
                    <p class="text-2xl font-bold {{ ($item['avgGrade'] ?? 0) >= 11 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $item['avgGrade'] !== null ? number_format($item['avgGrade'], 1) : '—' }}
                    </p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-gray-500 mb-1">Asistencia</p>
                    <p class="text-2xl font-bold {{ $item['attPct'] >= 70 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $item['attPct'] }}%
                    </p>
                </div>
            </div>

            <a href="{{ route('padres.show', $student) }}"
               class="flex items-center justify-center gap-2 w-full py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                <i data-lucide="eye" class="w-4 h-4"></i> Ver detalle completo
            </a>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
