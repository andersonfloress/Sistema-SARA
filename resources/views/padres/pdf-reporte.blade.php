<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; font-size: 11px; color: #1f2937; }

        /* Cabecera */
        .header { display: table; width: 100%; border-bottom: 3px solid #8b1c30; padding-bottom: 10px; margin-bottom: 14px; }
        .header .logo-cell { display: table-cell; width: 52px; vertical-align: middle; }
        .header .logo-cell img { width: 44px; height: 44px; }
        .header .text-cell { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .header h1 { font-size: 15px; color: #8b1c30; margin-bottom: 2px; }
        .header .meta { font-size: 10px; color: #6b7280; }
        .header .meta span { margin-right: 16px; }

        /* Datos del alumno */
        .student-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px 14px; margin-bottom: 16px; display: table; width: 100%; }
        .student-card .col { display: table-cell; vertical-align: top; }
        .student-card .name { font-size: 13px; font-weight: bold; color: #1f2937; }
        .student-card .sub { font-size: 10px; color: #6b7280; margin-top: 2px; }
        .student-card .avg-col { text-align: right; }
        .student-card .avg-value { font-size: 22px; font-weight: bold; }
        .student-card .avg-label { font-size: 9px; color: #6b7280; }

        /* Aviso de riesgo */
        .risk-banner { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 4px; padding: 6px 10px; margin-bottom: 14px; font-size: 10px; font-weight: 600; }

        /* Secciones */
        .section-title { font-size: 12px; font-weight: bold; color: #1f2937; margin: 18px 0 8px 0; padding-bottom: 4px; border-bottom: 1px solid #e5e7eb; }
        .year-title { background: #8b1c30; color: #fff; padding: 4px 8px; font-size: 10.5px; font-weight: bold; border-radius: 3px 3px 0 0; margin-top: 14px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 5px 7px; text-align: left; }
        th { background: #f9fafb; color: #374151; font-size: 9.5px; font-weight: 600; text-transform: uppercase; }
        tr:nth-child(even) { background: #f9fafb; }
        td.center, th.center { text-align: center; }

        .avg-row td { background: #fef3c7; font-weight: 700; font-size: 10px; color: #92400e; }
        .approved { color: #15803d; font-weight: 700; }
        .failed { color: #dc2626; font-weight: 700; }
        .muted { color: #9ca3af; }

        /* Resumen de asistencia */
        .att-summary { display: table; width: 100%; margin-bottom: 4px; }
        .att-box { display: table-cell; width: 25%; text-align: center; border: 1px solid #e5e7eb; padding: 8px 4px; }
        .att-box .n { font-size: 16px; font-weight: bold; }
        .att-box .l { font-size: 8.5px; color: #6b7280; text-transform: uppercase; }
        .att-pct { margin-top: 8px; font-size: 10.5px; }
        .att-pct strong { font-size: 13px; }

        /* Pie */
        .footer { margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 6px; font-size: 9px; color: #9ca3af; text-align: right; }
    </style>
</head>
<body>

    {{-- Cabecera --}}
    <div class="header">
        <div class="logo-cell">
            <img src="{{ public_path('images/logo-transparent.png') }}">
        </div>
        <div class="text-cell">
            <h1>IE Santa Rosa — Reporte Académico</h1>
            <div class="meta">
                <span>Generado: {{ now()->translatedFormat('d \d\e F \d\e Y, H:i') }}</span>
                <span>Solicitado por: <strong>{{ auth()->user()->name }}</strong></span>
            </div>
        </div>
    </div>

    {{-- Datos del alumno --}}
    <div class="student-card">
        <div class="col">
            <p class="name">{{ $alumno->name }}</p>
            <p class="sub">{{ $alumno->email }}</p>
            @if($currentSection)
            <p class="sub">{{ $currentSection->name }} · Año escolar {{ $currentSection->year }}</p>
            @else
            <p class="sub">Sin sección asignada en el año actual</p>
            @endif
        </div>
        @if($overallAvg !== null)
        <div class="col avg-col">
            <p class="avg-value {{ $overallAvg >= 11 ? 'approved' : 'failed' }}">{{ $overallAvg }}</p>
            <p class="avg-label">Promedio general actual</p>
        </div>
        @endif
    </div>

    @if($atRisk)
    <div class="risk-banner">⚠ Este alumno está en riesgo académico (promedio bajo y/o inasistencia elevada).</div>
    @endif

    {{-- ── Notas: año actual ─────────────────────────────────────────── --}}
    <div class="section-title">Calificaciones — Año escolar {{ $currentSection?->year ?? '—' }}</div>

    @if($currentCourses->isEmpty())
    <p class="muted">Sin calificaciones registradas para el año actual.</p>
    @else
    @include('padres._pdf_grade_table', ['courses' => $currentCourses, 'gradeMatrix' => $gradeMatrix, 'periods' => $periods, 'overallAvg' => $overallAvg])
    @endif

    {{-- ── Notas: historial ──────────────────────────────────────────── --}}
    @if(!empty($history))
    <div class="section-title">Historial académico ({{ count($history) }} año{{ count($history) > 1 ? 's' : '' }} anterior{{ count($history) > 1 ? 'es' : '' }})</div>
    @foreach($history as $hist)
    <div class="year-title">
        {{ $hist['section']->year }} — {{ $hist['section']->name }}
    </div>
    @include('padres._pdf_grade_table', ['courses' => $hist['courses'], 'gradeMatrix' => $hist['gradeMatrix'], 'periods' => $periods, 'overallAvg' => $hist['overallAvg']])
    @endforeach
    @endif

    {{-- ── Asistencia ───────────────────────────────────────────────── --}}
    <div class="section-title">Resumen de asistencia (histórico)</div>
    <div class="att-summary">
        <div class="att-box"><div class="n approved">{{ $present }}</div><div class="l">Presente</div></div>
        <div class="att-box"><div class="n failed">{{ $absent }}</div><div class="l">Ausente</div></div>
        <div class="att-box"><div class="n" style="color:#b45309">{{ $late }}</div><div class="l">Tardanza</div></div>
        <div class="att-box"><div class="n" style="color:#1d4ed8">{{ $justified }}</div><div class="l">Justificado</div></div>
    </div>
    <p class="att-pct">
        Porcentaje de asistencia: <strong class="{{ $attPct >= 70 ? 'approved' : 'failed' }}">{{ $attPct }}%</strong>
        &nbsp;&nbsp;({{ $total }} registros en total)
    </p>

    <div class="footer">
        IE Santa Rosa &mdash; Sistema de Gestión Escolar &mdash; Reporte generado automáticamente
    </div>

</body>
</html>
