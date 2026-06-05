<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use Inertia\Inertia;

class LessonController extends Controller
{
    public function show(Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);
        $this->abortIfNoAccess($course);

        $lesson->load(['materials' => fn($q) => $q->orderBy('created_at')]);

        return Inertia::render('Lessons/Show', [
            'course' => $course->only(['id', 'title']),
            'lesson' => [
                ...$lesson->only(['id', 'title', 'content', 'order']),
                'materials' => $lesson->materials->map(fn($material) => [
                    'id' => $material->id,
                    'title' => $material->title,
                    'type' => $material->type,
                    'url' => $material->url,
                    'file_url' => $material->path ? asset($material->path) : null,
                    'original_name' => $material->original_name,
                    'size' => $material->size,
                ])->values(),
            ],
        ]);
    }

    private function abortIfNoAccess(Course $course): void
    {
        $user = request()->user();
        $isEnrolled = false;

        if ($user) {
            $isEnrolled = Enrollment::where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->exists();
        }

        $canAccess = $course->is_public
            || ($user && ($user->isAdmin() || ($user->isTeacher() && $course->teacher_id === $user->id)))
            || $isEnrolled;

        if (!$canAccess) {
            abort(403);
        }
    }
}
