<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentProfile extends Model
{
    protected $fillable = [
        'parent_id', 'dni', 'telefono', 'direccion', 'ocupacion', 'grado_instruccion',
    ];

    public function gradoInstruccionLabel(): string
    {
        return match($this->grado_instruccion) {
            'sin_instruccion' => 'Sin instrucción',
            'primaria'        => 'Primaria',
            'secundaria'      => 'Secundaria',
            'tecnico'         => 'Técnico',
            'universitario'   => 'Universitario',
            'posgrado'        => 'Posgrado',
            default           => '—',
        };
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }
}
