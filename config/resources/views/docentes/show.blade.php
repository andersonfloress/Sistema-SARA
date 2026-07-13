@extends('layouts.app')
@section('title', $docente->name)
@section('page-title', 'Perfil Docente')

@section('content')
@if(session('success'))
<div class="mb-6 p-4 bg-green-50 border border-green-200 rounded text-sm text-green-700">
    {{ session('success') }}
</div>
@endif

@php($profile = $docente->teacherProfile)
@php($puedeVerDatosSensibles = auth()->user()->isAdmin() || auth()->id() === $docente->id)

<div class="bg-white rounded shadow-sm border border-gray-100 p-6 mb-6">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            @if($profile?->foto_perfil)
                <img src="{{ Storage::url($profile->foto_perfil) }}" alt="{{ $docente->name }}"
                     class="w-16 h-16 rounded object-cover border border-gray-100">
            @else
                <div class="w-16 h-16 bg-purple-100 rounded flex items-center justify-center text-2xl font-bold text-purple-600">
                    {{ strtoupper(substr($docente->name, 0, 1)) }}
                </div>
            @endif
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $docente->name }}</h2>
                <p class="text-sm text-gray-500">{{ $docente->email }}</p>
                <div class="mt-1 flex flex-wrap gap-1">
                    @if($profile?->especialidad)
                    <span class="px-2 py-0.5 text-xs bg-purple-100 text-purple-700 rounded-full inline-block">
                        {{ $profile->especialidad }}
                    </span>
                    @endif
                    @if($puedeVerDatosSensibles && $profile?->telefono)
                    <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full inline-block">
                        <i data-lucide="phone" class="w-3 h-3 inline"></i> {{ $profile->telefono }}
                    </span>
                    @endif
                    @if($profile?->condicion_laboral)
                    <span class="px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded-full inline-block capitalize">
                        {{ $profile->condicion_laboral }}
                    </span>
                    @endif
                    @if($profile?->nivel_academico)
                    <span class="px-2 py-0.5 text-xs bg-amber-100 text-amber-700 rounded-full inline-block capitalize">
                        {{ $profile->nivel_academico }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('docentes.editProfile', $docente) }}"
           class="flex-shrink-0 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition flex items-center gap-2">
            <i data-lucide="pencil" class="w-4 h-4"></i> Editar Perfil
        </a>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    @if($puedeVerDatosSensibles)
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-4">
            <i data-lucide="id-card" class="w-4 h-4 text-indigo-500"></i> Datos Personales
        </h3>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">DNI</dt><dd class="text-gray-800 font-medium">{{ $profile?->dni ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Fecha de nacimiento</dt><dd class="text-gray-800 font-medium">{{ optional($profile?->fecha_nacimiento)->format('d/m/Y') ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Sexo</dt><dd class="text-gray-800 font-medium">{{ $profile?->sexo === 'M' ? 'Masculino' : ($profile?->sexo === 'F' ? 'Femenino' : '—') }}</dd></div>
        </dl>
    </div>
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-4">
            <i data-lucide="phone" class="w-4 h-4 text-indigo-500"></i> Contacto
        </h3>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Teléfono</dt><dd class="text-gray-800 font-medium">{{ $profile?->telefono ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Correo alterno</dt><dd class="text-gray-800 font-medium">{{ $profile?->correo_alternativo ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Dirección</dt><dd class="text-gray-800 font-medium text-right">{{ $profile?->direccion ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Emergencia</dt><dd class="text-gray-800 font-medium text-right">{{ $profile?->contacto_emergencia_nombre ? $profile->contacto_emergencia_nombre.' · '.$profile->contacto_emergencia_telefono : '—' }}</dd></div>
        </dl>
    </div>
    @else
    <div class="bg-gray-50 rounded border border-gray-100 p-6 flex items-center justify-center text-center md:col-span-2">
        <p class="text-sm text-gray-400"><i data-lucide="lock" class="w-4 h-4 inline mb-1"></i><br>Los datos personales y de contacto de emergencia son visibles solo para administración.</p>
    </div>
    @endif
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-4">
            <i data-lucide="briefcase" class="w-4 h-4 text-indigo-500"></i> Datos Laborales
        </h3>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Código docente</dt><dd class="text-gray-800 font-medium">{{ $profile?->codigo_docente ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Fecha de ingreso</dt><dd class="text-gray-800 font-medium">{{ optional($profile?->fecha_ingreso)->format('d/m/Y') ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">N° colegiatura</dt><dd class="text-gray-800 font-medium">{{ $profile?->numero_colegiatura ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Turno</dt><dd class="text-gray-800 font-medium capitalize">{{ $profile?->turno ?? '—' }}</dd></div>
            @if($profile?->cv_path)
            <div class="pt-2">
                <a href="{{ Storage::url($profile->cv_path) }}" target="_blank" class="inline-flex items-center gap-1 text-indigo-600 hover:underline">
                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Ver CV / Título
                </a>
            </div>
            @endif
        </dl>
    </div>
</div>

<div class="bg-white rounded shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
            <i data-lucide="book-open" class="w-4 h-4 text-indigo-500"></i> Cursos Asignados
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-xs text-gray-600 uppercase">
                <tr>
                    <th class="px-6 py-3">Curso</th>
                    <th class="px-6 py-3">Sección</th>
                    <th class="px-6 py-3">Grado</th>
                    <th class="px-6 py-3">Horas/sem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($docente->courses as $course)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-3 font-medium text-gray-800">{{ $course->name }}</td>
                    <td class="px-6 py-3 text-gray-600">{{ $course->section->name }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 text-xs bg-indigo-100 text-indigo-700 rounded-full">Grado {{ $course->section->grade }}</span>
                    </td>
                    <td class="px-6 py-3 text-gray-500">{{ $course->hours_per_week }}h</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">Sin cursos asignados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
