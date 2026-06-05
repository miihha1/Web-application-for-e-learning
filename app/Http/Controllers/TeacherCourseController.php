<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Test;
use App\Models\Question;
use App\Models\AnswerOption;
use App\Models\LessonMaterial;
use App\Models\LessonProgress;
use App\Models\TestResult;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TeacherCourseController extends Controller
{
    public function manage(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        $course->load([
            'lessons' => fn($q) => $q->orderBy('order'),
            'enrollments.user:id,name,email',
        ]);
        $test = Test::where('course_id', $course->id)->first();
        $testQuestions = $test
            ? $test->questions()->with(['options' => fn($q) => $q->orderBy('id')])->get()
            : collect();
        $analytics = $this->buildAnalytics($course, $test, $testQuestions);

        return Inertia::render('Teacher/CourseManage', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'enroll_code' => $course->enroll_code,
                'is_public' => $course->is_public,
                'cover_image_url' => $course->cover_image_path ? asset($course->cover_image_path) : null,
            ],
            'lessons' => $course->lessons->map(fn($l) => [
                'id' => $l->id,
                'title' => $l->title,
                'order' => $l->order,
            ])->values(),
            'test' => $test ? [
                'id' => $test->id,
                'title' => $test->title,
                'description' => $test->description,
                'pass_percent' => $test->pass_percent,
                'time_limit_minutes' => $test->time_limit_minutes,
                'max_attempts' => $test->max_attempts,
                'cooldown_minutes' => $test->cooldown_minutes,
                'randomize_questions' => $test->randomize_questions,
                'randomize_options' => $test->randomize_options,
                'questions' => $testQuestions->map(fn($q) => [
                    'id' => $q->id,
                    'text' => $q->text,
                    'order' => $q->order,
                    'options' => $q->options->map(fn($o) => [
                        'id' => $o->id,
                        'text' => $o->text,
                        'is_correct' => $o->is_correct,
                    ])->values(),
                ])->values(),
            ] : null,
            'analytics' => $analytics,
        ]);
    }

    public function updateCourse(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:2048'],
            'remove_cover_image' => ['nullable', 'boolean'],
        ]);

        if (($data['remove_cover_image'] ?? false) && $course->cover_image_path) {
            File::delete(public_path($course->cover_image_path));
            $course->cover_image_path = null;
        }

        if ($request->hasFile('cover_image')) {
            if ($course->cover_image_path) {
                File::delete(public_path($course->cover_image_path));
            }

            $course->cover_image_path = $this->storePublicImage($request->file('cover_image'), 'uploads/course-covers');
        }

        $course->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'cover_image_path' => $course->cover_image_path,
        ]);

        return back();
    }

    public function createLesson(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        return Inertia::render('Teacher/LessonForm', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'lesson' => null,
            'suggestedOrder' => (int) $course->lessons()->max('order') + 1,
        ]);
    }

    public function storeLesson(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:1'],
        ]);

        $order = $data['order'] ?? ($course->lessons()->max('order') + 1);

        Lesson::create([
            'course_id' => $course->id,
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'order' => $order,
        ]);

        return redirect()->route('teacher.courses.manage', $course);
    }

    public function editLesson(Request $request, Course $course, Lesson $lesson)
    {
        $this->authorizeCourse($request, $course);
        abort_unless($lesson->course_id === $course->id, 404);

        return Inertia::render('Teacher/LessonForm', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'content' => $lesson->content,
                'order' => $lesson->order,
                'materials' => $lesson->materials()
                    ->orderBy('created_at')
                    ->get()
                    ->map(fn($material) => $this->materialPayload($material))
                    ->values(),
            ],
            'suggestedOrder' => $lesson->order,
        ]);
    }

    public function updateLesson(Request $request, Course $course, Lesson $lesson)
    {
        $this->authorizeCourse($request, $course);
        abort_unless($lesson->course_id === $course->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'order' => ['required', 'integer', 'min:1'],
        ]);

        $lesson->update($data);

        return redirect()->route('teacher.courses.manage', $course);
    }

    public function deleteLesson(Request $request, Course $course, Lesson $lesson)
    {
        $this->authorizeCourse($request, $course);
        abort_unless($lesson->course_id === $course->id, 404);

        $lesson->delete();

        return back();
    }

    public function storeMaterial(Request $request, Course $course, Lesson $lesson)
    {
        $this->authorizeCourse($request, $course);
        abort_unless($lesson->course_id === $course->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:file,link,video'],
            'file' => ['required_if:type,file', 'nullable', 'file', 'mimes:pdf,zip,doc,docx', 'max:2048'],
            'url' => ['required_unless:type,file', 'nullable', 'url', 'max:2048'],
        ]);

        $payload = [
            'lesson_id' => $lesson->id,
            'title' => $data['title'],
            'type' => $data['type'],
        ];

        if ($data['type'] === 'file' && $request->hasFile('file')) {
            $file = $request->file('file');

            if (!$file->isValid()) {
                return back()->withErrors([
                    'file' => 'Súbor sa nepodarilo nahrať. Skontrolujte veľkosť súboru a skúste to znova.',
                ])->withInput();
            }

            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
            $size = $file->getSize();
            $directory = 'uploads/lesson-materials';
            File::ensureDirectoryExists(public_path($directory));

            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path($directory), $filename);

            $payload += [
                'path' => trim($directory . '/' . $filename, '/'),
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'size' => $size,
            ];
        } else {
            $payload['url'] = $data['url'];
        }

        LessonMaterial::create($payload);

        return back();
    }

    public function deleteMaterial(Request $request, Course $course, Lesson $lesson, LessonMaterial $material)
    {
        $this->authorizeCourse($request, $course);
        abort_unless($lesson->course_id === $course->id, 404);
        abort_unless($material->lesson_id === $lesson->id, 404);

        if ($material->path) {
            File::delete(public_path($material->path));
        }

        $material->delete();

        return back();
    }

    public function updateTest(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'pass_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
            'cooldown_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'randomize_questions' => ['nullable', 'boolean'],
            'randomize_options' => ['nullable', 'boolean'],
        ]);

        $data['time_limit_minutes'] = $data['time_limit_minutes'] ?? null;
        $data['max_attempts'] = $data['max_attempts'] ?? null;
        $data['cooldown_minutes'] = $data['cooldown_minutes'] ?? 0;
        $data['randomize_questions'] = (bool) ($data['randomize_questions'] ?? false);
        $data['randomize_options'] = (bool) ($data['randomize_options'] ?? false);

        $test = Test::firstOrCreate(
            ['course_id' => $course->id],
            [
                'title' => $data['title'],
                'questions' => [],
            ]
        );

        $test->update($data);

        return back();
    }

    public function updateAccess(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        $data = $request->validate([
            'is_public' => ['required', 'boolean'],
        ]);

        if ($data['is_public']) {
            $course->is_public = true;
            $course->enroll_code = null;
        } else {
            $course->is_public = false;
            if (!$course->enroll_code) {
                $course->enroll_code = Str::upper(Str::random(8));
            }
        }

        $course->save();

        return back();
    }

    public function regenerateCode(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        if ($course->is_public) {
            abort(400);
        }

        $course->enroll_code = Str::upper(Str::random(8));
        $course->save();

        return back();
    }

    public function createQuestion(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        $test = Test::firstOrCreate(['course_id' => $course->id], [
            'title' => 'Záverečný test',
            'questions' => [],
        ]);

        return Inertia::render('Teacher/QuestionForm', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'test' => ['id' => $test->id, 'title' => $test->title],
            'question' => null,
        ]);
    }

    public function storeQuestion(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);
        $test = Test::firstOrCreate(['course_id' => $course->id], [
            'title' => 'Záverečný test',
            'questions' => [],
        ]);

        $data = $request->validate([
            'text' => ['required', 'string'],
            'options' => ['required', 'array', 'min:2'],
            'options.*' => ['required', 'string'],
            'correct_indices' => ['required', 'array', 'min:1'],
            'correct_indices.*' => ['integer', 'min:0'],
        ]);

        $options = collect($data['options'])
            ->map(fn($text) => trim($text))
            ->values();

        $correctIndices = collect($data['correct_indices'])
            ->map(fn($index) => (int) $index)
            ->filter(fn($index) => $index >= 0 && $index < $options->count())
            ->unique()
            ->values();

        if ($correctIndices->isEmpty()) {
            return back()->withErrors([
                'correct_indices' => 'Vyberte aspoň jednu správnu odpoveď.',
            ])->withInput();
        }

        $order = $test->questions()->max('order') + 1;
        $question = Question::create([
            'test_id' => $test->id,
            'text' => $data['text'],
            'order' => $order,
        ]);

        foreach ($options as $idx => $text) {
            AnswerOption::create([
                'question_id' => $question->id,
                'text' => $text,
                'is_correct' => $correctIndices->contains($idx),
            ]);
        }

        return redirect()->route('teacher.courses.manage', $course);
    }

    public function editQuestion(Request $request, Course $course, Question $question)
    {
        $this->authorizeCourse($request, $course);
        abort_unless($question->test->course_id === $course->id, 404);

        $options = $question->options()->orderBy('id')->get();
        $correctIndices = $options
            ->filter(fn($o) => $o->is_correct)
            ->keys()
            ->values();

        return Inertia::render('Teacher/QuestionForm', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'test' => ['id' => $question->test->id, 'title' => $question->test->title],
            'question' => [
                'id' => $question->id,
                'text' => $question->text,
                'options' => $options->pluck('text')->values(),
                'correct_indices' => $correctIndices->isNotEmpty() ? $correctIndices : collect([0]),
            ],
        ]);
    }

    public function updateQuestion(Request $request, Course $course, Question $question)
    {
        $this->authorizeCourse($request, $course);
        abort_unless($question->test->course_id === $course->id, 404);

        $data = $request->validate([
            'text' => ['required', 'string'],
            'options' => ['required', 'array', 'min:2'],
            'options.*' => ['required', 'string'],
            'correct_indices' => ['required', 'array', 'min:1'],
            'correct_indices.*' => ['integer', 'min:0'],
        ]);

        $options = collect($data['options'])
            ->map(fn($text) => trim($text))
            ->values();

        $correctIndices = collect($data['correct_indices'])
            ->map(fn($index) => (int) $index)
            ->filter(fn($index) => $index >= 0 && $index < $options->count())
            ->unique()
            ->values();

        if ($correctIndices->isEmpty()) {
            return back()->withErrors([
                'correct_indices' => 'Vyberte aspoň jednu správnu odpoveď.',
            ])->withInput();
        }

        $question->update(['text' => $data['text']]);
        $question->options()->delete();

        foreach ($options as $idx => $text) {
            AnswerOption::create([
                'question_id' => $question->id,
                'text' => $text,
                'is_correct' => $correctIndices->contains($idx),
            ]);
        }

        return redirect()->route('teacher.courses.manage', $course);
    }

    public function deleteQuestion(Request $request, Course $course, Question $question)
    {
        $this->authorizeCourse($request, $course);
        abort_unless($question->test->course_id === $course->id, 404);

        $question->delete();

        return back();
    }

    private function authorizeCourse(Request $request, Course $course): void
    {
        $user = $request->user();

        if (!$user || !($user->isAdmin() || ($user->isTeacher() && $course->teacher_id === $user->id))) {
            abort(403);
        }
    }

    private function storePublicImage($file, string $directory): string
    {
        File::ensureDirectoryExists(public_path($directory));

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path($directory), $filename);

        return trim($directory . '/' . $filename, '/');
    }

    private function materialPayload(LessonMaterial $material): array
    {
        return [
            'id' => $material->id,
            'title' => $material->title,
            'type' => $material->type,
            'url' => $material->url,
            'file_url' => $material->path ? asset($material->path) : null,
            'original_name' => $material->original_name,
            'size' => $material->size,
        ];
    }

    private function buildAnalytics(Course $course, ?Test $test, $questions): array
    {
        $lessonIds = $course->lessons->pluck('id');
        $enrollments = $course->enrollments;
        $studentIds = $enrollments->pluck('user_id');
        $latestResults = collect();
        $allResults = collect();

        if ($test && $studentIds->isNotEmpty()) {
            $allResults = TestResult::query()
                ->where('test_id', $test->id)
                ->whereIn('user_id', $studentIds)
                ->with('user:id,name,email')
                ->orderByDesc('created_at')
                ->get();

            $latestResults = $allResults
                ->groupBy('user_id')
                ->map(fn($results) => $results->sortByDesc('created_at')->first());
        }

        $progressRows = LessonProgress::query()
            ->whereIn('lesson_id', $lessonIds)
            ->whereIn('user_id', $studentIds)
            ->whereNotNull('completed_at')
            ->get()
            ->groupBy('user_id');

        $students = $enrollments->map(function ($enrollment) use ($course, $progressRows, $latestResults) {
            $completedLessonIds = $progressRows->get($enrollment->user_id, collect())->pluck('lesson_id');
            $completedTitles = $course->lessons
                ->whereIn('id', $completedLessonIds)
                ->pluck('title')
                ->values();
            $result = $latestResults->get($enrollment->user_id);

            return [
                'id' => $enrollment->user->id,
                'name' => $enrollment->user->name,
                'email' => $enrollment->user->email,
                'completed_lessons' => $completedTitles->count(),
                'completed_lesson_titles' => $completedTitles,
                'lessons_count' => $course->lessons->count(),
                'test_attempted' => (bool) $result,
                'test_percent' => $result?->percent,
                'test_passed' => $result?->passed,
                'test_attempt' => $result?->attempt,
            ];
        })->values();

        $wrongQuestions = $questions->map(function ($question) use ($allResults) {
            $wrong = 0;
            $answered = 0;
            $correctIds = $question->options
                ->where('is_correct', true)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            foreach ($allResults as $result) {
                $chosen = $result->answers[$question->id] ?? null;
                if (!$chosen) {
                    continue;
                }

                $answered++;
                $chosenIds = collect(is_array($chosen) ? $chosen : [$chosen])
                    ->map(fn($id) => (int) $id)
                    ->sort()
                    ->values()
                    ->all();

                if ($chosenIds !== $correctIds) {
                    $wrong++;
                }
            }

            return [
                'id' => $question->id,
                'text' => $question->text,
                'wrong_count' => $wrong,
                'answered_count' => $answered,
            ];
        })
            ->filter(fn($item) => $item['answered_count'] > 0)
            ->sortByDesc('wrong_count')
            ->take(5)
            ->values();

        return [
            'enrolled_count' => $enrollments->count(),
            'test_attempts_count' => $allResults->count(),
            'students_attempted_count' => $latestResults->count(),
            'average_percent' => $latestResults->count() > 0 ? round($latestResults->avg('percent'), 2) : null,
            'students' => $students,
            'wrong_questions' => $wrongQuestions,
        ];
    }
}
