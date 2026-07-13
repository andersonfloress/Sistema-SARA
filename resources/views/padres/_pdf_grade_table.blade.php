{{--
    Partial de tabla de notas para el reporte PDF de padres.
    Variables esperadas:
      $courses     — Collection de Course (con teacher cargado)
      $gradeMatrix — array [course_id][period] => ['score'=>float, 'observation'=>string|null]
      $periods     — array ['I','II','III']
      $overallAvg  — float|null
--}}
<table style="margin-bottom: 14px;">
    <thead>
        <tr>
            <th style="width:36%">Curso</th>
            <th style="width:20%">Docente</th>
            @foreach($periods as $p)
            <th class="center" style="width:10%">Trim. {{ $p }}</th>
            @endforeach
            <th class="center" style="width:12%">Promedio</th>
        </tr>
    </thead>
    <tbody>
        @forelse($courses as $course)
        @php
            $courseGrades = $gradeMatrix[$course->id] ?? [];
            $scores = collect($periods)
                ->map(fn($p) => $courseGrades[$p]['score'] ?? null)
                ->filter(fn($v) => $v !== null);
            $avg = $scores->count() > 0 ? round($scores->avg(), 1) : null;
            $approved = $avg !== null && $avg >= 11;
        @endphp
        <tr>
            <td>{{ $course->name }}</td>
            <td class="muted">{{ $course->teacher?->name ?? '—' }}</td>
            @foreach($periods as $p)
            @php $score = $courseGrades[$p]['score'] ?? null; @endphp
            <td class="center {{ $score !== null ? ($score >= 11 ? 'approved' : 'failed') : 'muted' }}">
                {{ $score !== null ? number_format($score, 0) : '—' }}
            </td>
            @endforeach
            <td class="center {{ $avg !== null ? ($approved ? 'approved' : 'failed') : 'muted' }}">
                {{ $avg !== null ? $avg : '—' }}
            </td>
        </tr>
        @empty
        <tr><td colspan="{{ 3 + count($periods) }}" class="muted center">Sin cursos registrados.</td></tr>
        @endforelse
    </tbody>
    @if($overallAvg !== null)
    <tfoot>
        <tr class="avg-row">
            <td colspan="{{ 2 + count($periods) }}">Promedio general del año</td>
            <td class="center">{{ $overallAvg }}</td>
        </tr>
    </tfoot>
    @endif
</table>
