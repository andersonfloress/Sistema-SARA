<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\TaskSubmission;
use Illuminate\Database\Seeder;

class TaskSubmissionSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = Task::all();

        foreach ($tasks as $task) {
            // Alumnos matriculados en la sección del curso de esta tarea
            $studentIds = Enrollment::where('section_id', $task->course->section_id)
                ->where('year', 2026)
                ->pluck('student_id');

            foreach ($studentIds as $studentId) {
                TaskSubmission::create([
                    'task_id'      => $task->id,
                    'student_id'   => $studentId,
                    'file_url'     => 'https://storage.../tarea' . $task->id . '_alumno' . $studentId . '.pdf',
                    'submitted_at' => now(),
                    'grade'        => rand(11, 20),
                    'feedback'     => 'Buen trabajo.',
                ]);
            }
        }
    }
}
