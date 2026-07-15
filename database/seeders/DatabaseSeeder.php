<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AcademicYearSeeder::class,
            SectionSeeder::class,
            TeacherProfileSeeder::class,
            StudentProfileSeeder::class,
            ParentProfileSeeder::class,
            CourseSeeder::class,
            EnrollmentSeeder::class,
            ScheduleSlotSeeder::class,
            GradeSeeder::class,
            AttendanceSeeder::class,
            ParentStudentSeeder::class,
            MaterialSeeder::class,
            AnnouncementSeeder::class,
            AcademicEventSeeder::class,
            TaskSeeder::class,
            TaskSubmissionSeeder::class,
        ]);
    }
}
