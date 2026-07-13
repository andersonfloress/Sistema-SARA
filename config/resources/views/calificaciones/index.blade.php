@extends('layouts.app')
@section('title', 'Calificaciones')
@section('page-title', 'Calificaciones')

@section('content')
<div class="flex gap-5 items-start" x-data="{ q: '' }">

    {{-- ── Panel lateral: secciones ──────────────────────────────────────── --}}
    <div class="w-64 flex-shrink-0">
        <div class="bg-white rounded shadow-sm border border-gray-100 overflow-hidden">

            {{-- Selector de año académico --}}
            @if($years->count() > 1)
            <div class="px-3 pt-3 pb-2 border-b border-gray-100 flex gap-1.5 flex-wrap">
                @foreach($years as $y)
                <a href="{{ route('calificaciones.index', ['year' => $y]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-semibold transition"
                   style="{{ (int)$selectedYear === (int)$y
                       ? 'background:linear-gradient(135deg,#8b1c30,#6b1427); color:#fff;'
                       : 'background:#f3f4f6; color:#6b7280;' }}">
                    {{ $y }}
                </a>
                @endforeach
            </div>
            @endif

            {{-- Selector de grado --}}
            @if($grades->count() > 1)
            <div class="px-3 pt-2 pb-2 border-b border-gray-100 flex gap-1.5 flex-wrap">
                <a href="{{ route('calificaciones.index', ['year' => $selectedYear]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-semibold transition"
                   style="{{ !$selectedGrade
                       ? 'background:#374151; color:#fff;'
                       : 'background:#f3f4f6; color:#6b7280;' }}">
                    Todos
                </a>
                @foreach($grades as $g)
                <a href="{{ route('calificaciones.index', ['year' => $selectedYear, 'grado' => $g]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-semibold transition"
                   style="{{ $selectedGrade === $g
                       ? 'background:#374151; color:#fff;'
                       : 'background:#f3f4f6; color:#6b7280;' }}">
                    {{ $g }}
                </a>
                @endforeach
            </div>
            @endif

            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    Secciones {{ $selectedYear ? '('.$selectedYear.($selectedGrade ? ' · '.$selectedGrade : '').')' : '' }}
                </p>
            </div>

            {{-- Buscador rápido --}}
            @if($sections->count() > 5)
            <div class="px-3 pt-2.5 pb-1">
                <div class="relative">
                    <i data-lucide="search" style="width:14px;height:14px;" class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" x-model="q" placeholder="Buscar sección..."
                           class="w-full pl-8 pr-2 py-1.5 text-xs rounded-lg border border-gray-200 focus:outline-none focus:ring-1 focus:ring-red-300">
                </div>
            </div>
            @endif

            @if($sections->isEmpty())
                <div class="px-4 py-6 text-center text-gray-400 text-xs">
                    Sin secciones disponibles para este año.
                </div>
            @else
                <div class="px-3 pt-2 pb-1">
                    @foreach($sections as $sec)
                    @php
                        $isSelected = $selectedSection?->id === $sec->id;
                        $label = $sec->name;
                    @endphp
                    <a href="{{ route('calificaciones.index', ['section_id' => $sec->id, 'year' => $selectedYear, 'grado' => $selectedGrade]) }}"
                       x-show="q === '' || {{ Illuminate\Support\Js::from(strtolower($label)) }}.includes(q.toLowerCase())"
                       class="flex items-center gap-2.5 w-full px-3 py-2.5 rounded-lg mb-0.5 text-sm font-medium transition-all"
                       style="{{ $isSelected
                           ? 'background:linear-gradient(135deg,#8b1c30,#6b1427); color:#fff;'
                           : 'color:#374151;' }}"
                       onmouseover="{{ $isSelected ? '' : "this.style.background='#fce7eb'; this.style.color='#8b1c30';" }}"
                       onmouseout="{{ $isSelected ? '' : "this.style.background=''; this.style.color='#374151';" }}">
                        <span class="w-6 h-6 rounded-md flex items-center justify-center text-xs font-bold flex-shrink-0"
                              style="{{ $isSelected ? 'background:rgba(255,255,255,0.2); color:#fff;' : 'background:#f3f4f6; color:#6b7280;' }}">
                            {{ strtoupper(substr($sec->name, 0, 1)) }}
                        </span>
                        <span class="truncate">{{ $label }}</span>
                    </a>
                    @endforeach
                </div>
                <div class="h-2"></div>
            @endif
        </div>
    </div>

    {{-- ── Panel principal ────────────────────────────────────────────────── --}}
    <div class="flex-1 min-w-0">

        @if(!$selectedSection)
        {{-- Estado vacío --}}
        <div class="bg-white rounded border border-gray-100 shadow-sm p-14 text-center">
            <div class="w-16 h-16 rounded flex items-center justify-center mx-auto mb-4"
                 style="background:#fce7eb;">
                <i data-lucide="clipboard-list" style="width:28px;height:28px;color:#8b1c30;"></i>
            </div>
            <h3 class="text-base font-semibold text-gray-700 mb-1">Selecciona una sección</h3>
            <p class="text-sm text-gray-400">Elige una sección del panel izquierdo para ver sus calificaciones.</p>
        </div>

        @else

        {{-- ── Cabecera + botón registrar ─────────────────────────────────── --}}
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-bold text-gray-800">
                    {{ $selectedSection->grade ? 'Grado '.$selectedSection->grade : '' }}
                    — Sección {{ $selectedSection->name }}
                    <span class="text-xs font-medium text-gray-400">({{ $selectedSection->year }})</span>
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">{{ $students->count() }} alumno(s)</p>
            </div>
            @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
            <a href="{{ route('calificaciones.create', array_filter(['section_id' => $selectedSection->id, 'course_id' => $selectedCourse?->id])) }}"
               class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm font-semibold shadow-sm transition"
               style="background: linear-gradient(135deg, #8b1c30, #6b1427);"
               onmouseover="this.style.background='linear-gradient(135deg,#a8183b,#8b1c30)'"
               onmouseout="this.style.background='linear-gradient(135deg,#8b1c30,#6b1427)'">
                <i data-lucide="edit-3" style="width:15px;height:15px;"></i>
                Registrar notas
            </a>
            @endif
        </div>

        {{-- ── Tabs de cursos ──────────────────────────────────────────────── --}}
        @if($courses->isNotEmpty())
        <div class="flex gap-2 flex-wrap mb-4">
            @foreach($courses as $c)
            @php
                $isCourse = $selectedCourse?->id === $c->id;
                $prog = $courseProgress[$c->id] ?? null;
            @endphp
            <a href="{{ route('calificaciones.index', ['section_id' => $selectedSection->id, 'course_id' => $c->id, 'year' => $selectedYear, 'grado' => $selectedGrade]) }}"
               class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border transition"
               style="{{ $isCourse
                   ? 'background:linear-gradient(135deg,#8b1c30,#6b1427); color:#fff; border-color:transparent;'
                   : 'background:#fff; color:#374151; border-color:#e5e7eb;' }}"
               onmouseover="{{ $isCourse ? '' : "this.style.background='#fce7eb'; this.style.color='#8b1c30'; this.style.borderColor='#f9a8b4';" }}"
               onmouseout="{{ $isCourse ? '' : "this.style.background='#fff'; this.style.color='#374151'; this.style.borderColor='#e5e7eb';" }}">
                {{ $c->name }}
                @if($prog && $prog['total'] > 0)
                    @if($prog['complete'])
                        <i data-lucide="check-circle-2" style="width:13px;height:13px;" class="{{ $isCourse ? 'text-white' : 'text-green-600' }}"></i>
                    @else
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full {{ $isCourse ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700' }}">
                            {{ $prog['graded'] }}/{{ $prog['total'] }}
                        </span>
                    @endif
                @endif
            </a>
            @endforeach
        </div>
        @endif

        {{-- ── Grilla de notas ─────────────────────────────────────────────── --}}
        @if($selectedCourse && $students->isNotEmpty())
        <div class="bg-white rounded shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background:#f9fafb; border-bottom:2px solid #e5e7eb;">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide"
                                style="min-width:200px;">
                                Nombres y Apellidos
                            </th>
                            @foreach($periods as $p)
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-28">
                                Trimestre {{ $p }}
                            </th>
                            @endforeach
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-28"
                                style="border-left:2px solid #e5e7eb;">
                                Promedio
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($students as $i => $s)
                        @php
                            $scores = collect($periods)
                                ->map(fn($p) => $gradeMatrix[$s->id][$p]['score'] ?? null)
                                ->filter(fn($v) => $v !== null);
                            $avg = $scores->count() > 0
                                ? round($scores->avg(), 1)
                                : null;
                        @endphp
                        <tr class="{{ $i % 2 === 0 ? '' : 'bg-gray-50/40' }} hover:bg-amber-50/30 transition-colors">

                            {{-- Alumno --}}
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                         style="background:linear-gradient(135deg,#8b1c30,#6b1427);">
                                        {{ strtoupper(substr($s->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800 text-sm">{{ $s->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $s->studentProfile?->codigo_estudiante ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>

                            {{-- Nota por trimestre --}}
                            @foreach($periods as $p)
                            @php
                                $entry = $gradeMatrix[$s->id][$p] ?? null;
                                $score = $entry['score'] ?? null;
                                $obs   = $entry['observation'] ?? null;
                            @endphp
                            <td class="px-4 py-3 text-center">
                                @if($score !== null)
                                    <div class="inline-flex flex-col items-center gap-0.5">
                                        <span class="text-base font-bold {{ $score >= 11 ? 'text-gray-800' : 'text-red-600' }}">
                                            {{ number_format($score, 1) }}
                                        </span>
                                        @if($obs)
                                        <span class="text-xs text-gray-400 max-w-[90px] truncate" title="{{ $obs }}">
                                            {{ $obs }}
                                        </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-300 text-lg">—</span>
                                @endif
                            </td>
                            @endforeach

                            {{-- Promedio --}}
                            <td class="px-4 py-3 text-center" style="border-left:2px solid #e5e7eb;">
                                @if($avg !== null)
                                <div class="inline-flex flex-col items-center">
                                    <span class="text-base font-extrabold {{ $avg >= 11 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $avg }}
                                    </span>
                                    <div class="mt-1 w-14 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $avg >= 11 ? 'bg-green-400' : 'bg-red-400' }}"
                                             style="width:{{ ($avg / 20) * 100 }}%"></div>
                                    </div>
                                </div>
                                @else
                                    <span class="text-gray-300 text-lg">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>

                    {{-- Fila de promedios del curso --}}
                    @php
                        $courseAvgs = collect($periods)->mapWithKeys(function($p) use ($students, $gradeMatrix) {
                            $vals = $students->map(fn($s) => $gradeMatrix[$s->id][$p]['score'] ?? null)
                                            ->filter(fn($v) => $v !== null);
                            return [$p => $vals->count() > 0 ? round($vals->avg(), 1) : null];
                        });
                        $allScores = collect($gradeMatrix)->flatMap(fn($ps) => collect($ps)->pluck('score'))->filter(fn($v) => $v !== null);
                        $courseOverall = $allScores->count() > 0 ? round($allScores->avg(), 1) : null;
                    @endphp
                    <tfoot>
                        <tr style="border-top:2px solid #e5e7eb; background:#f9fafb;">
                            <td class="px-5 py-2.5 text-xs font-bold text-gray-500 uppercase tracking-wide">
                                Promedio del curso
                            </td>
                            @foreach($periods as $p)
                            <td class="px-4 py-2.5 text-center">
                                @if($courseAvgs[$p] !== null)
                                    <span class="text-sm font-bold {{ $courseAvgs[$p] >= 11 ? 'text-green-700' : 'text-red-600' }}">
                                        {{ $courseAvgs[$p] }}
                                    </span>
                                @else
                                    <span class="text-gray-300 text-sm">—</span>
                                @endif
                            </td>
                            @endforeach
                            <td class="px-4 py-2.5 text-center" style="border-left:2px solid #e5e7eb;">
                                @if($courseOverall !== null)
                                    <span class="text-sm font-extrabold {{ $courseOverall >= 11 ? 'text-green-700' : 'text-red-600' }}">
                                        {{ $courseOverall }}
                                    </span>
                                @else
                                    <span class="text-gray-300 text-sm">—</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @elseif($selectedCourse)
        <div class="bg-white rounded p-12 text-center border border-gray-100 shadow-sm">
            <i data-lucide="graduation-cap" style="width:36px;height:36px;" class="mx-auto mb-3 text-gray-300"></i>
            <p class="text-gray-500 font-medium">No hay alumnos matriculados en esta sección.</p>
        </div>

        @else
        <div class="bg-white rounded border border-gray-100 shadow-sm p-14 text-center">
            <div class="w-16 h-16 rounded flex items-center justify-center mx-auto mb-4"
                 style="background:#fce7eb;">
                <i data-lucide="book-open" style="width:28px;height:28px;color:#8b1c30;"></i>
            </div>
            <h3 class="text-base font-semibold text-gray-700 mb-1">Selecciona un curso</h3>
            <p class="text-sm text-gray-400">Elige uno de los cursos de arriba para ver sus notas.</p>
        </div>
        @endif

        @endif {{-- end selectedSection --}}
    </div>
</div>
@endsection
