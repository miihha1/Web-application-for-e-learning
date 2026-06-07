<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Inertia\Inertia;
use App\Models\Lesson;
use App\Models\Test;
use App\Models\TestResult;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    private function publicAssetUrl(?string $path): ?string
    {
        return $path ? asset($path) : null;
    }

    public function index()
    {
        $user = request()->user();
        $courses = Course::withCount('lessons')
            ->with('teacher:id,name')
            ->orderBy('title')
            ->get(['id','title','description','teacher_id','is_public', 'cover_image_path']);

        $enrolledCourses = [];
        if ($user) {
            $enrolled = Enrollment::with('course.teacher:id,name')
                ->where('user_id', $user->id)
                ->get()
                ->sortBy(fn($e) => $e->course->title);

            $enrolledCourses = $enrolled->map(fn($e) => [
                'id' => $e->course->id,
                'title' => $e->course->title,
                'description' => $e->course->description,
                'lessons_count' => $e->course->lessons()->count(),
                'is_public' => $e->course->is_public,
                'teacher_name' => $e->course->teacher?->name,
                'cover_image_url' => $this->publicAssetUrl($e->course->cover_image_path),
            ])->values();
        }
        return Inertia::render('Courses/Index', [
            'courses' => $courses->map(fn($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'description' => $c->description,
                'lessons_count' => $c->lessons_count,
                'is_public' => $c->is_public,
                'teacher_name' => $c->teacher?->name,
                'cover_image_url' => $this->publicAssetUrl($c->cover_image_path),
            ]),
            'enrolled_courses' => $enrolledCourses,
        ]);
    }

    public function myCourses(Request $request)
    {
        $user = $request->user();

        $coursesQuery = Course::query()
            ->with('teacher:id,name')
            ->withCount('lessons')
            ->orderBy('title');

        if ($user->isStudent()) {
            $coursesQuery
                ->whereHas('enrollments', fn($q) => $q->where('user_id', $user->id))
                ->withCount([
                    'lessons as completed_lessons_count' => function ($q) use ($user) {
                        $q->whereHas('progresses', function ($q2) use ($user) {
                            $q2->where('user_id', $user->id)
                               ->whereNotNull('completed_at');
                        });
                    },
                ]);
        } elseif ($user->isTeacher()) {
            $coursesQuery
                ->where('teacher_id', $user->id)
                ->withCount([
                    'lessons as completed_lessons_count' => fn($q) => $q->whereRaw('1 = 0'),
                ]);
        } else {
            $coursesQuery->withCount([
                'lessons as completed_lessons_count' => fn($q) => $q->whereRaw('1 = 0'),
            ]);
        }

        $courses = $coursesQuery->get(['id','title','description','teacher_id','is_public', 'cover_image_path']);

        return Inertia::render('Courses/My', [
            'courses' => $courses->map(fn($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'description' => $c->description,
                'lessons_count' => $c->lessons_count,
                'completed_lessons_count' => $c->completed_lessons_count,
                'progress_percent' => $c->lessons_count > 0
                    ? round(($c->completed_lessons_count / $c->lessons_count) * 100)
                    : 0,
                'is_public' => $c->is_public,
                'teacher_name' => $c->teacher?->name,
                'cover_image_url' => $this->publicAssetUrl($c->cover_image_path),
            ])->values(),
        ]);
    }

    public function show(Course $course)
    {
        $course->load(['lessons' => fn($q) => $q->orderBy('order')]);
        $test = Test::where('course_id', $course->id)
            ->withCount('questions')
            ->first();
        $testAvailable = $test && $test->questions_count > 0;

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

        $canManage = $user && ($user->isAdmin() || ($user->isTeacher() && $course->teacher_id === $user->id));
        $canSeeCode = $canManage;

        return Inertia::render('Courses/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'is_public' => $course->is_public,
                'teacher_id' => $course->teacher_id,
                'enroll_code' => $canSeeCode ? $course->enroll_code : null,
                'cover_image_url' => $this->publicAssetUrl($course->cover_image_path),
                'lessons_count' => $course->lessons->count(),
                'lessons' => $canAccess ? $course->lessons : [],
                'test_available' => $testAvailable,
            ],
            'access' => [
                'can_access' => $canAccess,
                'is_enrolled' => $isEnrolled,
                'can_enroll' => $user && $user->isStudent() && !$isEnrolled,
                'enroll_requires_code' => !$course->is_public,
                'can_manage' => $canManage,
            ],
        ]);
    }

    public function lessonShow(Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);
        $this->abortIfNoAccess($course);

        return Inertia::render('Lessons/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
            ],
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'content' => $lesson->content,
                'order' => $lesson->order,
            ],
        ]);
    }

    public function testShow(Course $course)
    {
        $this->abortIfNoAccess($course);
        $test = Test::where('course_id', $course->id)->first();

        if (!$test) {
            return Inertia::render('Tests/Empty', [
                'course' => ['id' => $course->id, 'title' => $course->title],
            ]);
        }

        return Inertia::render('Tests/Show', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'test' => [
                'id' => $test->id,
                'title' => $test->title,
                'questions' => $test->questions,
            ],
        ]);
    }

    public function testSubmit(Request $request, Course $course)
    {
        $this->abortIfNoAccess($course);
        $test = Test::where('course_id', $course->id)->firstOrFail();

        $data = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $questions = $test->questions;
        $score = 0;
        $max = count($questions);

        foreach ($questions as $i => $q) {
            $correct = $q['correct'];      // napríklad index správnej odpovede
            $userAns = $data['answers'][$i] ?? null;
            if ((string)$userAns === (string)$correct) $score++;
        }

        TestResult::updateOrCreate(
            ['test_id' => $test->id, 'user_id' => $request->user()->id],
            ['score' => $score, 'max_score' => $max, 'answers' => $data['answers']]
        );

        return back()->with('success', "Výsledok: $score / $max");
    }

    public function create()
    {
        return Inertia::render('Courses/Create');
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:2048'],
        ]);

        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $this->storePublicImage($request->file('cover_image'), 'uploads/course-covers');
        }

        $isPublic = $user->isAdmin();
        $course = Course::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'teacher_id' => $user->isTeacher() ? $user->id : null,
            'is_public' => $isPublic,
            'enroll_code' => $isPublic ? null : Str::upper(Str::random(8)),
            'cover_image_path' => $coverImagePath,
        ]);

        return redirect()->route('teacher.courses.manage', $course);
    }

    public function enroll(Request $request, Course $course)
    {
        $user = $request->user();

        if (!$user || !$user->isStudent()) {
            abort(403);
        }

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:16'],
        ]);

        if (!$course->is_public) {
            $code = strtoupper(trim($data['code'] ?? ''));
            if (!$code || $code !== $course->enroll_code) {
                return back()->withErrors(['code' => 'Invalid code.']);
            }
        }

        Enrollment::firstOrCreate([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        return back();
    }

    public function unenroll(Request $request, Course $course)
    {
        $user = $request->user();

        if (!$user || !$user->isStudent()) {
            abort(403);
        }

        Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->delete();

        return back();
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

    protected function storePublicImage($file, string $directory): string
    {
        File::ensureDirectoryExists(public_path($directory));

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path($directory), $filename);

        return trim($directory . '/' . $filename, '/');
    }
}
