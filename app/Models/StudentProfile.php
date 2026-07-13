<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    protected $fillable = [
        'student_id',
        'codigo_estudiante', 'dni',
        'fecha_nacimiento', 'sexo', 'nacionalidad', 'tipo_sangre',
        'foto_perfil',
        'grado', 'turno', 'anio_ingreso',
        'direccion', 'telefono',
        'nombre_apoderado', 'dni_apoderado', 'telefono_emergencia',
        'condicion_especial',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
