{{--
    Partial reutilizable: tabla de calificaciones por curso.
    Variables esperadas:
      $courses     — Collection de Course (con teacher cargado)
      $gradeMatrix — array [course_id][period] => ['score'=>float, 'observation'=>string|null]
      $periods     — array ['I','II','III']
      $overallAvg  — float|null
      $compact     — bool (opcional, false por defecto) → reduce padding para vistas históricas
--}}
@php $compact = $compact ?? false; @endphp

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr style="background:#f9fafb; border-bottom:2px solid #e5e7eb;">
                <th class="{{ $compact ? 'px-4 py-2.5' : 'px-5 py-3.5' }} text-left text-xs font-semibold text-gray-500 uppercase tracking-wide"
                    style="min-width:200px;">
                    Curso
                </th>
                <th class="{{ $compact ? 'px-4 py-2.5' : 'px-5 py-3.5' }} text-left text-xs font-semibold text-gray-500 uppercase tracking-wide hidden sm:table-cell"
                    style="min-width:150px;">
                    Docente
                </th>
                @foreach($periods as $p)
                <th class="{{ $compact ? 'px-3 py-2.5' : 'px-4 py-3.5' }} text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-20">
                    Trim. {{ $p }}
                </th>
                @endforeach
                <th class="{{ $compact ? 'px-3 py-2.5' : 'px-4 py-3.5' }} text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-24"
                    style="border-left:2px solid #e5e7eb;">
                    Promedio
                </th>
                <th class="{{ $compact ? 'px-3 py-2.5' : 'px-4 py-3.5' }} text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-28">
                    Estado
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($courses as $i => $course)
            @php
                $courseGrades = $gradeMatrix[$course->id] ?? [];
                $scores = collect($periods)
                    ->map(fn($p) => $courseGrades[$p]['score'] ?? null)
                    ->filter(fn($v) => $v !== null);
                $avg = $scores->count() > 0 ? round($scores->avg(), 1) : null;
                $approved = $avg !== null && $avg >= 11;
            @endphp
            <tr class="{{ $i % 2 === 0 ? '' : 'bg-gray-50/40' }} hover:bg-indigo-50/20 transition-colors">

                {{-- Curso --}}
                <td class="{{ $compact ? 'px-4 py-2.5' : 'px-5 py-3.5' }}">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 text-white text-xs font-bold"
                             style="background:linear-gradient(135deg,#8b1c30,#6b1427);">
                            {{ $i + 1 }}
                        </div>
                        <span class="font-semibold text-gray-800 leading-tight">{{ $course->name }}</span>
                    </div>
                </td>

                {{-- Docente --}}
                <td class="{{ $compact ? 'px-4 py-2.5' : 'px-5 py-3.5' }} hidden sm:table-cell">
                    <span class="text-gray-400 text-xs">{{ $course->teacher?->name ?? '—' }}</span>
                </td>

                {{-- Nota por trimestre --}}
                @foreach($periods as $p)
                @php $score = $courseGrades[$p]['score'] ?? null; @endphp
                <td class="{{ $compact ? 'px-3 py-2.5' : 'px-4 py-3.5' }} text-center">
                    @if($score !== null)
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-sm font-bold
                            {{ $score >= 11
                                ? 'bg-green-50 text-green-700 ring-1 ring-green-200'
                                : 'bg-red-50 text-red-700 ring-1 ring-red-200' }}">
                            {{ number_format($score, 0) }}
                        </span>
                    @else
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-sm
                                     text-gray-300 bg-gray-50 ring-1 ring-gray-100">
                            —
                        </span>
                    @endif
                </td>
                @endforeach

                {{-- Promedio del curso --}}
                <td class="{{ $compact ? 'px-3 py-2.5' : 'px-4 py-3.5' }} text-center"
                    style="border-left:2px solid #e5e7eb;">
                    @if($avg !== null)
                    <div class="flex flex-col items-center gap-1">
                        <span class="text-base font-extrabold {{ $approved ? 'text-green-700' : 'text-red-600' }}">
                            {{ $avg }}
                        </span>
                        <div class="w-12 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $approved ? 'bg-green-400' : 'bg-red-400' }}"
                                 style="width:{{ ($avg / 20) * 100 }}%"></div>
                        </div>
                    </div>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>

                {{-- Estado --}}
                <td class="{{ $compact ? 'px-3 py-2.5' : 'px-4 py-3.5' }} text-center">
                    @if($avg !== null)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold
                        {{ $approved ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        <i data-lucide="{{ $approved ? 'check-circle' : 'x-circle' }}" class="w-3 h-3"></i>
                        {{ $approved ? 'Aprobado' : 'Desaprobado' }}
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold
                                 bg-gray-100 text-gray-500">
                        <i data-lucide="clock" class="w-3 h-3"></i> Pendiente
                    </span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>

        {{-- Pie: promedios por trimestre --}}
        @php
            $periodAvgs = collect($periods)->mapWithKeys(function($p) use ($courses, $gradeMatrix) {
                $vals = $courses->map(fn($c) => $gradeMatrix[$c->id][$p]['score'] ?? null)
                               ->filter(fn($v) => $v !== null);
                return [$p => $vals->count() > 0 ? round($vals->avg(), 1) : null];
            });
        @endphp
        <tfoot>
            <tr style="border-top:2px solid #e5e7eb; background:#f9fafb;">
                <td class="{{ $compact ? 'px-4 py-2' : 'px-5 py-3' }} text-xs font-bold text-gray-500 uppercase tracking-wide"
                    colspan="2">
                    Promedio por trimestre
                </td>
                @foreach($periods as $p)
                <td class="{{ $compact ? 'px-3 py-2' : 'px-4 py-3' }} text-center">
                    @if($periodAvgs[$p] !== null)
                    <span class="text-sm font-bold {{ $periodAvgs[$p] >= 11 ? 'text-green-700' : 'text-red-600' }}">
                        {{ $periodAvgs[$p] }}
                    </span>
                    @else
                    <span class="text-gray-300 text-sm">—</span>
                    @endif
                </td>
                @endforeach
                <td class="{{ $compact ? 'px-3 py-2' : 'px-4 py-3' }} text-center"
                    style="border-left:2px solid #e5e7eb;">
                    @if($overallAvg !== null)
                    <span class="text-sm font-extrabold {{ $overallAvg >= 11 ? 'text-green-700' : 'text-red-600' }}">
                        {{ $overallAvg }}
                    </span>
                    @else
                    <span class="text-gray-300 text-sm">—</span>
                    @endif
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
