@extends('layouts.app')
@section('title', 'Reportes')
@section('page-title', 'Reportes Estadísticos')

@section('content')
@php $esDocente = auth()->user()->isTeacher(); @endphp

{{-- Filtros + Exportar -------------------------------------------------------}}
@php
$cursosJson = $cursos->map(fn($c) => [
    'id'         => $c->id,
    'name'       => $c->name,
    'section'    => $c->section?->name ?? '',
    'section_id' => $c->section?->id ?? '',
    'grade'      => $c->section?->grade ?? '',
])->values()->toJson();
@endphp
<div class="mb-6 bg-white rounded shadow-sm border border-gray-100 p-5"
     x-data="{
         cursoId: '',
         seccionFiltro: '',
         gradoFiltro: '',
         periodo: '',
         anio: '{{ $selectedYear }}',
         cursos: {{ $cursosJson }},

         get porGrado() {
             if (!this.gradoFiltro) return this.cursos;
             return this.cursos.filter(c => String(c.grade) === String(this.gradoFiltro));
         },
         get seccionesDisponibles() {
             const seen = new Set();
             return this.porGrado
                 .filter(c => { if (seen.has(c.section_id)) return false; seen.add(c.section_id); return true; })
                 .map(c => ({ id: c.section_id, name: c.section }))
                 .sort((a,b) => a.name.localeCompare(b.name));
         },
         get cursosFiltered() {
             let list = this.porGrado;
             if (this.seccionFiltro) list = list.filter(c => String(c.section_id) === String(this.seccionFiltro));
             return list;
         },
         resetGrado()   { this.seccionFiltro = ''; this.cursoId = ''; },
         resetSeccion() { this.cursoId = ''; },

         urlCals(fmt)  { return '/reportes/calificaciones/' + fmt + '?year=' + this.anio + (this.cursoId ? '&course_id=' + this.cursoId : '') + (this.periodo ? '&period=' + this.periodo : '') },
         urlAsis(fmt)  { return '/reportes/asistencia/'    + fmt + '?year=' + this.anio + (this.cursoId ? '&course_id=' + this.cursoId : '') + (this.periodo ? '&period=' + this.periodo : '') },
     }">

    <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <i data-lucide="sliders-horizontal" class="w-4 h-4 text-indigo-500"></i>
        Filtrar y Exportar
    </h3>

    {{-- Selector de año escolar: recarga la página para reflejar el año en todas las estadísticas --}}
    <form method="GET" action="{{ route('reportes.index') }}" class="mb-4 flex items-center gap-2">
        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Año escolar</label>
        <select name="year" onchange="this.form.submit()"
                class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-indigo-300 outline-none">
            @foreach($availableYears as $y)
            <option value="{{ $y }}" {{ (int) $selectedYear === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
        <span class="text-xs text-gray-400">Todas las estadísticas y exportaciones de esta página son de este año.</span>
    </form>

    {{-- Fila de filtros en cascada: Grado → Sección → Curso → Periodo --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-4">

        {{-- 1. Grado --}}
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">
                <span class="inline-flex items-center gap-1">
                    <span class="w-4 h-4 rounded-full bg-indigo-100 text-indigo-600 text-[10px] font-bold flex items-center justify-center">1</span>
                    Grado
                </span>
            </label>
            <select x-model="gradoFiltro" @change="resetGrado()"
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
                <option value="" disabled selected>— Seleccionar grado —</option>
                @foreach(range(1,5) as $g)
                <option value="{{ $g }}">{{ $g }}° Grado</option>
                @endforeach
            </select>
        </div>

        {{-- 2. Sección (habilitada solo si hay grado seleccionado) --}}
        <div>
            <label class="block text-xs font-medium mb-1" :class="gradoFiltro ? 'text-gray-500' : 'text-gray-300'">
                <span class="inline-flex items-center gap-1">
                    <span class="w-4 h-4 rounded-full text-[10px] font-bold flex items-center justify-center"
                          :class="gradoFiltro ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400'">2</span>
                    Sección
                </span>
            </label>
            <select x-model="seccionFiltro" @change="resetSeccion()"
                    :disabled="!gradoFiltro"
                    class="w-full text-sm border rounded-lg px-3 py-2 outline-none transition"
                    :class="gradoFiltro
                        ? 'border-gray-200 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400'
                        : 'border-gray-100 bg-gray-50 text-gray-300 cursor-not-allowed'">
                <option value="" disabled selected>— Seleccionar sección —</option>
                <template x-for="s in seccionesDisponibles" :key="s.id">
                    <option :value="s.id" x-text="s.name"></option>
                </template>
            </select>
        </div>

        {{-- 3. Curso (habilitado solo si hay sección seleccionada) --}}
        <div>
            <label class="block text-xs font-medium mb-1"
                   :class="seccionFiltro ? 'text-gray-500' : 'text-gray-300'">
                <span class="inline-flex items-center gap-1">
                    <span class="w-4 h-4 rounded-full text-[10px] font-bold flex items-center justify-center"
                          :class="seccionFiltro ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400'">3</span>
                    Curso
                    <span x-show="cursosFiltered.length > 0 && seccionFiltro"
                          class="text-gray-400 font-normal"
                          x-text="'(' + cursosFiltered.length + ')'"></span>
                </span>
            </label>
            <select x-model="cursoId"
                    :disabled="!seccionFiltro"
                    class="w-full text-sm border rounded-lg px-3 py-2 outline-none transition"
                    :class="seccionFiltro
                        ? 'border-gray-200 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400'
                        : 'border-gray-100 bg-gray-50 text-gray-300 cursor-not-allowed'">
                <option value="" disabled selected>— Seleccionar curso —</option>
                <template x-for="c in cursosFiltered" :key="c.id">
                    <option :value="c.id" x-text="c.name"></option>
                </template>
            </select>
        </div>

        {{-- 4. Periodo (solo para calificaciones) --}}
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">
                <span class="inline-flex items-center gap-1">
                    <span class="w-4 h-4 rounded-full bg-indigo-100 text-indigo-600 text-[10px] font-bold flex items-center justify-center">4</span>
                    Periodo
                    <span class="text-gray-400 font-normal">(calificaciones)</span>
                </span>
            </label>
            <select x-model="periodo"
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
                <option value="">Todos los periodos</option>
                <option value="I">Trimestre I</option>
                <option value="II">Trimestre II</option>
                <option value="III">Trimestre III</option>
            </select>
        </div>
    </div>

    {{-- Botones de exportación: solo visibles cuando se ha elegido un curso --}}
    <div x-show="cursoId" x-transition class="flex flex-wrap gap-3">
        <a :href="urlCals('pdf')"
           class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-700 rounded-lg text-sm font-medium hover:bg-red-100 transition">
            <i data-lucide="file-text" class="w-4 h-4"></i> Calificaciones (PDF)
        </a>
        <a :href="urlCals('excel')"
           class="flex items-center gap-2 px-4 py-2 bg-green-50 text-green-700 rounded-lg text-sm font-medium hover:bg-green-100 transition">
            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Calificaciones (Excel)
        </a>
        <a :href="urlAsis('pdf')"
           class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-700 rounded-lg text-sm font-medium hover:bg-red-100 transition">
            <i data-lucide="file-text" class="w-4 h-4"></i> Asistencia (PDF)
        </a>
        <a :href="urlAsis('excel')"
           class="flex items-center gap-2 px-4 py-2 bg-green-50 text-green-700 rounded-lg text-sm font-medium hover:bg-green-100 transition">
            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Asistencia (Excel)
        </a>
        <a href="{{ route('reportes.boletin') }}"
           class="flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg text-sm font-medium hover:bg-indigo-100 transition">
            <i data-lucide="graduation-cap" class="w-4 h-4"></i> Boletín individual del alumno
        </a>
    </div>
    {{-- Mensaje guía cuando aún no se ha seleccionado curso --}}
    <div x-show="!cursoId" class="flex items-center gap-2 text-sm text-gray-400 py-1">
        <i data-lucide="info" class="w-4 h-4 flex-shrink-0"></i>
        Selecciona grado, sección y curso para habilitar la descarga de reportes.
    </div>
</div>

{{-- Tarjetas de resumen -------------------------------------------------------}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded p-5 shadow-sm border border-gray-100">
        <p class="text-xs text-gray-500 uppercase mb-1">
            {{ $esDocente ? 'Mis Alumnos' : 'Total Alumnos' }}
        </p>
        <p class="text-3xl font-bold text-gray-800">{{ $totalStudents }}</p>
    </div>
    <div class="bg-white rounded p-5 shadow-sm border border-gray-100">
        <p class="text-xs text-gray-500 uppercase mb-1">
            {{ $esDocente ? 'Mi Promedio' : 'Promedio General' }}
        </p>
        <p class="text-3xl font-bold {{ $avgGrade >= 11 ? 'text-green-600' : 'text-red-600' }}">{{ $avgGrade }}</p>
    </div>
    <div class="bg-white rounded p-5 shadow-sm border border-gray-100">
        <p class="text-xs text-gray-500 uppercase mb-1">% Asistencia</p>
        <p class="text-3xl font-bold text-blue-600">{{ $attPct }}%</p>
    </div>
    <div class="bg-white rounded p-5 shadow-sm border border-gray-100">
        <p class="text-xs text-gray-500 uppercase mb-1">En Riesgo</p>
        <p class="text-3xl font-bold text-red-600">{{ $atRisk }}</p>
        <p class="text-xs text-gray-400 mt-1">Promedio &lt; 11</p>
    </div>
    <div class="bg-white rounded p-5 shadow-sm border border-gray-100">
        <p class="text-xs text-gray-500 uppercase mb-1">Destacados</p>
        <p class="text-3xl font-bold text-green-600">{{ $outstanding }}</p>
        <p class="text-xs text-gray-400 mt-1">Promedio ≥ 18</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Promedio por trimestre --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i data-lucide="bar-chart-2" class="w-4 h-4 text-indigo-500"></i> Promedio por Trimestre
        </h3>
        <div class="space-y-4">
            @foreach(['I' => 'Trimestre I', 'II' => 'Trimestre II', 'III' => 'Trimestre III'] as $p => $label)
            @php $avg = $gradesByPeriod[$p] ?? 0; @endphp
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">{{ $label }}</span>
                    <span class="font-semibold {{ $avg >= 11 ? 'text-green-600' : 'text-red-600' }}">{{ $avg }}</span>
                </div>
                <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full {{ $avg >= 11 ? 'bg-green-500' : 'bg-red-500' }} rounded-full transition-all"
                         style="width: {{ min(100, ($avg / 20) * 100) }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Distribución de asistencia --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i data-lucide="check-square" class="w-4 h-4 text-indigo-500"></i> Distribución de Asistencia
        </h3>
        @php
            $attStatusConfig = [
                'present'   => ['Presente',   'bg-green-500'],
                'absent'    => ['Ausente',     'bg-red-500'],
                'late'      => ['Tardanza',    'bg-yellow-500'],
                'justified' => ['Justificado', 'bg-blue-500'],
            ];
            $attTotalCount = $attByStatus->sum();
        @endphp
        <div class="space-y-3">
            @foreach($attStatusConfig as $key => [$label, $color])
            @php
                $count = $attByStatus->get($key, 0);
                $pct   = $attTotalCount > 0 ? round(($count / $attTotalCount) * 100, 1) : 0;
            @endphp
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">{{ $label }}</span>
                    <span class="text-gray-500">{{ $count }} ({{ $pct }}%)</span>
                </div>
                <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full {{ $color }} rounded-full" style="width: {{ $pct }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Secciones con mejor rendimiento --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6 lg:col-span-2">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i data-lucide="trophy" class="w-4 h-4 text-amber-500"></i>
            {{ $esDocente ? 'Mis Secciones por Rendimiento' : 'Secciones con Mejor Rendimiento' }}
        </h3>
        @forelse($topSections as $i => $sec)
        <div class="flex items-center gap-4 mb-3">
            <span class="w-7 h-7 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold
                {{ $i === 0 ? 'bg-amber-100 text-amber-700' : ($i === 1 ? 'bg-gray-100 text-gray-600' : 'bg-orange-50 text-orange-600') }}">
                {{ $i + 1 }}
            </span>
            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm font-medium text-gray-800">{{ $sec->name }}</span>
                    <span class="text-sm font-bold {{ $sec->avg >= 11 ? 'text-green-600' : 'text-red-600' }}">{{ $sec->avg }}</span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-500 rounded-full" style="width: {{ ($sec->avg / 20) * 100 }}%"></div>
                </div>
            </div>
        </div>
        @empty
        <p class="text-gray-400 text-sm">Sin datos.</p>
        @endforelse
    </div>
</div>
@endsection
