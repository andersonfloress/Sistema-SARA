# IE Santa Rosa - Sistema SARA

Sistema completo de gestión escolar desarrollado con **Laravel 12** + **MySQL** + **Blade** + **Tailwind CSS** + **Flowbite** + **Alpine.js**.

## Stack Tecnológico

- **Backend**: Laravel 12 (PHP 8.3+)
- **Base de datos**: MySQL
- **ORM**: Eloquent
- **Autenticación**: Laravel Breeze (sessions)
- **Frontend**: Blade + Tailwind CSS + Flowbite + Alpine.js + SweetAlert2 + Lucide Icons

## Módulos

- 👥 Gestión de Usuarios (admin/docente/alumno/padre)
- 🎓 Alumnos con perfiles completos
- 👨‍🏫 Docentes con perfiles
- 🏫 Secciones y Cursos
- 📝 Calificaciones por bimestre (I, II, III)
- ✅ Asistencia (presente/ausente/tardanza/justificado)
- 📢 Comunicados con segmentación por rol
- 📅 Horarios semanales
- 📊 Reportes estadísticos
- 👨‍👩‍👧 Portal de Padres de Familia

## Instalación

### Requisitos
- PHP 8.3+
- Composer
- MySQL 8.0+
- Node.js 20+ y npm


## Estructura del Proyecto

```
app/
├── Http/
│   ├── Controllers/     # Controladores de cada módulo
│   ├── Middleware/      # RoleMiddleware para control de acceso
│   └── Requests/        # Form Requests con validaciones
├── Models/              # Modelos Eloquent
database/
├── migrations/          # Migraciones de todas las tablas
└── seeders/             # Datos de prueba
resources/views/
├── layouts/             # Layout principal con sidebar
├── auth/                # Vista de login
├── dashboard/           # Dashboard con estadísticas
├── alumnos/             # Módulo alumnos
├── docentes/            # Módulo docentes
├── secciones/           # Módulo secciones y cursos
├── calificaciones/      # Módulo calificaciones
├── asistencia/          # Módulo asistencia
├── comunicados/         # Módulo comunicados
├── horarios/            # Módulo horarios
├── reportes/            # Módulo reportes
├── padres/              # Portal de padres
└── perfil/              # Perfil de usuario
routes/
└── web.php              # Todas las rutas web
```
