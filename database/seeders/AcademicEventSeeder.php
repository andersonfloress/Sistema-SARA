<?php

namespace Database\Seeders;

use App\Models\AcademicEvent;
use App\Models\User;
use Illuminate\Database\Seeder;

class AcademicEventSeeder extends Seeder
{
    public function run(): void
    {
        $admin   = User::where('role', 'admin')->first();
        $teacher = User::where('role', 'teacher')->first();

        // Eventos del año escolar 2026 (pasados + futuros desde julio 2026)
        $events = [
            // Pasados 2026 (contexto del año escolar)
            ['title' => 'Inicio del año escolar 2026',
             'description' => 'Primer día de clases del año escolar 2026. Bienvenida a toda la comunidad educativa de IE Santa Rosa.',
             'event_date' => '2026-03-02', 'target_role' => 'all', 'author_id' => $admin->id],

            ['title' => 'Matrícula extemporánea',
             'description' => 'Último día para regularizar la matrícula del año escolar 2026. Presentar documentos en Secretaría.',
             'event_date' => '2026-03-13', 'target_role' => 'parent', 'author_id' => $admin->id],

            ['title' => 'Reunión de padres — 1er Bimestre',
             'description' => 'Reunión informativa con padres y apoderados para presentar el plan académico del Primer Bimestre. Auditorio principal, 5:00 p.m.',
             'event_date' => '2026-03-20', 'target_role' => 'parent', 'author_id' => $admin->id],

            ['title' => 'Cierre del 1er Bimestre',
             'description' => 'Fecha límite para el registro de calificaciones y asistencias del Primer Bimestre en el sistema institucional.',
             'event_date' => '2026-04-24', 'target_role' => 'teacher', 'author_id' => $admin->id],

            ['title' => 'Semana Santa — Vacaciones',
             'description' => 'Vacaciones de Semana Santa. No hay clases del 30 de marzo al 3 de abril. Las actividades se reanudan el 6 de abril.',
             'event_date' => '2026-03-30', 'target_role' => 'all', 'author_id' => $admin->id],

            ['title' => 'Olimpiada de Matemáticas — Fase Interna',
             'description' => 'Primera fase de la Olimpiada de Matemáticas. Participan todos los estudiantes inscritos. Sala de cómputo, 8:00 a.m.',
             'event_date' => '2026-04-27', 'target_role' => 'student', 'author_id' => $teacher->id],

            ['title' => 'Día de la Madre — Actuación Escolar',
             'description' => 'Gran actuación escolar por el Día de la Madre. Participación de todos los grados con números artísticos. Auditorio principal, 9:00 a.m.',
             'event_date' => '2026-05-08', 'target_role' => 'all', 'author_id' => $admin->id],

            ['title' => 'Cierre del 2do Bimestre',
             'description' => 'Plazo máximo para el registro de calificaciones y asistencias del Segundo Bimestre.',
             'event_date' => '2026-06-26', 'target_role' => 'teacher', 'author_id' => $admin->id],

            ['title' => 'Día del Maestro — Sin clases',
             'description' => 'Día no laborable en homenaje a todos los docentes de IE Santa Rosa.',
             'event_date' => '2026-07-06', 'target_role' => 'all', 'author_id' => $admin->id],

            // PRÓXIMOS (desde julio 2026 en adelante)
            ['title' => 'Fiestas Patrias — Desfile escolar',
             'description' => 'Participación de IE Santa Rosa en el desfile por las Fiestas Patrias. Punto de concentración: Plaza de Armas, 8:30 a.m.',
             'event_date' => '2026-07-28', 'target_role' => 'all', 'author_id' => $admin->id],

            ['title' => 'Regreso de vacaciones de medio año',
             'description' => 'Retorno a clases después de las vacaciones de medio año. Turno mañana: 07:20 a.m. Turno tarde: 12:50 p.m.',
             'event_date' => '2026-08-03', 'target_role' => 'all', 'author_id' => $admin->id],

            ['title' => 'Reunión de padres — 3er Bimestre',
             'description' => 'Reunión informativa con padres y apoderados para revisar el avance académico del Segundo Bimestre e inicio del Tercero. Auditorio principal, 5:00 p.m.',
             'event_date' => '2026-08-14', 'target_role' => 'parent', 'author_id' => $admin->id],

            ['title' => 'Concurso de Oratoria Escolar',
             'description' => 'Final del Concurso de Oratoria Escolar con participación de los mejores representantes de cada grado. Auditorio principal, 10:00 a.m.',
             'event_date' => '2026-08-21', 'target_role' => 'all', 'author_id' => $teacher->id],

            ['title' => 'Cierre del 3er Bimestre',
             'description' => 'Plazo máximo para el registro de calificaciones del Tercer Bimestre. Recordar subir también el informe de asistencias.',
             'event_date' => '2026-09-11', 'target_role' => 'teacher', 'author_id' => $admin->id],

            ['title' => 'Día de la Primavera y del Estudiante',
             'description' => 'Celebración del Día de la Primavera con actividades recreativas y deportivas para todos los estudiantes.',
             'event_date' => '2026-09-23', 'target_role' => 'student', 'author_id' => $admin->id],

            ['title' => 'Exámenes finales — 4to Bimestre',
             'description' => 'Semana de evaluaciones finales del Cuarto Bimestre. Consultar el calendario de exámenes por sección en el tablón de anuncios.',
             'event_date' => '2026-11-09', 'target_role' => 'student', 'author_id' => $teacher->id],

            ['title' => 'Reunión de padres — Resultados finales',
             'description' => 'Reunión con padres de familia para la entrega y explicación de calificaciones finales del año escolar. Traer DNI del apoderado.',
             'event_date' => '2026-12-17', 'target_role' => 'parent', 'author_id' => $admin->id],

            ['title' => 'Clausura del año escolar 2026',
             'description' => 'Ceremonia de clausura y entrega de libretas finales del año escolar 2026. Presencia obligatoria de padres y apoderados. Auditorio principal, 9:00 a.m.',
             'event_date' => '2026-12-18', 'target_role' => 'all', 'author_id' => $admin->id],
        ];

        foreach ($events as $e) {
            AcademicEvent::create($e);
        }
    }
}
