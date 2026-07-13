@extends('layouts.app')
@section('title', 'Alumnos')
@section('page-title', 'Alumnos')

@section('content')

{{-- ── Búsqueda global por DNI (admin) ───────────────────────────────────── --}}
@if(auth()->user()->isAdmin())
<div class="mb-4">
    <form method="GET" action="{{ route('alumnos.index') }}" class="flex gap-2 max-w-sm">
        @if($selectedSection)
        <input type="hidden" name="section_id" value="{{ $selectedSection->id }}">
        @endif
        <div class="relative flex-1">
            <i data-lucide="id-card" style="width:15px;height:15px;"
               class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" name="dni" value="{{ $dniQuery }}"
                   placeholder="Buscar alumno por DNI…"
                   class="w-full pl-9 pr-3 py-1.5 text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-red-300">
        </div>
        <button type="submit"
                class="px-3 py-1.5 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700 transition">
            Buscar
        </button>
        @if($dniQuery)
        <a href="{{ route('alumnos.index', $selectedSection ? ['section_id' => $selectedSection->id] : []) }}"
           class="px-3 py-1.5 bg-gray-100 text-gray-600 text-sm rounded-lg hover:bg-gray-200 transition flex items-center gap-1">
            <i data-lucide="x" class="w-4 h-4"></i>
        </a>
        @endif
    </form>
</div>
@endif

{{-- ── Resultados de búsqueda por DNI ────────────────────────────────────── --}}
@if($dniQuery)
<div class="bg-white rounded shadow-sm border border-gray-100 mb-5 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
            Resultados para "{{ $dniQuery }}" ({{ $dniResults->count() }})
        </p>
        <a href="{{ route('alumnos.index', $selectedSection ? ['section_id' => $selectedSection->id] : []) }}"
           class="text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1">
            <i data-lucide="x" style="width:13px;height:13px;"></i> Limpiar
        </a>
    </div>
    @if($dniResults->isEmpty())
    <div class="px-4 py-6 text-center text-gray-400 text-xs">
        Ningún alumno con DNI que contenga "{{ $dniQuery }}".
    </div>
    @else
    <div class="divide-y divide-gray-50">
        @foreach($dniResults as $s)
        @php $sec = $s->enrollments->first()?->section; @endphp
        <a href="{{ route('alumnos.show', $s) }}"
           class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50/70 transition-colors group">
            <div class="w-9 h-9 rounded flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                 style="background: linear-gradient(135deg,#8b1c30,#6b1427);">
                {{ strtoupper(substr($s->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-800 text-sm truncate">{{ $s->name }}</p>
                <p class="text-xs text-gray-400 mt-0.5">
                    DNI {{ $s->studentProfile?->dni ?? '—' }}
                    @if($sec)
                        · {{ $sec->name }}
                        @if($sec->year) <span class="text-gray-300">·</span> {{ $sec->year }} @endif
                    @endif
                </p>
            </div>
            <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 group-hover:text-gray-500 flex-shrink-0"></i>
        </a>
        @endforeach
    </div>
    @endif
</div>
@endif

{{-- ── Layout principal ───────────────────────────────────────────────────── --}}
<div class="flex gap-5 items-start" x-data="{ q: '' }">

    {{-- ── Sidebar: acordeón Año → Grado → Sección ──────────────────────── --}}
    @php
        // Precalcular nombre normalizado para el filtro Alpine (sin °, espacios, minúsculas)
        // Ejemplo: "4° H" → "4h", "2° A" → "2a"
    @endphp
    @php
        $availableGrades = $allSections->flatten()->pluck('grade')->unique()->sort()->values();
        // Construir el literal JS con comillas simples para evitar conflicto
        // con el atributo HTML que usa comillas dobles como delimitador.
        // @json() produce {"2025":true} que rompe el atributo x-data="...".
        $openYearsJs = '{' . collect($openYears)
            ->map(fn($v, $k) => "'{$k}':" . ($v ? 'true' : 'false'))
            ->implode(',') . '}';
    @endphp
    <div class="w-64 flex-shrink-0"
         x-data="{
             filterYear:  '',
             filterGrade: '',
             openYears:   {{ $openYearsJs }}
         }">
        <div class="bg-white rounded shadow-sm border border-gray-100 overflow-hidden">

            {{-- Cabecera + filtros --}}
            <div class="px-4 py-3 border-b border-gray-100 space-y-2">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Secciones</p>

                {{-- Select de año --}}
                <select x-model="filterYear"
                        class="w-full text-xs border border-gray-200 rounded-lg px-2.5 py-1.5
                               bg-gray-50 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                    <option value="">Todos los años</option>
                    @foreach($allSections->keys() as $yr)
                        <option value="{{ $yr }}">{{ $yr }}</option>
                    @endforeach
                </select>

                {{-- Pills de grado --}}
                <div class="flex flex-wrap gap-1 pt-0.5">
                    <button @click="filterGrade = ''"
                            :class="filterGrade === '' ? 'bg-red-700 text-white shadow-sm' : 'bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-700'"
                            class="px-2.5 py-1 rounded-md text-xs font-semibold transition-all">
                        Todos
                    </button>
                    @foreach($availableGrades as $gr)
                    <button @click="filterGrade = (filterGrade === '{{ $gr }}') ? '' : '{{ $gr }}'"
                            :class="filterGrade === '{{ $gr }}' ? 'bg-red-700 text-white shadow-sm' : 'bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-700'"
                            class="px-2.5 py-1 rounded-md text-xs font-semibold transition-all">
                        {{ $gr }}°
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Acordeón --}}
            @if($allSections->isEmpty())
                <div class="px-4 py-8 text-center text-gray-400 text-xs">
                    Sin secciones asignadas.
                </div>
            @else
            <div class="overflow-y-auto" style="max-height: 72vh;">
                @foreach($allSections as $year => $yearSections)
                @php
                    $gradeGroups = $yearSections->groupBy('grade');
                    $yearStr     = (string) $year;
                @endphp

                {{-- Mostrar año si coincide con el filtro (o no hay filtro) --}}
                <div x-show="filterYear === '' || filterYear === '{{ $yearStr }}'"
                     class="border-b border-gray-100 last:border-0">

                    {{-- Encabezado de año (toggle manual, deshabilitado cuando hay filtro activo) --}}
                    <button @click="if (filterYear === '') openYears['{{ $yearStr }}'] = !openYears['{{ $yearStr }}']"
                            class="w-full flex items-center justify-between px-4 py-2.5
                                   hover:bg-gray-50 transition-colors text-left"
                            :class="filterYear !== '' ? 'cursor-default' : ''">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar-days"
                               class="text-indigo-400 flex-shrink-0"
                               style="width:13px;height:13px;"></i>
                            <span class="text-xs font-bold text-gray-700 tracking-wide">{{ $year }}</span>
                        </div>
                        <i data-lucide="chevron-right"
                           class="text-gray-400 transition-transform duration-200 flex-shrink-0"
                           style="width:13px;height:13px;"
                           :class="(filterYear !== '' || openYears['{{ $yearStr }}']) ? 'rotate-90' : ''"></i>
                    </button>

                    {{-- Contenido: abierto si el toggle lo dice O si hay filtro de año activo --}}
                    <div x-show="openYears['{{ $yearStr }}'] || filterYear !== ''"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-end="opacity-0">

                        @foreach($gradeGroups as $grade => $gradeSections)
                        {{-- Mostrar grupo de grado si coincide con el filtro --}}
                        <div x-show="filterGrade === '' || filterGrade === '{{ $grade }}'">

                            {{-- Separador de grado --}}
                            <div class="px-4 pt-2 pb-1 flex items-center gap-2">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                    {{ $grade }}° Grado
                                </span>
                                <div class="flex-1 h-px bg-gray-100"></div>
                            </div>

                            {{-- Secciones del grado --}}
                            <div class="px-3 pb-1">
                                @foreach($gradeSections as $sec)
                                @php $isSelected = $selectedSection?->id === $sec->id; @endphp
                                <a href="{{ route('alumnos.index', ['section_id' => $sec->id]) }}"
                                   class="flex items-center gap-2.5 w-full px-3 py-2 rounded-lg mb-0.5
                                          text-sm font-medium transition-all"
                                   style="{{ $isSelected
                                       ? 'background:linear-gradient(135deg,#8b1c30,#6b1427); color:#fff;'
                                       : 'color:#374151;' }}"
                                   onmouseover="{{ $isSelected ? '' : "this.style.background='#fce7eb'; this.style.color='#8b1c30';" }}"
                                   onmouseout="{{ $isSelected ? '' : "this.style.background=''; this.style.color='#374151';" }}">
                                    <span class="w-6 h-6 rounded-md flex items-center justify-center
                                                 text-xs font-bold flex-shrink-0"
                                          style="{{ $isSelected
                                              ? 'background:rgba(255,255,255,0.2); color:#fff;'
                                              : 'background:#f3f4f6; color:#6b7280;' }}">
                                        {{ strtoupper(substr(trim($sec->name), -1)) }}
                                    </span>
                                    <span class="flex-1 min-w-0">
                                        <span class="block truncate">{{ $sec->name }}</span>
                                        @if(!empty($teacherCoursesBySectionId[$sec->id] ?? null) && $teacherCoursesBySectionId[$sec->id]->isNotEmpty())
                                        <span class="block truncate text-[9px] leading-tight mt-0.5 {{ $isSelected ? 'text-red-200' : 'text-gray-400' }}">
                                            {{ $teacherCoursesBySectionId[$sec->id]->implode(' · ') }}
                                        </span>
                                        @endif
                                    </span>
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endforeach

                        <div class="h-1.5"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- ── Panel principal: alumnos ──────────────────────────────────────── --}}
    <div class="flex-1 min-w-0">

        @if(!$selectedSection)
        {{-- Estado vacío --}}
        <div class="bg-white rounded border border-gray-100 shadow-sm p-14 text-center">
            <div class="w-16 h-16 rounded flex items-center justify-center mx-auto mb-4"
                 style="background:#fce7eb;">
                <i data-lucide="users" style="width:28px;height:28px;color:#8b1c30;"></i>
            </div>
            <h3 class="text-base font-semibold text-gray-700 mb-1">Selecciona una sección</h3>
            <p class="text-sm text-gray-400">Elige una sección del panel izquierdo para ver sus alumnos.</p>
        </div>

        @elseif($students->isEmpty())
        {{-- Sección sin alumnos --}}
        <div class="bg-white rounded border border-gray-100 shadow-sm p-14 text-center">
            <i data-lucide="graduation-cap" style="width:36px;height:36px;" class="mx-auto mb-3 text-gray-300"></i>
            <p class="text-gray-500 font-medium">No hay alumnos matriculados en esta sección.</p>
        </div>

        @else
        {{-- Cabecera de sección --}}
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-bold text-gray-800">
                    {{ $selectedSection->name }}
                    <span class="text-gray-400 font-normal text-sm">· {{ $selectedSection->year }}</span>
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">{{ $students->count() }} alumno(s)</p>
            </div>

            {{-- Leyenda de semáforo --}}
            <div class="flex items-center gap-3 text-xs text-gray-500">
                <span class="flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-green-400 inline-block"></span> Al día
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span> Atención
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span> En riesgo
                </span>
            </div>
        </div>

        {{-- Buscador rápido dentro de la sección --}}
        @if($students->count() > 5)
        <div class="relative mb-3">
            <i data-lucide="search" style="width:14px;height:14px;"
               class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" x-model="q"
                   placeholder="Filtrar alumnos de esta sección por nombre…"
                   class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-200
                          focus:outline-none focus:ring-1 focus:ring-red-300">
        </div>
        @endif

        {{-- Lista de alumnos --}}
        <div class="bg-white rounded shadow-sm border border-gray-100 divide-y divide-gray-50">
            @foreach($students as $s)
            @php
                // Semáforo — ahora calculado correctamente en el controlador
                if ($s->atRisk) {
                    $dot = 'bg-red-500';
                } elseif (($s->gradeAvg !== null && $s->gradeAvg < $thresholds['attn_grade']) ||
                          ($s->attPct  !== null && $s->attPct  < $thresholds['attn_att_pct'])) {
                    $dot = 'bg-amber-400';
                } else {
                    $dot = 'bg-green-400';
                }
            @endphp
            <a href="{{ route('alumnos.show', $s) }}"
               x-show="q === '' || {{ Illuminate\Support\Js::from(strtolower($s->name)) }}.includes(q.toLowerCase())"
               class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50/70 transition-colors group">

                {{-- Avatar + semáforo --}}
                <div class="relative flex-shrink-0">
                    <div class="w-10 h-10 rounded flex items-center justify-center text-sm font-bold text-white"
                         style="background: linear-gradient(135deg,#8b1c30,#6b1427);">
                        {{ strtoupper(substr($s->name, 0, 1)) }}
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white {{ $dot }}"></span>
                </div>

                {{-- Nombre + código --}}
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-800 text-sm truncate transition-colors">
                        {{ $s->name }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $s->studentProfile?->codigo_estudiante ?? 'Sin código' }}
                        @if($s->studentProfile?->dni)
                            · DNI {{ $s->studentProfile->dni }}
                        @endif
                    </p>
                </div>

                {{-- Promedio --}}
                <div class="text-center w-20 flex-shrink-0">
                    <p class="text-xs text-gray-400 mb-0.5">Promedio</p>
                    @if($s->gradeAvg !== null)
                        <span class="text-sm font-bold {{ $s->gradeAvg >= 11 ? 'text-gray-800' : 'text-red-600' }}">
                            {{ $s->gradeAvg }}
                        </span>
                        <span class="text-xs text-gray-400"> / 20</span>
                    @else
                        <span class="text-xs text-gray-300">—</span>
                    @endif
                </div>

                {{-- Asistencia --}}
                <div class="w-28 flex-shrink-0">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-400">Asistencia</span>
                        @if($s->attPct !== null)
                            <span class="font-semibold {{ $s->attPct >= 70 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $s->attPct }}%
                            </span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </div>
                    @if($s->attPct !== null)
                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all
                                    {{ $s->attPct >= 70 ? 'bg-green-400' : 'bg-red-400' }}"
                             style="width: {{ $s->attPct }}%"></div>
                    </div>
                    @else
                    <div class="h-1.5 bg-gray-100 rounded-full"></div>
                    @endif
                </div>

                {{-- Ausencias --}}
                <div class="text-center w-16 flex-shrink-0">
                    <p class="text-xs text-gray-400 mb-0.5">Ausencias</p>
                    <span class="text-sm font-semibold
                                 {{ ($s->attAbsent ?? 0) > 0 ? 'text-red-500' : 'text-gray-400' }}">
                        {{ ($s->attAbsent ?? 0) > 0 ? $s->attAbsent : '—' }}
                    </span>
                </div>

                {{-- Flecha --}}
                <i data-lucide="chevron-right"
                   class="w-4 h-4 text-gray-300 group-hover:text-gray-500 flex-shrink-0 transition-colors"></i>
            </a>
            @endforeach
        </div>
        @endif

    </div>
</div>
@endsection
