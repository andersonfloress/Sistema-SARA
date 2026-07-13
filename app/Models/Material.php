<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = ['title', 'type', 'url', 'description', 'course_id', 'teacher_id'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function typeLabel(): string
    {
        return match($this->type) {
            'document' => 'Documento',
            'video'    => 'Video',
            'link'     => 'Enlace',
            default    => ucfirst($this->type),
        };
    }

    public function typeIcon(): string
    {
        return match($this->type) {
            'document' => 'file-text',
            'video'    => 'play-circle',
            'link'     => 'link',
            default    => 'file',
        };
    }
}
