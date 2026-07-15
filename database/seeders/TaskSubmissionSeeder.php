<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\Enrollment;
use App\Models\TaskSubmission;
use Illuminate\Database\Seeder;

class TaskSubmissionSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = Task::all();

        foreach ($tasks as $task) {
            $studentIds = Enrollment::where('section_id', $task->course->section_id)
                ->where('year', 2026)
                ->pluck('student_id');

            foreach ($studentIds as $studentId) {
                TaskSubmission::create([
                    'task_id'       => $task->id,
                    'student_id'    => $studentId,
                    'file_path'     => 'tareas/tarea' . $task->id . '_alumno' . $studentId . '.pdf',
                    'original_name' => 'tarea' . $task->id . '_entrega.pdf',
                    'attempt'       => 1,
                    'grade'         => rand(11, 20),
                    'teacher_note'  => 'Buen trabajo.',
                    'submitted_at'  => now(),
                ]);
            }
        }
    }
}
