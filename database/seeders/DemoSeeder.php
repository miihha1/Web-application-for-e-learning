<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\LessonProgress;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::create([
            'title' => 'Základy Laravelu',
            'description' => 'Demo kurz',
        ]);

        $lesson1 = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Inštalácia',
            'content' => '...',
            'order' => 1,
        ]);

        $lesson2 = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Routing',
            'content' => '...',
            'order' => 2,
        ]);

        // студент
        $student = User::firstOrCreate(
            ['email' => 'student@local.test'],
            [
                'name' => 'Student',
                'password' => Hash::make('password'),
                'role' => 'student',
                'active' => true,
            ]
        );

        // запись на курс (enrollment)
        Enrollment::firstOrCreate([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        // прогресс по урокам
        // Если в таблице lesson_progress пока нет progress/completed_at — убери эти поля и оставь только user_id + lesson_id.
        LessonProgress::updateOrCreate(
            ['user_id' => $student->id, 'lesson_id' => $lesson1->id],
            ['progress' => 100, 'completed_at' => now()]
        );

        LessonProgress::updateOrCreate(
            ['user_id' => $student->id, 'lesson_id' => $lesson2->id],
            ['progress' => 30, 'completed_at' => null]
        );
    }
}
