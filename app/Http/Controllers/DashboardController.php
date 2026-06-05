<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Test;
use App\Models\TestResult;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'role' => $user->role,
            'student' => $user->isStudent() ? $this->studentPayload($user->id) : null,
            'manager' => ($user->isAdmin() || $user->isTeacher()) ? $this->managerPayload($user) : null,
        ]);
    }

    private function studentPayload(int $userId): array
    {
        $courseIds = Enrollment::where('user_id', $userId)->pluck('course_id');
        $lessonIds = Lesson::whereIn('course_id', $courseIds)->pluck('id');
        $lessonsCount = $lessonIds->count();
        $completedLessons = LessonProgress::where('user_id', $userId)
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('completed_at')
            ->count();

        $continueLessons = Lesson::query()
            ->with('course:id,title')
            ->whereIn('course_id', $courseIds)
            ->whereDoesntHave('progresses', function ($query) use ($userId) {
                $query->where('user_id', $userId)->whereNotNull('completed_at');
            })
            ->orderBy('course_id')
            ->orderBy('order')
            ->limit(2)
            ->get()
            ->map(fn (Lesson $lesson) => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'course_title' => $lesson->course?->title,
                'order' => $lesson->order,
                'href' => route('lessons.show', [$lesson->course_id, $lesson->id], false),
            ])
            ->values();

        $nextTest = Test::query()
            ->with('course:id,title')
            ->whereIn('course_id', $courseIds)
            ->whereHas('questions')
            ->orderBy('course_id')
            ->first();

        return [
            'stats' => [
                'courses' => $courseIds->count(),
                'progress' => $lessonsCount > 0 ? round(($completedLessons / $lessonsCount) * 100) : 0,
                'attempts' => TestResult::where('user_id', $userId)->count(),
            ],
            'continue_lessons' => $continueLessons,
            'next_test' => $nextTest ? [
                'title' => $nextTest->title,
                'course_title' => $nextTest->course?->title,
                'href' => route('courses.test.show', $nextTest->course_id, false),
            ] : null,
        ];
    }

    private function managerPayload($user): array
    {
        $coursesQuery = Course::query();

        if ($user->isTeacher()) {
            $coursesQuery->where('teacher_id', $user->id);
        }

        $courseIds = (clone $coursesQuery)->pluck('id');
        $testIds = Test::whereIn('course_id', $courseIds)->pluck('id');

        $recentCourses = (clone $coursesQuery)
            ->withCount(['lessons', 'enrollments'])
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'lessons_count' => $course->lessons_count,
                'students_count' => $course->enrollments_count,
                'href' => route('teacher.courses.manage', $course, false),
            ])
            ->values();

        return [
            'stats' => [
                'courses' => $courseIds->count(),
                'lessons' => Lesson::whereIn('course_id', $courseIds)->count(),
                'students' => Enrollment::whereIn('course_id', $courseIds)->distinct('user_id')->count('user_id'),
                'attempts' => TestResult::whereIn('test_id', $testIds)->count(),
            ],
            'recent_courses' => $recentCourses,
            'primary_action' => [
                'label' => $user->isAdmin() ? 'Open courses' : 'Create course',
                'href' => $user->isAdmin() ? route('courses.index', absolute: false) : route('courses.create', absolute: false),
            ],
        ];
    }
}
