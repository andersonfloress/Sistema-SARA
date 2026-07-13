<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Hoja de Excel para un curso: resumen de asistencia por alumno.
 * Columnas: Alumno | P | A | T | J | Total | %
 *
 * Layout:
 *   Row 1  — Título del curso (celdas combinadas)
 *   Row 2  — Info: docente / sección / año / alumnos
 *   Row 3  — Encabezados
 *   Row 4+ — Datos por alumno
 *   Last   — Total del curso
 */
class CourseAttendanceSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    private const HEADER_ROW = 1;
    private const INFO_ROW   = 2;
    private const COLS_ROW   = 3;
    private const DATA_START = 4;
    private const TOTAL_COLS = 7; // Alumno, P, A, T, J, Total, %

    private $course;
    private Collection $students;
    private array $totals = [];  // totals[student_id] = [P,A,T,J,total,pct]
    private ?int  $year;

    public function __construct(Collection $courseAtts, ?int $year = null)
    {
        $this->year = $year;

        if ($courseAtts->isEmpty()) {
            $this->course   = null;
            $this->students = collect();
            return;
        }

        $this->course = $courseAtts->first()->course;

        $this->students = $courseAtts
            ->map(fn($a) => $a->student)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        // Agrupa por alumno para calcular totales
        $byStudent = $courseAtts->groupBy('student_id');
        foreach ($this->students as $s) {
            $statuses = $byStudent->get($s->id, collect())->pluck('status');
            $p = $statuses->filter(fn($x) => $x === 'present')->count();
            $a = $statuses->filter(fn($x) => $x === 'absent')->count();
            $t = $statuses->filter(fn($x) => $x === 'late')->count();
            $j = $statuses->filter(fn($x) => $x === 'justified')->count();
            $total = $p + $a + $t + $j;
            $this->totals[$s->id] = [
                'P' => $p, 'A' => $a, 'T' => $t, 'J' => $j,
                'total' => $total,
                'pct'   => $total > 0 ? round((($p + $j) / $total) * 100) : 100,
            ];
        }
    }

    // ── Nombre de la hoja ─────────────────────────────────────────────
    public function title(): string
    {
        if (! $this->course) return 'Sin datos';
        $name = $this->course->name ?? 'Curso';
        $sec  = $this->course->section?->name ?? '';
        $raw  = $sec ? "$name ($sec)" : $name;
        $safe = preg_replace('/[\/\\\?\*\[\]:]/', '-', $raw);
        return mb_substr($safe, 0, 31);
    }

    // ── Datos ─────────────────────────────────────────────────────────
    public function array(): array
    {
        if (! $this->course) {
            return [['No hay registros de asistencia con los filtros seleccionados.']];
        }

        $docente = $this->course->teacher?->name ?? '—';
        $seccion = $this->course->section?->name ?? '—';

        // Fila 1: título
        $titleRow    = array_fill(0, self::TOTAL_COLS, '');
        $titleRow[0] = 'RESUMEN DE ASISTENCIA — ' . $this->course->name . ' / ' . $seccion;

        // Fila 2: info
        $infoRow    = array_fill(0, self::TOTAL_COLS, '');
        $infoRow[0] = 'Docente: ' . $docente
                    . '    |    Sección: ' . $seccion
                    . '    |    Año: ' . ($this->year ?? '—')
                    . '    |    Alumnos: ' . $this->students->count();

        // Fila 3: encabezados
        $headRow = ['Alumno', 'Presentes', 'Ausentes', 'Tardanzas', 'Justificados', 'Total', '%'];

        $rows = [$titleRow, $infoRow, $headRow];

        // Filas de alumnos
        foreach ($this->students as $student) {
            $t      = $this->totals[$student->id];
            $rows[] = [
                $student->name,
                $t['P'],
                $t['A'],
                $t['T'],
                $t['J'],
                $t['total'],
                $t['pct'] . '%',
            ];
        }

        // Fila de totales del curso
        $sumP     = array_sum(array_column($this->totals, 'P'));
        $sumA     = array_sum(array_column($this->totals, 'A'));
        $sumT     = array_sum(array_column($this->totals, 'T'));
        $sumJ     = array_sum(array_column($this->totals, 'J'));
        $sumTotal = array_sum(array_column($this->totals, 'total'));
        $avgPct   = count($this->totals) > 0
            ? round(array_sum(array_column($this->totals, 'pct')) / count($this->totals))
            : 0;

        $rows[] = ['TOTAL DEL CURSO', $sumP, $sumA, $sumT, $sumJ, $sumTotal, $avgPct . '%'];

        return $rows;
    }

    // ── Anchos de columna ─────────────────────────────────────────────
    public function columnWidths(): array
    {
        return [
            'A' => 36,  // Alumno
            'B' => 12,  // Presentes
            'C' => 12,  // Ausentes
            'D' => 12,  // Tardanzas
            'E' => 14,  // Justificados
            'F' => 10,  // Total
            'G' => 8,   // %
        ];
    }

    // ── Estilos ───────────────────────────────────────────────────────
    public function styles(Worksheet $sheet): array
    {
        if (! $this->course) return [];

        $lastCol    = 'G';
        $dataRows   = $this->students->count();
        $totalRow   = self::DATA_START + $dataRows;

        // ── Fila 1: título ───────────────────────────────────────────
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '8B1C30']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // ── Fila 2: info ─────────────────────────────────────────────
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['size' => 9, 'italic' => true, 'color' => ['rgb' => '374151']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(16);

        // ── Fila 3: encabezados ───────────────────────────────────────
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                             'color'       => ['rgb' => '374151']]],
        ]);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension(3)->setRowHeight(18);

        // ── Área de datos ─────────────────────────────────────────────
        if ($dataRows > 0) {
            $lastDataRow = $totalRow - 1;

            $sheet->getStyle("A" . self::DATA_START . ":{$lastCol}{$lastDataRow}")->applyFromArray([
                'font'    => ['size' => 9],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                               'color'       => ['rgb' => 'E5E7EB']]],
            ]);

            // Nombre de alumno: negrita, izquierda
            $sheet->getStyle("A" . self::DATA_START . ":A{$lastDataRow}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT,
                                'vertical'   => Alignment::VERTICAL_CENTER],
            ]);

            // Columnas numéricas centradas
            $sheet->getStyle("B" . self::DATA_START . ":{$lastCol}{$lastDataRow}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical'   => Alignment::VERTICAL_CENTER],
            ]);

            // Colores por columna y filas alternas
            $colColors = [
                'B' => ['even' => 'DCFCE7', 'odd' => 'F0FDF4', 'fg' => '166534'],  // P
                'C' => ['even' => 'FEE2E2', 'odd' => 'FEF2F2', 'fg' => '991B1B'],  // A
                'D' => ['even' => 'FEF9C3', 'odd' => 'FEFCE8', 'fg' => '854D0E'],  // T
                'E' => ['even' => 'DBEAFE', 'odd' => 'EFF6FF', 'fg' => '1E40AF'],  // J
                'G' => ['even' => '1E3A5F', 'odd' => '1E3A5F', 'fg' => 'FFFFFF'],  // %
            ];

            for ($r = self::DATA_START; $r <= $lastDataRow; $r++) {
                $isEven = ($r - self::DATA_START) % 2 === 0;
                $sheet->getRowDimension($r)->setRowHeight(16);

                // Fila alterna suave en col A y F
                if (! $isEven) {
                    $sheet->getStyle("A{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID,
                                   'startColor' => ['rgb' => 'F9FAFB']],
                    ]);
                    $sheet->getStyle("F{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID,
                                   'startColor' => ['rgb' => 'F9FAFB']],
                    ]);
                }

                foreach ($colColors as $col => $c) {
                    $bg = $isEven ? $c['even'] : $c['odd'];
                    $sheet->getStyle("{$col}{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID,
                                   'startColor' => ['rgb' => $bg]],
                        'font' => ['bold' => true, 'color' => ['rgb' => $c['fg']], 'size' => 9],
                    ]);
                }
            }
        }

        // ── Fila total del curso ──────────────────────────────────────
        $sheet->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '92400E']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
            'borders'   => [
                'top'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D97706']],
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D97706']],
            ],
        ]);
        $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension($totalRow)->setRowHeight(16);

        return [];
    }
}
