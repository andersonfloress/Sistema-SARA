<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['title', 'content', 'author_id', 'target_role'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function targetRoleLabel(): string
    {
        return match($this->target_role) {
            'all'     => 'Todos',
            'student' => 'Alumnos',
            'teacher' => 'Docentes',
            'admin'   => 'Administradores',
            'parent'  => 'Padres',
            default   => ucfirst($this->target_role),
        };
    }

    public function targetRoleClass(): string
    {
        return match($this->target_role) {
            'all'     => 'bg-gray-100 text-gray-800',
            'student' => 'bg-blue-100 text-blue-800',
            'teacher' => 'bg-purple-100 text-purple-800',
            'admin'   => 'bg-red-100 text-red-800',
            'parent'  => 'bg-green-100 text-green-800',
            default   => 'bg-gray-100 text-gray-800',
        };
    }
}
