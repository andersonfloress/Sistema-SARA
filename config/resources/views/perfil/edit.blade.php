@extends('layouts.app')
@section('title', 'Mi Perfil')
@section('page-title', 'Mi Perfil')

@section('content')
@php
    $user      = auth()->user();
    $esDocente = $user->isTeacher();
    $esAlumno  = $user->isStudent();
    $esPadre   = $user->isParent();
    $p         = $teacherProfile;
    $sp        = $studentProfile ?? null;
    $pp        = $parentProfile ?? null;
@endphp

@if(session('success'))
<div class="mb-6 p-4 bg-green-50 border border-green-200 rounded text-sm text-green-700">
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
    @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
</div>
@endif

{{-- ── Cabecera de perfil ───────────────────────────────────────────────── --}}
@php $enrollActual = $esAlumno ? ($studentEnrollments->first() ?? null) : null; @endphp
<div class="bg-white rounded shadow-sm border border-gray-100 p-6 mb-6">
    <div class="flex items-center gap-5">
        {{-- Avatar --}}
        @if($esDocente && $p?->foto_perfil)
            <img src="{{ Storage::url($p->foto_perfil) }}" alt="{{ $user->name }}"
                 class="w-20 h-20 rounded object-cover border border-gray-100 flex-shrink-0">
        @elseif($esAlumno && $sp?->foto_perfil)
            <img src="{{ Storage::url($sp->foto_perfil) }}" alt="{{ $user->name }}"
                 class="w-20 h-20 rounded object-cover border border-gray-100 flex-shrink-0">
        @else
            <div class="w-20 h-20 {{ $esAlumno ? 'bg-blue-100' : 'bg-purple-100' }} rounded flex items-center justify-center
                        text-3xl font-bold {{ $esAlumno ? 'text-blue-600' : 'text-purple-600' }} flex-shrink-0">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
        @endif

        <div class="flex-1 min-w-0">
            <h2 class="text-xl font-bold text-gray-800">{{ $user->name }}</h2>
            <p class="text-sm text-gray-500">{{ $user->email }}</p>
            <div class="mt-2 flex flex-wrap gap-1.5">
                <span class="px-2.5 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-700 rounded-full">
                    {{ $user->roleLabel() }}
                </span>
                @if($esDocente)
                    @if($p?->especialidad)
                    <span class="px-2.5 py-0.5 text-xs font-medium bg-purple-100 text-purple-700 rounded-full">
                        {{ $p->especialidad }}
                    </span>
                    @endif
                    @if($p?->condicion_laboral)
                    <span class="px-2.5 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 rounded-full capitalize">
                        {{ $p->condicion_laboral }}
                    </span>
                    @endif
                    @if($p?->nivel_academico)
                    <span class="px-2.5 py-0.5 text-xs font-medium bg-amber-100 text-amber-700 rounded-full capitalize">
                        {{ $p->nivel_academico }}
                    </span>
                    @endif
                @endif
                @if($esAlumno)
                    @if($enrollActual)
                    <span class="px-2.5 py-0.5 text-xs font-medium bg-indigo-50 text-indigo-600 rounded-full">
                        {{ $enrollActual->section->name }}
                    </span>
                    @endif
                    @if($sp?->turno)
                    <span class="px-2.5 py-0.5 text-xs font-medium bg-amber-100 text-amber-700 rounded-full capitalize">
                        Turno {{ $sp->turno }}
                    </span>
                    @endif
                    @if($sp?->codigo_estudiante)
                    <span class="px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">
                        Cód. {{ $sp->codigo_estudiante }}
                    </span>
                    @endif
                @endif
            </div>
        </div>

        @if($esDocente)
        <a href="{{ route('perfil.editFull') }}"
           class="flex-shrink-0 flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
            <i data-lucide="pencil" class="w-4 h-4"></i>
            <span class="hidden sm:inline">Editar mi perfil</span>
        </a>
        @endif
    </div>
</div>

@if($esDocente)
{{-- ── 3 tarjetas de datos (solo docente) ──────────────────────────────── --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

    {{-- Datos Personales — solo lectura --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-4">
            <i data-lucide="id-card" class="w-4 h-4 text-indigo-500"></i> Datos Personales
        </h3>
        <p class="text-xs text-gray-400 mb-3 italic">Gestionados por administración.</p>
        <dl class="space-y-2.5 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">DNI</dt>
                <dd class="text-gray-800 font-medium">{{ $p?->dni ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Fecha nacimiento</dt>
                <dd class="text-gray-800 font-medium">{{ optional($p?->fecha_nacimiento)->format('d/m/Y') ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Sexo</dt>
                <dd class="text-gray-800 font-medium">
                    {{ $p?->sexo === 'M' ? 'Masculino' : ($p?->sexo === 'F' ? 'Femenino' : '—') }}
                </dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Correo alterno</dt>
                <dd class="text-gray-800 font-medium text-right">{{ $p?->correo_alternativo ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Contacto emergencia</dt>
                <dd class="text-gray-800 font-medium text-right">
                    {{ $p?->contacto_emergencia_nombre
                        ? $p->contacto_emergencia_nombre . ' · ' . $p->contacto_emergencia_telefono
                        : '—' }}
                </dd>
            </div>
        </dl>
    </div>

    {{-- Mi Contacto — editable --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 border-t-4 border-t-indigo-500 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-1">
            <i data-lucide="phone" class="w-4 h-4 text-indigo-500"></i> Mi Contacto
        </h3>
        <p class="text-xs text-gray-400 mb-4 italic">Puedes actualizar estos datos tú mismo.</p>

        <form method="POST" action="{{ route('perfil.updateContactInfo') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf @method('PUT')

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono', $p?->telefono) }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dirección</label>
                <textarea name="direccion" rows="2"
                          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none resize-none">{{ old('direccion', $p?->direccion) }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Foto de Perfil</label>
                <input type="file" name="foto_perfil" accept="image/*"
                       class="w-full text-sm text-gray-500 border border-gray-200 rounded-lg px-2 py-1.5
                              file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0
                              file:bg-indigo-50 file:text-indigo-700 file:text-xs">
                @if($p?->foto_perfil)
                <p class="text-xs text-gray-400 mt-1">
                    Foto actual: <a href="{{ Storage::url($p->foto_perfil) }}" target="_blank" class="text-indigo-500 hover:underline">ver</a>
                </p>
                @endif
            </div>

            <button type="submit"
                    class="w-full mt-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                Guardar Contacto
            </button>
        </form>
    </div>

    {{-- Datos Laborales — solo lectura --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-4">
            <i data-lucide="briefcase" class="w-4 h-4 text-indigo-500"></i> Datos Laborales
        </h3>
        <p class="text-xs text-gray-400 mb-3 italic">Gestionados por administración.</p>
        <dl class="space-y-2.5 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">Código docente</dt>
                <dd class="text-gray-800 font-medium">{{ $p?->codigo_docente ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Fecha ingreso</dt>
                <dd class="text-gray-800 font-medium">{{ optional($p?->fecha_ingreso)->format('d/m/Y') ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">N° colegiatura</dt>
                <dd class="text-gray-800 font-medium">{{ $p?->numero_colegiatura ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Turno</dt>
                <dd class="text-gray-800 font-medium capitalize">{{ $p?->turno ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Carga máx.</dt>
                <dd class="text-gray-800 font-medium">
                    {{ $p?->max_horas_semanales ? $p->max_horas_semanales . ' h/sem' : '—' }}
                </dd>
            </div>
            @if($p?->cv_path)
            <div class="pt-1">
                <a href="{{ Storage::url($p->cv_path) }}" target="_blank"
                   class="inline-flex items-center gap-1 text-indigo-600 hover:underline text-xs">
                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Ver CV / Título
                </a>
            </div>
            @endif
        </dl>
    </div>
</div>

{{-- ── Cursos asignados ─────────────────────────────────────────────────── --}}
@php
    $currentCourses  = $coursesByYear->get($currentYear, collect());
    $previousYears   = $coursesByYear->except($currentYear);
    $totalPrevious   = $previousYears->flatten()->count();
@endphp

<div class="bg-white rounded shadow-sm border border-gray-100 mb-6">

    {{-- Cabecera --}}
    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
        <i data-lucide="book-open" class="w-4 h-4 text-indigo-500"></i>
        <h3 class="font-semibold text-gray-800">Mis Cursos Asignados</h3>
        @if($currentYear)
        <span class="px-2 py-0.5 text-xs bg-indigo-100 text-indigo-700 rounded-full font-medium">
            {{ $currentYear }}
        </span>
        @endif
        <span class="ml-auto text-xs text-gray-400">
            {{ $currentCourses->count() }} {{ $currentCourses->count() === 1 ? 'curso' : 'cursos' }}
        </span>
    </div>

    {{-- Tabla año actual --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-xs text-gray-600 uppercase">
                <tr>
                    <th class="px-6 py-3">Curso</th>
                    <th class="px-6 py-3">Sección</th>
                    <th class="px-6 py-3">Grado</th>
                    <th class="px-6 py-3">Horas / sem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($currentCourses as $course)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-3 font-medium text-gray-800">{{ $course->name }}</td>
                    <td class="px-6 py-3 text-gray-600">{{ $course->section->name }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 text-xs bg-indigo-100 text-indigo-700 rounded-full">
                            Grado {{ $course->section->grade }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-gray-500">{{ $course->hours_per_week }}h</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-400 text-sm">
                        No tienes cursos asignados para este año.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Toggle años anteriores --}}
    @if($previousYears->isNotEmpty())
    <div x-data="{ open: false }" class="border-t border-gray-100">
        <button @click="open = !open"
                class="w-full flex items-center justify-between px-6 py-3 text-sm text-gray-500 hover:bg-gray-50 transition">
            <span class="flex items-center gap-2">
                <i data-lucide="history" class="w-4 h-4"></i>
                Ver años anteriores
                <span class="px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded text-xs font-medium">
                    {{ $totalPrevious }} {{ $totalPrevious === 1 ? 'curso' : 'cursos' }}
                </span>
            </span>
            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"
               :class="open ? 'rotate-180' : ''"></i>
        </button>

        <div x-show="open" x-transition class="border-t border-gray-100">
            @foreach($previousYears as $year => $yearCourses)
            <div class="border-b border-gray-50 last:border-0">
                {{-- Encabezado del año --}}
                <div class="px-6 py-2 bg-gray-50 flex items-center gap-2">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-gray-400"></i>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $year }}</span>
                    <span class="text-xs text-gray-400">· {{ $yearCourses->count() }} {{ $yearCourses->count() === 1 ? 'curso' : 'cursos' }}</span>
                </div>
                {{-- Filas del año --}}
                <table class="w-full text-sm text-left">
                    <tbody class="divide-y divide-gray-50">
                        @foreach($yearCourses as $course)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-2.5 text-gray-600">{{ $course->name }}</td>
                            <td class="px-6 py-2.5 text-gray-500">{{ $course->section->name }}</td>
                            <td class="px-6 py-2.5">
                                <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-500 rounded-full">
                                    Grado {{ $course->section->grade }}
                                </span>
                            </td>
                            <td class="px-6 py-2.5 text-gray-400">{{ $course->hours_per_week }}h</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     PERFIL ALUMNO
     ══════════════════════════════════════════════════════════════════════ --}}
@if($esAlumno)

{{-- ── 2 tarjetas: Datos Personales + Datos Académicos ────────────────── --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

    {{-- Datos Personales --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-4">
            <i data-lucide="id-card" class="w-4 h-4 text-blue-500"></i> Datos Personales
        </h3>
        <p class="text-xs text-gray-400 mb-3 italic">Gestionados por administración.</p>
        <dl class="space-y-2.5 text-sm">
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">DNI</dt>
                <dd class="text-gray-800 font-medium text-right">{{ $sp?->dni ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Fecha de nacimiento</dt>
                <dd class="text-gray-800 font-medium">{{ optional($sp?->fecha_nacimiento)->format('d/m/Y') ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Sexo</dt>
                <dd class="text-gray-800 font-medium">
                    {{ $sp?->sexo === 'M' ? 'Masculino' : ($sp?->sexo === 'F' ? 'Femenino' : '—') }}
                </dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Nacionalidad</dt>
                <dd class="text-gray-800 font-medium">{{ $sp?->nacionalidad ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Tipo de sangre</dt>
                <dd class="text-gray-800 font-medium">{{ $sp?->tipo_sangre ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Teléfono</dt>
                <dd class="text-gray-800 font-medium">{{ $sp?->telefono ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Dirección</dt>
                <dd class="text-gray-800 font-medium text-right">{{ $sp?->direccion ?? '—' }}</dd>
            </div>
            @if($sp?->condicion_especial)
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Condición especial</dt>
                <dd class="text-gray-800 font-medium text-right">{{ $sp->condicion_especial }}</dd>
            </div>
            @endif
        </dl>
    </div>

    {{-- Datos Académicos --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-4">
            <i data-lucide="graduation-cap" class="w-4 h-4 text-blue-500"></i> Datos Académicos
        </h3>
        <dl class="space-y-2.5 text-sm mb-5">
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Código estudiante</dt>
                <dd class="text-gray-800 font-medium">{{ $sp?->codigo_estudiante ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Año de ingreso</dt>
                <dd class="text-gray-800 font-medium">{{ $sp?->anio_ingreso ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Turno</dt>
                <dd class="text-gray-800 font-medium capitalize">{{ $sp?->turno ?? '—' }}</dd>
            </div>
            @if($enrollActual)
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Sección actual</dt>
                <dd class="text-gray-800 font-medium">{{ $enrollActual->section->name }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Grado</dt>
                <dd class="text-gray-800 font-medium">{{ $enrollActual->section->grade }}° Grado</dd>
            </div>
            @endif
        </dl>

        @if($enrollActual)
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
            Cursos matriculados
        </h4>
        <div class="space-y-1.5">
            @foreach($enrollActual->section->courses as $curso)
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-700">{{ $curso->name }}</span>
                <span class="text-xs text-gray-400">
                    {{ $curso->teacher?->name ?? '—' }}
                </span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ── Padres / Apoderados ──────────────────────────────────────────────── --}}
<div class="bg-white rounded shadow-sm border border-gray-100 mb-6">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
        <i data-lucide="users" class="w-4 h-4 text-blue-500"></i>
        <h3 class="font-semibold text-gray-800">Padres / Apoderados</h3>
    </div>

    @if($studentParents->isEmpty() && !$sp?->nombre_apoderado)
    <p class="px-6 py-6 text-sm text-gray-400">No hay padres/apoderados registrados.</p>
    @else
    <div class="divide-y divide-gray-100">

        {{-- Padres vinculados en el sistema --}}
        @foreach($studentParents as $padre)
        @php $pp = $padre->parentProfile; @endphp
        <div class="px-6 py-4 flex items-center gap-4">
            <div class="w-10 h-10 bg-green-100 rounded flex items-center justify-center
                        text-base font-bold text-green-600 flex-shrink-0">
                {{ strtoupper(substr($padre->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-800">{{ $padre->name }}</p>
                <p class="text-xs text-gray-500">{{ $padre->email }}</p>
                @if($padre->pivot?->parentesco)
                <p class="text-xs text-gray-400 mt-0.5 capitalize">{{ $padre->pivot->parentesco }}</p>
                @endif
            </div>
            <dl class="hidden sm:grid grid-cols-2 gap-x-6 gap-y-1 text-xs text-right">
                @if($pp?->telefono)
                <dt class="text-gray-400">Teléfono</dt>
                <dd class="text-gray-700 font-medium">{{ $pp->telefono }}</dd>
                @endif
                @if($pp?->dni)
                <dt class="text-gray-400">DNI</dt>
                <dd class="text-gray-700 font-medium">{{ $pp->dni }}</dd>
                @endif
                @if($pp?->ocupacion)
                <dt class="text-gray-400">Ocupación</dt>
                <dd class="text-gray-700 font-medium">{{ $pp->ocupacion }}</dd>
                @endif
            </dl>
        </div>
        @endforeach

        {{-- Apoderado del perfil del alumno: solo si no hay padres vinculados en el sistema --}}
        @if($studentParents->isEmpty() && $sp?->nombre_apoderado)
        <div class="px-6 py-4 flex items-center gap-4">
            <div class="w-10 h-10 bg-amber-100 rounded flex items-center justify-center
                        text-base font-bold text-amber-600 flex-shrink-0">
                {{ strtoupper(substr($sp->nombre_apoderado, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-800">{{ $sp->nombre_apoderado }}</p>
                <p class="text-xs text-gray-400">Apoderado (sin cuenta en el sistema)</p>
            </div>
            <dl class="hidden sm:grid grid-cols-2 gap-x-6 gap-y-1 text-xs text-right">
                @if($sp->dni_apoderado)
                <dt class="text-gray-400">DNI</dt>
                <dd class="text-gray-700 font-medium">{{ $sp->dni_apoderado }}</dd>
                @endif
                @if($sp->telefono_emergencia)
                <dt class="text-gray-400">Tel. emergencia</dt>
                <dd class="text-gray-700 font-medium">{{ $sp->telefono_emergencia }}</dd>
                @endif
            </dl>
        </div>
        @endif

    </div>
    @endif
</div>

@endif {{-- fin @if($esAlumno) --}}

{{-- ══════════════════════════════════════════════════════════════════════
     PERFIL PADRE / MADRE / APODERADO
     ══════════════════════════════════════════════════════════════════════ --}}
@if($esPadre)

{{-- ── Datos del padre ─────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

    {{-- Datos Personales --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-4">
            <i data-lucide="id-card" class="w-4 h-4 text-green-500"></i> Datos Personales
        </h3>
        <p class="text-xs text-gray-400 mb-3 italic">Gestionados por administración.</p>
        <dl class="space-y-2.5 text-sm">
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">DNI</dt>
                <dd class="text-gray-800 font-medium">{{ $pp?->dni ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Teléfono</dt>
                <dd class="text-gray-800 font-medium">{{ $pp?->telefono ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Dirección</dt>
                <dd class="text-gray-800 font-medium text-right">{{ $pp?->direccion ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Ocupación</dt>
                <dd class="text-gray-800 font-medium">{{ $pp?->ocupacion ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500 shrink-0">Grado de instrucción</dt>
                <dd class="text-gray-800 font-medium capitalize">
                    @php
                        $gradoLabels = [
                            'sin_instruccion' => 'Sin instrucción',
                            'primaria'        => 'Primaria',
                            'secundaria'      => 'Secundaria',
                            'tecnico'         => 'Técnico',
                            'universitario'   => 'Universitario',
                            'posgrado'        => 'Posgrado',
                        ];
                    @endphp
                    {{ $gradoLabels[$pp?->grado_instruccion] ?? ($pp?->grado_instruccion ? ucfirst($pp->grado_instruccion) : '—') }}
                </dd>
            </div>
        </dl>
    </div>

    {{-- Resumen de hijos --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-4">
            <i data-lucide="users" class="w-4 h-4 text-green-500"></i> Mis Hijos Vinculados
            <span class="ml-auto text-xs font-medium bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
                {{ $parentChildren->count() }}
            </span>
        </h3>

        @if($parentChildren->isEmpty())
        <p class="text-sm text-gray-400">No tienes alumnos vinculados. Contacta a administración.</p>
        @else
        <div class="space-y-3">
            @foreach($parentChildren as $item)
            @php $child = $item['student']; @endphp
            <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 {{ $item['atRisk'] ? 'bg-red-50 border-red-100' : 'bg-gray-50' }}">
                <div class="w-10 h-10 bg-indigo-100 rounded flex items-center justify-center
                            text-base font-bold text-indigo-600 flex-shrink-0">
                    {{ strtoupper(substr($child->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-800 text-sm truncate">{{ $child->name }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $item['section']?->name ?? 'Sin sección' }}
                        @if($item['parentesco'])
                            · <span class="capitalize">{{ $item['parentesco'] }}</span>
                        @endif
                    </p>
                </div>
                <div class="flex gap-3 text-center flex-shrink-0">
                    <div>
                        <p class="text-xs text-gray-400">Promedio</p>
                        <p class="text-sm font-bold {{ ($item['avgGrade'] ?? 0) >= 11 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $item['avgGrade'] !== null ? number_format($item['avgGrade'], 1) : '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Asistencia</p>
                        <p class="text-sm font-bold {{ $item['attPct'] >= 70 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $item['attPct'] }}%
                        </p>
                    </div>
                </div>
                <a href="{{ route('padres.show', $child) }}"
                   class="flex-shrink-0 p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Ver detalle">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                </a>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ── Detalle académico de cada hijo ──────────────────────────────────── --}}
@if($parentChildren->isNotEmpty())
<div class="bg-white rounded shadow-sm border border-gray-100 mb-6">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
        <i data-lucide="graduation-cap" class="w-4 h-4 text-green-500"></i>
        <h3 class="font-semibold text-gray-800">Seguimiento Académico</h3>
    </div>
    <div class="divide-y divide-gray-100">
        @foreach($parentChildren as $item)
        @php $child = $item['student']; $sp2 = $child->studentProfile; @endphp
        <div class="px-6 py-5">
            {{-- Cabecera del hijo --}}
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-indigo-100 rounded flex items-center justify-center
                            text-base font-bold text-indigo-600 flex-shrink-0">
                    {{ strtoupper(substr($child->name, 0, 1)) }}
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-800">{{ $child->name }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $item['section']?->name ?? 'Sin sección asignada' }}
                        @if($item['parentesco'])· <span class="capitalize">{{ $item['parentesco'] }}</span>@endif
                    </p>
                </div>
                @if($item['atRisk'])
                <span class="flex items-center gap-1 px-2.5 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">
                    <i data-lucide="alert-triangle" class="w-3 h-3"></i> En riesgo
                </span>
                @endif
                <a href="{{ route('padres.show', $child) }}"
                   class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-medium hover:bg-indigo-700 transition">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i> Ver detalle
                </a>
            </div>

            {{-- Datos personales del alumno --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-400 mb-0.5">DNI</p>
                    <p class="text-sm font-medium text-gray-800">{{ $sp2?->dni ?? '—' }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-400 mb-0.5">Código</p>
                    <p class="text-sm font-medium text-gray-800">{{ $sp2?->codigo_estudiante ?? '—' }}</p>
                </div>
                <div class="bg-{{ ($item['avgGrade'] ?? 0) >= 11 ? 'green' : 'red' }}-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-gray-500 mb-0.5">Promedio general</p>
                    <p class="text-xl font-bold {{ ($item['avgGrade'] ?? 0) >= 11 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $item['avgGrade'] !== null ? number_format($item['avgGrade'], 1) : '—' }}
                    </p>
                </div>
                <div class="bg-{{ $item['attPct'] >= 70 ? 'green' : 'red' }}-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-gray-500 mb-0.5">Asistencia</p>
                    <p class="text-xl font-bold {{ $item['attPct'] >= 70 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $item['attPct'] }}%
                    </p>
                </div>
            </div>

            {{-- Cursos de la sección --}}
            @if($item['section'])
            <div class="mt-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Cursos matriculados</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    @foreach($item['section']->courses ?? [] as $curso)
                    <div class="flex items-center gap-2 text-xs text-gray-700 bg-gray-50 rounded-lg px-3 py-2">
                        <i data-lucide="book" class="w-3.5 h-3.5 text-indigo-400 flex-shrink-0"></i>
                        <span class="truncate">{{ $curso->name }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

@endif {{-- fin @if($esPadre) --}}

{{-- ── Cuenta y contraseña ──────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- Información de la cuenta --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-4">
            <i data-lucide="user" class="w-4 h-4 text-gray-400"></i> Información de la Cuenta
        </h3>
        <dl class="space-y-3 text-sm">
            <div>
                <dt class="text-xs text-gray-400 mb-0.5">Nombre completo</dt>
                <dd class="font-medium text-gray-800">{{ $user->name }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 mb-0.5">Correo electrónico</dt>
                <dd class="font-medium text-gray-800">{{ $user->email }}</dd>
            </div>
        </dl>
        <p class="mt-4 text-xs text-gray-400 bg-gray-50 rounded-lg px-3 py-2.5 leading-relaxed">
            <i data-lucide="info" class="w-3.5 h-3.5 inline-block mr-1 -mt-0.5 text-gray-400"></i>
            El nombre y correo son datos oficiales. Si necesitas corregirlos, comunícate con la administración.
        </p>
    </div>

    {{-- Cambiar contraseña --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-4">
            <i data-lucide="lock" class="w-4 h-4 text-gray-400"></i> Cambiar Contraseña
        </h3>
        <form method="POST" action="{{ route('perfil.updatePassword') }}" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña Actual</label>
                <input type="password" name="current_password" required
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none
                              @error('current_password') border-red-400 @enderror">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nueva Contraseña</label>
                <input type="password" name="password" required
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Nueva Contraseña</label>
                <input type="password" name="password_confirmation" required
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
            <button type="submit"
                    class="w-full px-4 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                Cambiar Contraseña
            </button>
        </form>
    </div>
</div>

@endsection
