<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ParentPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskGradebookController;
use App\Http\Controllers\AcademicEventController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\MatriculaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiStatsController;

// ─── API pública de estadísticas (sin autenticación) ───────────────────────
Route::prefix('api')->group(function () {
    Route::get('/stats',        [ApiStatsController::class, 'stats']);
    Route::get('/estudiantes',  [ApiStatsController::class, 'estudiantes']);
    Route::get('/docentes',     [ApiStatsController::class, 'docentes']);
});

// Redirect root
Route::get('/', fn() => redirect()->route('dashboard'));

// Auth routes (login/logout via Breeze)
require __DIR__ . '/auth.php';

// Authenticated routes
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Usuarios (admin only) ─────────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::resource('usuarios', UserController::class)
            ->parameters(['usuarios' => 'usuario'])
            ->except(['show']);
    });

    // ── Alumnos ───────────────────────────────────────────────────────────
    Route::middleware('role:admin,teacher,student')->group(function () {
        Route::get('/alumnos', [StudentController::class, 'index'])->name('alumnos.index');
        Route::get('/alumnos/{alumno}', [StudentController::class, 'show'])->name('alumnos.show');
    });
    Route::middleware('role:admin')->group(function () {
        Route::get('/alumnos/{alumno}/editar-perfil', [StudentController::class, 'editProfile'])->name('alumnos.editProfile');
        Route::put('/alumnos/{alumno}/perfil', [StudentController::class, 'updateProfile'])->name('alumnos.updateProfile');
    });

    // ── Docentes (solo admin) ─────────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::get('/docentes', [TeacherController::class, 'index'])->name('docentes.index');
        Route::get('/docentes/{docente}', [TeacherController::class, 'show'])->name('docentes.show');
        Route::get('/docentes/{docente}/editar-perfil', [TeacherController::class, 'editProfile'])->name('docentes.editProfile');
        Route::put('/docentes/{docente}/perfil', [TeacherController::class, 'updateProfile'])->name('docentes.updateProfile');
    });

    // ── Secciones ─────────────────────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::resource('secciones', SectionController::class)->except(['show', 'create', 'store']);
        Route::post('/secciones-generar', [SectionController::class, 'generate'])->name('secciones.generate');
        Route::get('/secciones/{seccione}/cursos', [SectionController::class, 'courses'])->name('secciones.courses');
        Route::post('/secciones/{seccione}/cursos', [SectionController::class, 'storeCourse'])->name('secciones.storeCourse');
        Route::delete('/secciones/{seccione}/cursos/{curso}', [SectionController::class, 'destroyCourse'])->name('secciones.destroyCourse');
        Route::put('/secciones/{seccione}/cursos/{curso}', [SectionController::class, 'updateCourse'])->name('secciones.updateCourse');
        Route::post('/secciones/{seccione}/matricular', [SectionController::class, 'enroll'])->name('secciones.enroll');

        Route::get('/matricula', [MatriculaController::class, 'index'])->name('matricula.index');
        Route::get('/matricula/admitir', [MatriculaController::class, 'createAdmision'])->name('matricula.admitir');
        Route::post('/matricula/admitir', [MatriculaController::class, 'storeAdmision'])->name('matricula.storeAdmision');

        Route::get('/promocion', [PromotionController::class, 'index'])->name('promocion.index');
        Route::post('/promocion', [PromotionController::class, 'store'])->name('promocion.store');
        Route::post('/promocion/resultado', [PromotionController::class, 'setResult'])->name('promocion.setResult');
        Route::post('/promocion/resultado-masivo', [PromotionController::class, 'setBulkResult'])->name('promocion.setBulkResult');
        Route::post('/promocion/limpiar-resultados', [PromotionController::class, 'clearSectionResult'])->name('promocion.clearResult');
        Route::post('/promocion/auto-calcular-seccion', [PromotionController::class, 'autoCalculateSection'])->name('promocion.autoCalculateSection');
        Route::post('/promocion/auto-calcular-anio', [PromotionController::class, 'autoCalculateYear'])->name('promocion.autoCalculateYear');

        Route::get('/anios', [AcademicYearController::class, 'index'])->name('anios.index');
        Route::post('/anios', [AcademicYearController::class, 'store'])->name('anios.store');
        Route::post('/anios/{anio}/habilitar', [AcademicYearController::class, 'openEnrollment'])->name('anios.openEnrollment');
        Route::post('/anios/{anio}/cerrar', [AcademicYearController::class, 'closeEnrollment'])->name('anios.closeEnrollment');
        Route::post('/anios/{anio}/finalizar', [AcademicYearController::class, 'finish'])->name('anios.finish');
    });

    // ── Calificaciones ────────────────────────────────────────────────────
    // Index visible to admin, teacher, student and parent (each sees only their own data)
    Route::middleware('role:admin,teacher,student,parent')->group(function () {
        Route::get('/calificaciones', [GradeController::class, 'index'])->name('calificaciones.index');
    });
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/calificaciones/registrar', [GradeController::class, 'create'])->name('calificaciones.create');
        Route::post('/calificaciones', [GradeController::class, 'store'])->name('calificaciones.store');
    });

    // ── Asistencia ────────────────────────────────────────────────────────
    // Index visible to admin, teacher, student and parent (each sees only their own data)
    Route::middleware('role:admin,teacher,student,parent')->group(function () {
        Route::get('/asistencia', [AttendanceController::class, 'index'])->name('asistencia.index');
    });
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/asistencia/registrar', [AttendanceController::class, 'create'])->name('asistencia.create');
        Route::post('/asistencia', [AttendanceController::class, 'store'])->name('asistencia.store');
    });

    // ── Materiales educativos ────────────────────────────────────────────
    Route::middleware('role:admin,teacher,student,parent')->group(function () {
        Route::get('/materiales', [MaterialController::class, 'index'])->name('materiales.index');
    });
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/materiales/crear', [MaterialController::class, 'create'])->name('materiales.create');
        Route::post('/materiales', [MaterialController::class, 'store'])->name('materiales.store');
        Route::delete('/materiales/{material}', [MaterialController::class, 'destroy'])->name('materiales.destroy');
    });

    // ── Tareas ────────────────────────────────────────────────────────────
    Route::middleware('role:admin,teacher,student,parent')->group(function () {
        Route::get('/tareas', [TaskController::class, 'index'])->name('tareas.index');
        Route::get('/tareas/libreta', [TaskGradebookController::class, 'index'])->name('tareas.libreta');
        Route::get('/tareas/{tarea}', [TaskController::class, 'show'])->name('tareas.show');
    });
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/tareas/nueva/crear', [TaskController::class, 'create'])->name('tareas.create');
        Route::post('/tareas', [TaskController::class, 'store'])->name('tareas.store');
        Route::delete('/tareas/{tarea}', [TaskController::class, 'destroy'])->name('tareas.destroy');
        Route::patch('/tareas/{tarea}/submissions/{submission}/grade', [TaskController::class, 'grade'])->name('tareas.grade');
    });
    Route::middleware('role:student')->group(function () {
        Route::post('/tareas/{tarea}/submit', [TaskController::class, 'submit'])->name('tareas.submit');
    });

    // ── Calendario académico ─────────────────────────────────────────────
    Route::get('/calendario', [AcademicEventController::class, 'index'])->name('calendario.index');
    Route::middleware('role:admin')->group(function () {
        Route::get('/calendario/crear', [AcademicEventController::class, 'create'])->name('calendario.create');
        Route::post('/calendario', [AcademicEventController::class, 'store'])->name('calendario.store');
        Route::delete('/calendario/{evento}', [AcademicEventController::class, 'destroy'])->name('calendario.destroy');
    });

    // ── Comunicados ───────────────────────────────────────────────────────
    Route::get('/comunicados', [AnnouncementController::class, 'index'])->name('comunicados.index');
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/comunicados/crear', [AnnouncementController::class, 'create'])->name('comunicados.create');
        Route::post('/comunicados', [AnnouncementController::class, 'store'])->name('comunicados.store');
    });
    Route::middleware('role:admin')->group(function () {
        Route::delete('/comunicados/{comunicado}', [AnnouncementController::class, 'destroy'])->name('comunicados.destroy');
    });

    // ── Horarios ──────────────────────────────────────────────────────────
    Route::get('/horarios', [ScheduleController::class, 'index'])->name('horarios.index');
    Route::middleware('role:admin')->group(function () {
        Route::get('/horarios/admin', [ScheduleController::class, 'adminIndex'])->name('horarios.admin');
        Route::post('/horarios', [ScheduleController::class, 'store'])->name('horarios.store');
        Route::put('/horarios/{horario}', [ScheduleController::class, 'update'])->name('horarios.update');
        Route::delete('/horarios/{horario}', [ScheduleController::class, 'destroy'])->name('horarios.destroy');
    });

    // ── Reportes ──────────────────────────────────────────────────────────
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/reportes', [ReportController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/calificaciones/pdf', [ReportController::class, 'exportGradesPdf'])->name('reportes.calificaciones.pdf');
        Route::get('/reportes/calificaciones/excel', [ReportController::class, 'exportGradesExcel'])->name('reportes.calificaciones.excel');
        Route::get('/reportes/asistencia/pdf', [ReportController::class, 'exportAttendancePdf'])->name('reportes.asistencia.pdf');
        Route::get('/reportes/asistencia/excel', [ReportController::class, 'exportAttendanceExcel'])->name('reportes.asistencia.excel');
        Route::get('/reportes/boletin', [ReportController::class, 'boletinSelector'])->name('reportes.boletin');
        Route::get('/reportes/boletin/{alumno}/pdf', [ReportController::class, 'boletinPdf'])->name('reportes.boletin.pdf');
    });

    // ── Portal Padres ─────────────────────────────────────────────────────
    Route::middleware('role:parent,admin')->group(function () {
        Route::get('/padres/portal', [ParentPortalController::class, 'index'])->name('padres.index');
        Route::get('/padres/portal/{alumno}', [ParentPortalController::class, 'show'])->name('padres.show');
        Route::get('/padres/portal/{alumno}/pdf', [ParentPortalController::class, 'exportPdf'])->name('padres.show.pdf');
    });
    // Edición de perfil de padre (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/padres/{padre}/editar-perfil', [UserController::class, 'editParentProfile'])->name('padres.editProfile');
        Route::put('/padres/{padre}/perfil', [UserController::class, 'updateParentProfile'])->name('padres.updateProfile');
    });

    // ── Perfil ────────────────────────────────────────────────────────────
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('perfil.update');
    Route::put('/perfil/contacto', [ProfileController::class, 'updateContactInfo'])->name('perfil.updateContactInfo');
    Route::put('/perfil/password', [ProfileController::class, 'updatePassword'])->name('perfil.updatePassword');
    // Edición completa del perfil (solo docente)
    Route::get('/perfil/editar', [ProfileController::class, 'editFull'])->name('perfil.editFull');
    Route::put('/perfil/editar', [ProfileController::class, 'updateFull'])->name('perfil.updateFull');
});
