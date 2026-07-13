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

        /* Etiqueta de filtros */
        .filters { background: #f3f4f6; border-radius: 4px; padding: 5px 8px; margin-bottom: 14px; font-size: 10px; color: #374151; }
        .filters strong { color: #1f2937; }

        /* Bloque por curso */
        .course-block { margin-bottom: 18px; }
        .course-title { background: #8b1c30; color: #fff; padding: 4px 8px; font-size: 11px; font-weight: bold; border-radius: 3px 3px 0 0; }
        .course-section { font-size: 9px; font-weight: normal; opacity: .85; margin-left: 6px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 5px 7px; text-align: left; }
        th { background: #f9fafb; color: #374151; font-size: 10px; font-weight: 600; }
        tr:nth-child(even) { background: #f9fafb; }

        /* Fila de promedio */
        .avg-row td { background: #fef3c7; font-weight: 600; font-size: 10px; color: #92400e; }
        .avg-row td:last-child { color: #b45309; }

        /* Pie */
        .footer { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 6px; font-size: 9px; color: #9ca3af; text-align: right; }
    </style>
</head>
<body>

    {{-- Cabecera --}}
    <div class="header">
        <div class="logo-cell">
            <img src="{{ public_path('images/logo-transparent.png') }}">
        </div>
        <div class="text-cell">
            <h1>IE Santa Rosa — Reporte de Calificaciones</h1>
            <div class="meta">
                <span>Generado: {{ now()->translatedFormat('d \d\e F \d\e Y, H:i') }}</span>
                @if($teacher)
                <span>Docente: <strong>{{ $teacher->name }}</strong></span>
                @else
                <span>Vista: <strong>Administrador (todos los cursos)</strong></span>
                @endif
            </div>
        </div>
    </div>

    {{-- Filtros aplicados --}}
    <div class="filters">
        Filtros aplicados:
        <strong>Año escolar: {{ $year }}</strong>
        @if($curso)
            &nbsp;&nbsp;<strong>Curso: {{ $curso->name }} — {{ $curso->section?->name }}</strong>
        @endif
        @if($period)
            &nbsp;&nbsp;<strong>Periodo: {{ $period }}</strong>
        @endif
    </div>

    {{-- Datos agrupados por curso --}}
    @forelse($grades->groupBy('course_id') as $courseId => $courseGrades)
    @php
        $primerRegistro = $courseGrades->first();
        $nombreCurso    = $primerRegistro->course?->name ?? 'Curso desconocido';
        $nombreSeccion  = $primerRegistro->course?->section?->name ?? '';
        $promedio       = round($courseGrades->avg('score'), 1);
    @endphp

    <div class="course-block">
        <div class="course-title">
            {{ $nombreCurso }}
            @if($nombreSeccion)
            <span class="course-section">/ {{ $nombreSeccion }}</span>
            @endif
        </div>
        <table>
            <thead>
                <tr>
                    <th>Alumno</th>
                    <th>Periodo</th>
                    <th style="width:60px; text-align:center">Nota</th>
                </tr>
            </thead>
            <tbody>
                @foreach($courseGrades as $g)
                <tr>
                    <td>{{ $g->student?->name }}</td>
                    <td>{{ $g->period }}</td>
                    <td style="text-align:center; font-weight:600;
                        color: {{ $g->score >= 11 ? '#15803d' : '#dc2626' }}">
                        {{ $g->score }}
                    </td>
                </tr>
                @endforeach
                <tr class="avg-row">
                    <td colspan="2">Promedio del curso</td>
                    <td style="text-align:center">{{ $promedio }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @empty
    <p style="color:#9ca3af; margin-top:20px">No hay calificaciones con los filtros seleccionados.</p>
    @endforelse

    <div class="footer">
        IE Santa Rosa &mdash; Sistema de Gestión Escolar &mdash; Total de registros: {{ $grades->count() }}
    </div>

</body>
</html>
