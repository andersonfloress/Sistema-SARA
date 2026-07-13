@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- ══════════════════════════════════════════════════
     STUDENT: personal stats
══════════════════════════════════════════════════ --}}
@if($isStudentView ?? false)
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 bg-blue-100 rounded flex items-center justify-center flex-shrink-0">
            <i data-lucide="book-open" class="w-6 h-6 text-blue-600"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $myCourses }}</p>
            <p class="text-sm text-gray-500">Mis Cursos</p>
        </div>
    </div>
    <div class="bg-white rounded p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 {{ $myAvgGrade >= 11 ? 'bg-green-100' : 'bg-red-100' }} rounded flex items-center justify-center flex-shrink-0">
            <i data-lucide="star" class="w-6 h-6 {{ $myAvgGrade >= 11 ? 'text-green-600' : 'text-red-500' }}"></i>
        </div>
        <div>
            <p class="text-2xl font-bold {{ $myAvgGrade >= 11 ? 'text-green-600' : 'text-red-500' }}">
                {{ $myAvgGrade > 0 ? $myAvgGrade : '—' }}
            </p>
            <p class="text-sm text-gray-500">Mi Promedio</p>
        </div>
    </div>
    <div class="bg-white rounded p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 {{ $myAttPct >= 70 ? 'bg-green-100' : 'bg-red-100' }} rounded flex items-center justify-center flex-shrink-0">
            <i data-lucide="check-circle" class="w-6 h-6 {{ $myAttPct >= 70 ? 'text-green-600' : 'text-red-500' }}"></i>
        </div>
        <div>
            <p class="text-2xl font-bold {{ $myAttPct >= 70 ? 'text-green-600' : 'text-red-500' }}">{{ $myAttPct }}%</p>
            <p class="text-sm text-gray-500">Mi Asistencia</p>
        </div>
    </div>
</div>

@if(($sectionLabel ?? null))
<div class="mb-5 flex items-center gap-2 text-sm text-gray-500">
    <i data-lucide="layers" class="w-4 h-4 text-indigo-400"></i>
    <span>Sección: <span class="font-semibold text-gray-700">{{ $sectionLabel }}</span></span>
</div>
@endif

{{-- Personal at-risk alert --}}
@if(($atRisk ?? 0) > 0)
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded flex items-center gap-3">
    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500 flex-shrink-0"></i>
    <p class="text-sm text-red-700 font-medium">
        Tu rendimiento académico está en zona de riesgo. Consulta con tu docente o tutor.
    </p>
</div>
@endif

@else
{{-- ══════════════════════════════════════════════════
     ADMIN / TEACHER / PARENT: school or scoped stats
══════════════════════════════════════════════════ --}}

{{-- Selector de trimestre (solo admin y docente) --}}
@if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
<div class="mb-5 flex flex-wrap items-center gap-3">
    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
        <label class="text-sm font-medium text-gray-600 flex items-center gap-1.5">
            <i data-lucide="calendar-range" class="w-4 h-4 text-indigo-400"></i>
            Periodo
        </label>
        <select name="period" onchange="this.form.submit()"
            class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 cursor-pointer">
            <option value="all"  {{ ($period ?? 'all') === 'all'  ? 'selected' : '' }}>Todos los periodos</option>
            <option value="I"    {{ ($period ?? 'all') === 'I'    ? 'selected' : '' }}>Trimestre I</option>
            <option value="II"   {{ ($period ?? 'all') === 'II'   ? 'selected' : '' }}>Trimestre II</option>
            <option value="III"  {{ ($period ?? 'all') === 'III'  ? 'selected' : '' }}>Trimestre III</option>
        </select>
    </form>
    <span class="text-xs text-gray-400 bg-gray-50 border border-gray-100 rounded-full px-3 py-1">
        {{ $periodLabel ?? '' }}
    </span>
</div>
@endif

{{-- Cards de estadísticas --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 bg-blue-100 rounded flex items-center justify-center flex-shrink-0">
            <i data-lucide="graduation-cap" class="w-6 h-6 text-blue-600"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $totalStudents }}</p>
            <p class="text-sm text-gray-500">{{ $isTeacherView ? 'Mis Alumnos' : 'Alumnos' }}</p>
        </div>
    </div>
    <div class="bg-white rounded p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 bg-purple-100 rounded flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $isTeacherView ? 'book-open' : 'user-check' }}" class="w-6 h-6 text-purple-600"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $totalTeachers }}</p>
            <p class="text-sm text-gray-500">{{ $isTeacherView ? 'Mis Cursos' : 'Docentes' }}</p>
        </div>
    </div>
    <div class="bg-white rounded p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 bg-indigo-100 rounded flex items-center justify-center flex-shrink-0">
            <i data-lucide="layers" class="w-6 h-6 text-indigo-600"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $totalSections }}</p>
            <p class="text-sm text-gray-500">{{ $isTeacherView ? 'Mis Secciones' : 'Secciones' }}</p>
        </div>
    </div>
    <div class="bg-white rounded p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 bg-amber-100 rounded flex items-center justify-center flex-shrink-0">
            <i data-lucide="star" class="w-6 h-6 text-amber-600"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $avgGrade }}</p>
            <p class="text-sm text-gray-500">{{ $isTeacherView ? 'Promedio de mis Alumnos' : 'Promedio General' }}</p>
        </div>
    </div>
    <div class="bg-white rounded p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 bg-green-100 rounded flex items-center justify-center flex-shrink-0">
            <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $attPct }}%</p>
            <p class="text-sm text-gray-500">{{ $isTeacherView ? 'Asistencia de mis Secciones' : 'Asistencia' }}</p>
        </div>
    </div>
</div>

{{-- At-risk alert --}}
@if($atRisk > 0)
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded flex items-center gap-3">
    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500 flex-shrink-0"></i>
    <p class="text-sm text-red-700">
        <span class="font-semibold">{{ $atRisk }} alumno(s) en riesgo académico</span>
        (promedio menor a 11).
        @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
            <a href="{{ route('reportes.index') }}" class="underline ml-1">Ver reportes</a>
        @endif
    </p>
</div>
@endif
@endif

{{-- Teacher: per-course breakdown --}}
@if($isTeacherView ?? false)
<div class="mb-6 bg-white rounded shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800 flex items-center gap-2">
            <i data-lucide="bar-chart-3" class="w-4 h-4 text-indigo-500"></i>
            Rendimiento por Curso
        </h2>
    </div>
    <div class="divide-y divide-gray-50">
        @forelse($courseBreakdown as $course)
        <div class="px-6 py-3 flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-gray-800">{{ $course['name'] }}</p>
                @if($course['section'])
                <p class="text-xs text-gray-400">Sección {{ $course['section'] }}</p>
                @endif
            </div>
            <div class="flex items-center gap-6 text-sm">
                <div class="text-right">
                    <p class="text-xs text-gray-400 mb-0.5">Promedio</p>
                    @if($course['avgGrade'] !== null)
                    <span class="font-semibold {{ $course['avgGrade'] >= 11 ? 'text-green-600' : 'text-red-500' }}">
                        {{ $course['avgGrade'] }}
                    </span>
                    @else
                    <span class="text-gray-300 text-xs">Sin notas</span>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-400 mb-0.5">Asistencia</p>
                    @if($course['attPct'] !== null)
                    <span class="font-semibold {{ $course['attPct'] >= 70 ? 'text-green-600' : 'text-red-500' }}">
                        {{ $course['attPct'] }}%
                    </span>
                    @else
                    <span class="text-gray-300 text-xs">Sin datos</span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <p class="px-6 py-4 text-sm text-gray-400">No tienes cursos asignados.</p>
        @endforelse
    </div>
</div>
@endif

{{-- Bottom grid: announcements + events + quick actions --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Recent Announcements --}}
    <div class="bg-white rounded shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800 flex items-center gap-2 text-sm">
                <i data-lucide="megaphone" class="w-4 h-4 text-purple-500"></i>
                Comunicados Recientes
            </h2>
            <a href="{{ route('comunicados.index') }}" class="text-xs text-indigo-500 hover:underline">Ver todos</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($announcements as $ann)
            <div class="px-5 py-3">
                <p class="text-sm font-medium text-gray-800 leading-snug">{{ $ann->title }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $ann->author->name }} · {{ $ann->created_at->diffForHumans() }}</p>
            </div>
            @empty
            <p class="px-5 py-4 text-sm text-gray-400">Sin comunicados recientes.</p>
            @endforelse
        </div>
    </div>

    {{-- Upcoming Events --}}
    <div class="bg-white rounded shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800 flex items-center gap-2 text-sm">
                <i data-lucide="calendar-days" class="w-4 h-4 text-indigo-500"></i>
                Próximos Eventos
            </h2>
            <a href="{{ route('calendario.index') }}" class="text-xs text-indigo-500 hover:underline">Ver todos</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($upcomingEvents as $event)
            <div class="px-5 py-3 flex items-start gap-3">
                <div class="flex-shrink-0 text-center bg-indigo-50 rounded-lg px-2 py-1 min-w-[44px]">
                    <p class="text-[10px] text-indigo-400 uppercase font-medium">{{ $event->event_date->format('M') }}</p>
                    <p class="text-lg font-bold text-indigo-700 leading-none">{{ $event->event_date->format('d') }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-800 leading-snug">{{ $event->title }}</p>
                    @if($event->description)
                    <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $event->description }}</p>
                    @endif
                </div>
            </div>
            @empty
            <p class="px-5 py-4 text-sm text-gray-400">Sin eventos próximos.</p>
            @endforelse
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white rounded shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800 flex items-center gap-2 text-sm">
                <i data-lucide="zap" class="w-4 h-4 text-amber-500"></i>
                Acciones Rápidas
            </h2>
        </div>
        <div class="p-4 grid grid-cols-2 gap-3">
            @if(auth()->user()->isAdmin())
            <a href="{{ route('alumnos.index') }}"
               class="flex flex-col items-center gap-2 p-4 bg-blue-50 rounded hover:bg-blue-100 transition text-center">
                <i data-lucide="graduation-cap" class="w-6 h-6 text-blue-600"></i>
                <span class="text-xs font-medium text-blue-700">Estudiantes</span>
            </a>
            <a href="{{ route('usuarios.index') }}"
               class="flex flex-col items-center gap-2 p-4 bg-gray-50 rounded hover:bg-gray-100 transition text-center">
                <i data-lucide="users" class="w-6 h-6 text-gray-600"></i>
                <span class="text-xs font-medium text-gray-700">Usuarios</span>
            </a>
            <a href="{{ route('calificaciones.create') }}"
               class="flex flex-col items-center gap-2 p-4 bg-amber-50 rounded hover:bg-amber-100 transition text-center">
                <i data-lucide="pen-line" class="w-6 h-6 text-amber-600"></i>
                <span class="text-xs font-medium text-amber-700">Registrar Notas</span>
            </a>
            <a href="{{ route('reportes.index') }}"
               class="flex flex-col items-center gap-2 p-4 bg-green-50 rounded hover:bg-green-100 transition text-center">
                <i data-lucide="file-bar-chart" class="w-6 h-6 text-green-600"></i>
                <span class="text-xs font-medium text-green-700">Reportes</span>
            </a>
            @elseif(auth()->user()->isTeacher())
            <a href="{{ route('calificaciones.create') }}"
               class="flex flex-col items-center gap-2 p-4 bg-amber-50 rounded hover:bg-amber-100 transition text-center">
                <i data-lucide="pen-line" class="w-6 h-6 text-amber-600"></i>
                <span class="text-xs font-medium text-amber-700">Registrar Notas</span>
            </a>
            <a href="{{ route('asistencia.create') }}"
               class="flex flex-col items-center gap-2 p-4 bg-green-50 rounded hover:bg-green-100 transition text-center">
                <i data-lucide="check-square" class="w-6 h-6 text-green-600"></i>
                <span class="text-xs font-medium text-green-700">Asistencia</span>
            </a>
            <a href="{{ route('materiales.create') }}"
               class="flex flex-col items-center gap-2 p-4 bg-blue-50 rounded hover:bg-blue-100 transition text-center">
                <i data-lucide="upload" class="w-6 h-6 text-blue-600"></i>
                <span class="text-xs font-medium text-blue-700">Publicar Material</span>
            </a>
            <a href="{{ route('comunicados.create') }}"
               class="flex flex-col items-center gap-2 p-4 bg-purple-50 rounded hover:bg-purple-100 transition text-center">
                <i data-lucide="megaphone" class="w-6 h-6 text-purple-600"></i>
                <span class="text-xs font-medium text-purple-700">Comunicado</span>
            </a>
            @elseif(auth()->user()->isStudent())
            <a href="{{ route('calificaciones.index') }}"
               class="flex flex-col items-center gap-2 p-4 bg-blue-50 rounded hover:bg-blue-100 transition text-center">
                <i data-lucide="clipboard-list" class="w-6 h-6 text-blue-600"></i>
                <span class="text-xs font-medium text-blue-700">Mis Notas</span>
            </a>
            <a href="{{ route('asistencia.index') }}"
               class="flex flex-col items-center gap-2 p-4 bg-green-50 rounded hover:bg-green-100 transition text-center">
                <i data-lucide="check-square" class="w-6 h-6 text-green-600"></i>
                <span class="text-xs font-medium text-green-700">Mi Asistencia</span>
            </a>
            <a href="{{ route('horarios.index') }}"
               class="flex flex-col items-center gap-2 p-4 bg-indigo-50 rounded hover:bg-indigo-100 transition text-center">
                <i data-lucide="calendar" class="w-6 h-6 text-indigo-600"></i>
                <span class="text-xs font-medium text-indigo-700">Mi Horario</span>
            </a>
            <a href="{{ route('materiales.index') }}"
               class="flex flex-col items-center gap-2 p-4 bg-amber-50 rounded hover:bg-amber-100 transition text-center">
                <i data-lucide="book-open" class="w-6 h-6 text-amber-600"></i>
                <span class="text-xs font-medium text-amber-700">Materiales</span>
            </a>
            @elseif(auth()->user()->isParent())
            <a href="{{ route('padres.index') }}"
               class="flex flex-col items-center gap-2 p-4 bg-blue-50 rounded hover:bg-blue-100 transition text-center col-span-2">
                <i data-lucide="users-2" class="w-6 h-6 text-blue-600"></i>
                <span class="text-xs font-medium text-blue-700">Ver mis hijos</span>
            </a>
            <a href="{{ route('comunicados.index') }}"
               class="flex flex-col items-center gap-2 p-4 bg-purple-50 rounded hover:bg-purple-100 transition text-center col-span-2">
                <i data-lucide="megaphone" class="w-6 h-6 text-purple-600"></i>
                <span class="text-xs font-medium text-purple-700">Comunicados</span>
            </a>
            @endif
        </div>
    </div>
</div>
@endsection
