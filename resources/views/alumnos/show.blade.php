@extends('layouts.app')
@section('title', $alumno->name)
@section('page-title', 'Perfil de Alumno')

@section('content')
{{-- At-risk alert --}}
@if($atRisk)
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded flex items-center gap-3">
    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500 flex-shrink-0"></i>
    <p class="text-sm text-red-700 font-medium">Este alumno está en riesgo académico.</p>
</div>
@endif

{{-- Header card --}}
<div class="bg-white rounded shadow-sm border border-gray-100 p-6 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="w-16 h-16 bg-indigo-100 rounded flex items-center justify-center text-2xl font-bold text-indigo-600 flex-shrink-0">
            {{ strtoupper(substr($alumno->name, 0, 1)) }}
        </div>
        <div class="flex-1 min-w-0">
            <h2 class="text-xl font-bold text-gray-800">{{ $alumno->name }}</h2>
            <p class="text-sm text-gray-500">{{ $alumno->email }}</p>
            <div class="flex flex-wrap gap-2 mt-2">
                @if($alumno->enrollments->first()?->section)
                <span class="px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded-full">
                    {{ $alumno->enrollments->first()->section->name }}
                </span>
                @endif
                @if($alumno->studentProfile?->turno)
                <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">
                    Turno: {{ $alumno->studentProfile->turno }}
                </span>
                @endif
                @if($alumno->studentProfile?->dni)
                <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">
                    DNI: {{ $alumno->studentProfile->dni }}
                </span>
                @endif
                <span class="px-2 py-0.5 text-xs bg-indigo-100 text-indigo-700 rounded-full">
                    Año {{ $contextYear }}
                </span>
            </div>
        </div>
        <div class="flex gap-2 flex-shrink-0">
            <a href="{{ route('reportes.boletin.pdf', $alumno) }}"
               class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-700 rounded-lg text-sm font-medium hover:bg-red-100 transition">
                <i data-lucide="file-text" class="w-4 h-4"></i> Boletín PDF
            </a>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('alumnos.editProfile', $alumno) }}"
               class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                <i data-lucide="pencil" class="w-4 h-4"></i> Editar Perfil
            </a>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Grades --}}
    <div class="lg:col-span-2 bg-white rounded shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                <i data-lucide="clipboard-list" class="w-4 h-4 text-indigo-500"></i> Calificaciones
            </h3>
            @if($overallAvg !== null)
            <span class="text-sm font-semibold {{ $overallAvg >= 11 ? 'text-green-600' : 'text-red-600' }}">
                Promedio General: {{ $overallAvg }}
            </span>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-xs text-gray-600 uppercase">
                    <tr>
                        <th class="px-4 py-3">Curso</th>
                        <th class="px-4 py-3 text-center">I</th>
                        <th class="px-4 py-3 text-center">II</th>
                        <th class="px-4 py-3 text-center">III</th>
                        <th class="px-4 py-3 text-center">Promedio</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($gradesByCourse as $c)
                    <tr class="{{ ($c['isTeacherCourse'] ?? false) ? 'bg-red-50/60' : '' }}">
                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ $c['courseName'] }}
                            @if($c['isTeacherCourse'] ?? false)
                            <span class="ml-1.5 px-1.5 py-0.5 text-[9px] font-bold bg-red-100 text-red-700 rounded uppercase tracking-wide">Tu curso</span>
                            @endif
                        </td>
                        @foreach(['I','II','III'] as $p)
                        <td class="px-4 py-3 text-center">
                            @if(isset($c['grades'][$p]))
                                <span class="{{ $c['grades'][$p] >= 11 ? 'text-gray-800' : 'text-red-600 font-bold' }}">
                                    {{ $c['grades'][$p] }}
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        @endforeach
                        <td class="px-4 py-3 text-center font-semibold {{ ($c['avg'] ?? 0) >= 11 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $c['avg'] ?? '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Sin calificaciones registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Attendance + Apoderados --}}
    <div class="space-y-4">
        {{-- Attendance summary --}}
        <div class="bg-white rounded shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i data-lucide="check-square" class="w-4 h-4 text-indigo-500"></i> Asistencia
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Total registros</span>
                    <span class="font-medium">{{ $attTotal }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-green-600">Presente</span>
                    <span class="font-medium text-green-600">{{ $attPresent }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-red-500">Ausente</span>
                    <span class="font-medium text-red-500">{{ $attAbsent }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-yellow-600">Tardanza</span>
                    <span class="font-medium text-yellow-600">{{ $attLate }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-blue-600">Justificado</span>
                    <span class="font-medium text-blue-600">{{ $attJustified }}</span>
                </div>
                <div class="pt-2 border-t border-gray-100">
                    <div class="flex justify-between text-sm font-semibold">
                        <span>% Asistencia</span>
                        <span class="{{ $attPct >= 70 ? 'text-green-600' : 'text-red-600' }}">{{ $attPct }}%</span>
                    </div>
                    <div class="mt-2 h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full {{ $attPct >= 70 ? 'bg-green-500' : 'bg-red-500' }} rounded-full transition-all" style="width: {{ $attPct }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Apoderados --}}
        @if($alumno->parents->count())
        <div class="bg-white rounded shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <i data-lucide="users-2" class="w-4 h-4 text-indigo-500"></i> Apoderados
            </h3>
            <div class="space-y-2">
                @foreach($alumno->parents as $parent)
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center text-xs font-bold text-green-700">
                        {{ strtoupper(substr($parent->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $parent->name }}</p>
                        <p class="text-xs text-gray-500">{{ $parent->email }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
