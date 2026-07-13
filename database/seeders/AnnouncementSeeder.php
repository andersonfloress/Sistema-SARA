<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $admin   = User::where('role', 'admin')->first();
        $teacher = User::where('role', 'teacher')->first();

        $announcements = [
            // Para todos
            ['title'   => 'Bienvenidos al año escolar 2026',
             'content' => 'La dirección de la IE Santa Rosa les da la más cordial bienvenida al año escolar 2026. Les deseamos un año lleno de aprendizajes, logros y convivencia armoniosa en nuestra institución. ¡Juntos construimos educación de calidad para Puno!',
             'author_id' => $admin->id, 'target_role' => 'all'],

            ['title'   => 'Protocolo de ingreso y salida 2026',
             'content' => 'Se recuerda a toda la comunidad educativa que el ingreso al plantel es estrictamente por la puerta principal. Turno mañana: 07:20 a.m. — Turno tarde: 12:50 p.m. No se permitirá el ingreso fuera del horario establecido. Portarse el carnet escolar de forma visible.',
             'author_id' => $admin->id, 'target_role' => 'all'],

            ['title'   => 'Uso del uniforme escolar — recordatorio',
             'content' => 'Se exige el uso del uniforme escolar completo todos los días. Los días lunes el uso de la chompa azul institucional es obligatorio. Los días de Educación Física usar el buzo deportivo. Cualquier consulta dirigirse a auxiliares de turno.',
             'author_id' => $admin->id, 'target_role' => 'all'],

            // Para padres
            ['title'   => 'Reunión de padres de familia — 1er Período',
             'content' => 'Se convoca a todos los padres y apoderados a la reunión informativa del Primer Período 2026. Fecha: viernes 20 de marzo a las 5:00 p.m. en el auditorio principal. Su presencia es indispensable. Traer DNI del apoderado.',
             'author_id' => $admin->id, 'target_role' => 'parent'],

            ['title'   => 'Entrega de kit de útiles escolares 2026',
             'content' => 'Estimados padres de familia, les comunicamos que el miércoles 25 de marzo se realizará la entrega de útiles escolares del MINEDU a todos los estudiantes matriculados. Deben presentar el DNI del apoderado para el recojo en la secretaría del plantel.',
             'author_id' => $admin->id, 'target_role' => 'parent'],

            ['title'   => 'Cuota ordinaria APAFA 2026',
             'content' => 'Se recuerda a los padres de familia que el plazo para el pago de la cuota ordinaria de APAFA vence el 31 de marzo. Los pagos se realizan en tesorería del plantel de lunes a viernes de 8:00 a.m. a 12:00 m. El monto aprobado en asamblea es de S/ 30.00.',
             'author_id' => $admin->id, 'target_role' => 'parent'],

            ['title'   => 'Reunión de padres — Resultados del 2do Período',
             'content' => 'Se convoca a reunión de padres para revisión de calificaciones del Segundo Período. Viernes 3 de julio a las 5:00 p.m. La asistencia es obligatoria. Los padres que no asistan deberán regularizar su situación en la semana siguiente.',
             'author_id' => $admin->id, 'target_role' => 'parent'],

            // Para docentes
            ['title'   => 'Plazo de registro de notas — Período I',
             'content' => 'Se recuerda a todos los docentes que el plazo máximo para el registro de calificaciones del Primer Período es el 25 de abril. Ingresar las notas al sistema antes de esa fecha. Pasado el plazo se reportará al coordinador académico.',
             'author_id' => $admin->id, 'target_role' => 'teacher'],

            ['title'   => 'Jornada de planificación curricular — 2do Período',
             'content' => 'Se convoca a todos los docentes a la jornada de planificación curricular del Segundo Período el sábado 2 de mayo de 8:00 a.m. a 1:00 p.m. en la sala de docentes. La asistencia es obligatoria. Traer unidades didácticas del bimestre anterior.',
             'author_id' => $admin->id, 'target_role' => 'teacher'],

            ['title'   => 'Subida de sesiones de aprendizaje — Período II',
             'content' => 'Se solicita a todos los docentes que suban sus sesiones de aprendizaje del Segundo Período al sistema institucional antes del 8 de mayo. El formato actualizado está disponible en la plataforma. Consultar con la coordinadora académica cualquier duda.',
             'author_id' => $admin->id, 'target_role' => 'teacher'],

            // Para alumnos
            ['title'   => 'Olimpiada Regional de Matemáticas 2026 — ¡Inscríbete!',
             'content' => 'Invitamos a todos los estudiantes de 3° a 5° interesados en participar en la Olimpiada Regional de Matemáticas 2026 a inscribirse en Secretaría hasta el viernes 20 de marzo. La preparación es los miércoles a las 12:30 p.m. con la Prof. Yolanda Mamani.',
             'author_id' => $teacher->id, 'target_role' => 'student'],

            ['title'   => 'Calendario de evaluaciones — Período I',
             'content' => 'Ya está disponible el calendario de evaluaciones del Primer Período en el tablón de anuncios de tu sección. Prepárate con tiempo. Recuerda que el 30% de tu calificación corresponde a trabajos, prácticas y participación en clase.',
             'author_id' => $admin->id, 'target_role' => 'student'],

            ['title'   => 'Concurso de oratoria escolar 2026',
             'content' => 'El área de Comunicación invita a todos los estudiantes al Concurso de Oratoria Escolar 2026. Inscripciones abiertas del 15 al 22 de marzo en la biblioteca. Los interesados contactar al Prof. Félix Condori. Habrá premiación para los tres primeros puestos.',
             'author_id' => $teacher->id, 'target_role' => 'student'],

            ['title'   => 'Campaña de limpieza — Aniversario de la IE',
             'content' => 'Con motivo del aniversario institucional de la IE Santa Rosa, se realizará una jornada de limpieza y embellecimiento de los ambientes del plantel el próximo sábado 18 de abril de 8:00 a.m. a 12:00 m. La participación de todos los estudiantes es voluntaria y se reconocerá con nota de conducta.',
             'author_id' => $admin->id, 'target_role' => 'student'],
        ];

        foreach ($announcements as $a) {
            Announcement::create($a);
        }
    }
}
