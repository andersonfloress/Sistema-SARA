<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherProfile extends Model
{
    protected $fillable = [
        'teacher_id', 'dni', 'codigo_docente', 'especialidad', 'telefono', 'direccion',
        'fecha_nacimiento', 'sexo', 'foto_perfil', 'correo_alternativo',
        'contacto_emergencia_nombre', 'contacto_emergencia_telefono',
        'fecha_ingreso', 'condicion_laboral', 'nivel_academico',
        'numero_colegiatura', 'turno', 'max_horas_semanales', 'cv_path',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso'    => 'date',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
