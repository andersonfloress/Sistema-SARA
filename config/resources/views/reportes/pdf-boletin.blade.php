<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; font-size: 11px; color: #1f2937; }

        /* Cabecera institucional: logo colegio + escudo Perú */
        .header { display: table; width: 100%; border-bottom: 3px solid #8b1c30; padding-bottom: 10px; margin-bottom: 12px; }
        .header .logo-cell { display: table-cell; width: 50px; vertical-align: middle; }
        .header .logo-cell img { width: 46px; height: 46px; }
        .header .text-cell { display: table-cell; vertical-align: middle; padding: 0 10px; text-align: center; }
        .header h1 { font-size: 14px; color: #8b1c30; letter-spacing: 0.5px; text-transform: uppercase; }
        .header .subtitle { font-size: 10.5px; color: #374151; margin-top: 2px; }
        .header .meta { font-size: 9px; color: #9ca3af; margin-top: 3px; }
        .header .escudo-cell { display: table-cell; width: 50px; vertical-align: middle; text-align: right; }
        .header .escudo-cell img { width: 40px; }

        /* Tabla de datos del alumno estilo formulario oficial */
        .datos-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .datos-table td { border: 1px solid #9ca3af; padding: 4px 8px; font-size: 10px; }
        .datos-table td.label { background: #f3f4f6; font-weight: 600; color: #374151; width: 18%; }
        .datos-table td.value { width: 32%; }

        .section-title { font-size: 11.5px; font-weight: bold; color: #fff; background: #8b1c30; padding: 5px 8px; margin-bottom: 0; border-radius: 3px 3px 0 0; }

        table.grades { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.grades th, table.grades td { border: 1px solid #9ca3af; padding: 5px 7px; text-align: left; }
        table.grades th { background: #f3f4f6; color: #374151; font-size: 9.5px; font-weight: 700; text-transform: uppercase; text-align: center; }
        table.grades td.curso { text-align: left; font-weight: 600; }
        table.grades td.center { text-align: center; }
        table.grades tr:nth-child(even) { background: #fafafa; }

        .approved { color: #15803d; font-weight: 700; }
        .failed { color: #dc2626; font-weight: 700; }
        .muted { color: #9ca3af; }

        .avg-row td { background: #fef3c7 !important; font-weight: 700; font-size: 10.5px; color: #92400e; }

        /* Resumen de asistencia */
        .att-summary { display: table; width: 100%; margin-bottom: 18px; border: 1px solid #9ca3af; border-top: none; }
        .att-box { display: table-cell; width: 20%; text-align: center; border-right: 1px solid #e5e7eb; padding: 8px 4px; }
        .att-box:last-child { border-right: none; }
        .att-box .n { font-size: 15px; font-weight: bold; }
        .att-box .l { font-size: 8px; color: #6b7280; text-transform: uppercase; }

        /* Firmas */
        .signatures { display: table; width: 100%; margin-top: 46px; }
        .sign-box { display: table-cell; width: 50%; text-align: center; }
        .sign-line { border-top: 1px solid #374151; margin: 0 30px 4px 30px; }
        .sign-label { font-size: 9.5px; color: #4b5563; }

        .footer { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 6px; font-size: 8.5px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>

    {{-- Cabecera institucional --}}
    <div class="header">
        <div class="logo-cell">
            <img src="{{ public_path('images/logo-transparent.png') }}">
        </div>
        <div class="text-cell">
            <h1>Boletín de Información del Estudiante</h1>
            <div class="subtitle">Institución Educativa Secundaria "Santa Rosa" — Puno</div>
            <div class="meta">Año escolar {{ $currentSection?->year ?? '—' }} · Generado {{ now()->translatedFormat('d \d\e F \d\e Y') }}</div>
        </div>
        <div class="escudo-cell">
            <img src="{{ public_path('images/escudo-peru.png') }}">
        </div>
    </div>

    {{-- Datos del alumno, estilo formulario oficial --}}
    <table class="datos-table">
        <tr>
            <td class="label">Nivel</td>
            <td class="value">Secundaria</td>
            <td class="label">Grado y Sección</td>
            <td class="value">{{ $currentSection ? $currentSection->name : '—' }}</td>
        </tr>
        <tr>
            <td class="label">Código de Estudiante</td>
            <td class="value">{{ $alumno->studentProfile?->codigo_estudiante ?? '—' }}</td>
            <td class="label">DNI</td>
            <td class="value">{{ $alumno->studentProfile?->dni ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Apellidos y Nombres</td>
            <td class="value" colspan="3">{{ $alumno->name }}</td>
        </tr>
    </table>

    {{-- Notas del año actual --}}
    <div class="section-title">Calificaciones — Año escolar {{ $currentSection?->year ?? '—' }}</div>
    <table class="grades">
        <thead>
            <tr>
                <th style="width:34%; text-align:left">Curso</th>
                <th style="width:20%; text-align:left">Docente</th>
                @foreach($periods as $p)
                <th style="width:12%">Trimestre {{ $p }}</th>
                @endforeach
                <th style="width:14%">Promedio</th>
            </tr>
        </thead>
        <tbody>
            @forelse($currentCourses as $course)
            @php
                $courseGrades = $gradeMatrix[$course->id] ?? [];
                $scores = collect($periods)
                    ->map(fn($p) => $courseGrades[$p]['score'] ?? null)
                    ->filter(fn($v) => $v !== null);
                $avg = $scores->count() > 0 ? round($scores->avg(), 1) : null;
            @endphp
            <tr>
                <td class="curso">{{ $course->name }}</td>
                <td class="muted">{{ $course->teacher?->name ?? '—' }}</td>
                @foreach($periods as $p)
                @php $score = $courseGrades[$p]['score'] ?? null; @endphp
                <td class="center {{ $score !== null ? ($score >= 11 ? 'approved' : 'failed') : 'muted' }}">
                    {{ $score !== null ? number_format($score, 1) : '—' }}
                </td>
                @endforeach
                <td class="center {{ $avg !== null ? ($avg >= 11 ? 'approved' : 'failed') : 'muted' }}">
                    {{ $avg !== null ? $avg : '—' }}
                </td>
            </tr>
            @empty
            <tr><td colspan="{{ 3 + count($periods) }}" class="muted center">Sin cursos registrados para el año actual.</td></tr>
            @endforelse
        </tbody>
        @if($overallAvg !== null)
        <tfoot>
            <tr class="avg-row">
                <td colspan="{{ 2 + count($periods) }}">Promedio General del Año</td>
                <td class="center">{{ $overallAvg }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    {{-- Asistencia --}}
    <div class="section-title">Resumen de Asistencia</div>
    <div class="att-summary">
        <div class="att-box"><div class="n approved">{{ $present }}</div><div class="l">Presente</div></div>
        <div class="att-box"><div class="n failed">{{ $absent }}</div><div class="l">Ausente</div></div>
        <div class="att-box"><div class="n" style="color:#b45309">{{ $late }}</div><div class="l">Tardanza</div></div>
        <div class="att-box"><div class="n" style="color:#1d4ed8">{{ $justified }}</div><div class="l">Justificado</div></div>
        <div class="att-box"><div class="n {{ $attPct >= 70 ? 'approved' : 'failed' }}">{{ $attPct }}%</div><div class="l">% Asistencia</div></div>
    </div>

    {{-- Firmas --}}
    <div class="signatures">
        <div class="sign-box">
            <div class="sign-line"></div>
            <div class="sign-label">Director(a)</div>
        </div>
        <div class="sign-box">
            <div class="sign-line"></div>
            <div class="sign-label">Tutor(a) / Docente</div>
        </div>
    </div>

    <div class="footer">
        IE Santa Rosa — Sistema de Gestión Escolar — Documento generado automáticamente, no requiere firma digital para uso interno
    </div>

</body>
</html>
