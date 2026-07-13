<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; font-size: 9px; color: #1f2937; }

        /* ── Cabecera ─────────────────────────── */
        .header { display: table; width: 100%; border-bottom: 3px solid #8b1c30;
                  padding-bottom: 8px; margin-bottom: 12px; }
        .header-logo { display: table-cell; width: 48px; vertical-align: middle; }
        .header-logo img { width: 40px; height: 40px; }
        .header-text { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .header-text h1 { font-size: 13px; color: #8b1c30; font-weight: bold; margin-bottom: 2px; }
        .header-text .meta { font-size: 8.5px; color: #6b7280; }
        .header-text .meta span { margin-right: 14px; }

        /* ── Filtros ──────────────────────────── */
        .filters { background: #f3f4f6; border-left: 3px solid #8b1c30; border-radius: 0 3px 3px 0;
                   padding: 4px 9px; margin-bottom: 14px; font-size: 8.5px; color: #374151; }
        .filters strong { color: #1f2937; }

        /* ── Bloque por curso ─────────────────── */
        .course-block { margin-bottom: 20px; page-break-inside: avoid; }

        .course-header { background: #8b1c30; color: #fff; padding: 5px 9px;
                         border-radius: 4px 4px 0 0; display: table; width: 100%; }
        .ch-left  { display: table-cell; vertical-align: middle; }
        .ch-right { display: table-cell; vertical-align: middle; text-align: right; font-size: 8px; opacity: .85; }
        .course-name { font-size: 11px; font-weight: bold; }
        .course-sec  { font-size: 8.5px; font-weight: normal; opacity: .8; margin-left: 6px; }
        .course-meta { font-size: 8px; opacity: .8; margin-top: 2px; }

        /* ── Tabla de resumen ─────────────────── */
        table.summary { border-collapse: collapse; width: 100%; }
        table.summary th,
        table.summary td { border: 1px solid #d1d5db; padding: 4px 8px; font-size: 8.5px; text-align: center; }
        table.summary th { background: #1e3a5f; color: #fff; font-weight: 700; font-size: 8px; }
        table.summary th.col-name,
        table.summary td.col-name { text-align: left; min-width: 150px; padding-left: 8px; }
        table.summary td.col-name { font-weight: 600; color: #1f2937; }
        table.summary .col-p   { background: #f0fdf4; color: #166534; font-weight: 700; }
        table.summary .col-a   { background: #fef2f2; color: #991b1b; font-weight: 700; }
        table.summary .col-t   { background: #fefce8; color: #854d0e; font-weight: 700; }
        table.summary .col-j   { background: #eff6ff; color: #1e40af; font-weight: 700; }
        table.summary .col-pct { background: #1e3a5f; color: #fff; font-weight: 700; min-width: 40px; }
        tr.summary-total td { background: #fef3c7 !important; font-weight: 700; color: #92400e; }
        tr:nth-child(even) td { background: #f9fafb; }
        tr:nth-child(even) td.col-p { background: #dcfce7; }
        tr:nth-child(even) td.col-a { background: #fee2e2; }
        tr:nth-child(even) td.col-t { background: #fef9c3; }
        tr:nth-child(even) td.col-j { background: #dbeafe; }
        tr:nth-child(even) td.col-pct { background: #1e3a5f; }

        /* ── Pie ──────────────────────────────── */
        .footer { margin-top: 16px; border-top: 1px solid #e5e7eb; padding-top: 5px;
                  font-size: 7.5px; color: #9ca3af; display: table; width: 100%; }
        .footer-l { display: table-cell; }
        .footer-r { display: table-cell; text-align: right; }
    </style>
</head>
<body>

    {{-- ── Cabecera ─────────────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-logo">
            <img src="{{ public_path('images/logo-transparent.png') }}">
        </div>
        <div class="header-text">
            <h1>IE Santa Rosa — Resumen de Asistencia</h1>
            <div class="meta">
                <span>Año escolar: <strong>{{ $year }}</strong></span>
                @if($teacher)
                    <span>Docente: <strong>{{ $teacher->name }}</strong></span>
                @else
                    <span>Vista: <strong>Administrador</strong></span>
                @endif
                <span>Generado: {{ now()->translatedFormat('d \d\e F \d\e Y, H:i') }}</span>
            </div>
        </div>
    </div>

    {{-- ── Filtros ────────────────────────────────────────────────────── --}}
    <div class="filters">
        Filtros: <strong>Año {{ $year }}</strong>
        &nbsp;·&nbsp; Periodo: <strong>{{ $periodLabel }}</strong>
        @if($curso)
            &nbsp;·&nbsp; Curso: <strong>{{ $curso->name }}</strong>
            &nbsp;·&nbsp; Sección: <strong>{{ $curso->section?->name }}</strong>
        @else
            &nbsp;·&nbsp; <strong>Todos los cursos</strong>
        @endif
    </div>

    {{-- ── Bloques por curso ──────────────────────────────────────────── --}}
    @forelse($courseMatrix as $cm)
    @php
        $docente      = $cm['course']->teacher?->name ?? '—';
        $seccion      = $cm['course']->section?->name ?? '';
        $totalAlumnos = $cm['students']->count();
        $totalesArr   = collect(array_values($cm['totals']));
        $sumP         = $totalesArr->sum('P');
        $sumA         = $totalesArr->sum('A');
        $sumT         = $totalesArr->sum('T');
        $sumJ         = $totalesArr->sum('J');
        $sumTotal     = $totalesArr->sum('total');
        $avgPct       = $totalesArr->count() > 0 ? round($totalesArr->avg('pct')) : 0;
    @endphp

    <div class="course-block">

        {{-- Encabezado del curso --}}
        <div class="course-header">
            <div class="ch-left">
                <span class="course-name">{{ $cm['course']->name }}</span>
                @if($seccion)
                <span class="course-sec">/ {{ $seccion }}</span>
                @endif
                <div class="course-meta">Docente: {{ $docente }}</div>
            </div>
            <div class="ch-right">
                {{ $totalAlumnos }} alumno{{ $totalAlumnos !== 1 ? 's' : '' }}
            </div>
        </div>

        {{-- Tabla de resumen: un alumno por fila --}}
        <table class="summary">
            <thead>
                <tr>
                    <th class="col-name">Alumno</th>
                    <th class="col-p">P</th>
                    <th class="col-a">A</th>
                    <th class="col-t">T</th>
                    <th class="col-j">J</th>
                    <th>Total</th>
                    <th class="col-pct">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cm['students'] as $student)
                @php $t = $cm['totals'][$student->id]; @endphp
                <tr>
                    <td class="col-name">{{ $student->name }}</td>
                    <td class="col-p">{{ $t['P'] }}</td>
                    <td class="col-a">{{ $t['A'] }}</td>
                    <td class="col-t">{{ $t['T'] }}</td>
                    <td class="col-j">{{ $t['J'] }}</td>
                    <td>{{ $t['total'] }}</td>
                    <td class="col-pct">{{ $t['pct'] }}%</td>
                </tr>
                @endforeach

                {{-- Total del curso --}}
                <tr class="summary-total">
                    <td class="col-name">Total del curso</td>
                    <td>{{ $sumP }}</td>
                    <td>{{ $sumA }}</td>
                    <td>{{ $sumT }}</td>
                    <td>{{ $sumJ }}</td>
                    <td>{{ $sumTotal }}</td>
                    <td>{{ $avgPct }}%</td>
                </tr>
            </tbody>
        </table>

    </div>{{-- .course-block --}}
    @empty
    <p style="color:#9ca3af; margin-top:20px; text-align:center">
        No hay registros de asistencia con los filtros seleccionados.
    </p>
    @endforelse

    <div class="footer">
        <div class="footer-l">IE Santa Rosa — Sistema de Gestión Escolar</div>
        <div class="footer-r">Año {{ $year }} — Resumen de Asistencia</div>
    </div>

</body>
</html>
