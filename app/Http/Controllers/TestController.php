<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Test;
use App\Models\TestResult;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class TestController extends Controller
{
    public function show(Request $request, Course $course)
    {
        $this->abortIfNoAccess($course, $request);
        $user = $request->user();
        $test = Test::where('course_id', $course->id)
            ->withCount('questions')
            ->first();

        if (!$test || $test->questions_count === 0) {
            return $this->emptyTestResponse($course, $user);
        }

        $questions = $test->questions()
            ->with('options')
            ->orderBy('order')
            ->get();

        if ($test->randomize_questions) {
            $questions = $questions->shuffle()->values();
        }

        if ($test->randomize_options) {
            $questions->each(fn($q) => $q->setRelation('options', $q->options->shuffle()->values()));
        }

        $attemptsCount = TestResult::where('test_id', $test->id)
            ->where('user_id', $user->id)
            ->count();
        $latest = TestResult::where('test_id', $test->id)
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        [$canTakeTest, $blockedReason] = $this->attemptPolicy($test, $attemptsCount, $latest);
        $timing = $this->prepareTiming($test, $canTakeTest);

        if ($timing['expired']) {
            session()->forget($this->timerSessionKey($test));
            $canTakeTest = false;
            $blockedReason = 'Časový limit testu vypršal. Otvorte test znova pre nový pokus.';
        }

        return Inertia::render('Tests/Show', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'test' => [
                'id' => $test->id,
                'title' => $test->title,
                'pass_percent' => $test->pass_percent,
                'time_limit_minutes' => $test->time_limit_minutes,
                'max_attempts' => $test->max_attempts,
                'cooldown_minutes' => $test->cooldown_minutes,
                'questions' => $questions->map(fn($q) => [
                    'id' => $q->id,
                    'text' => $q->text,
                    'order' => $q->order,
                    'allows_multiple' => $q->options->where('is_correct', true)->count() > 1,
                    'options' => $q->options->map(fn($o) => [
                        'id' => $o->id,
                        'text' => $o->text,
                    ])->values(),
                ])->values(),
            ],
            'attempt_policy' => [
                'attempts_count' => $attemptsCount,
                'can_take_test' => $canTakeTest,
                'blocked_reason' => $blockedReason,
            ],
            'timing' => [
                'started_at' => $timing['started_at'],
                'expires_at' => $timing['expires_at'],
                'server_now' => now()->toIso8601String(),
            ],
        ]);
    }

    public function results(Request $request, Course $course)
    {
        $this->abortIfNoAccess($course, $request);
        $user = $request->user();
        $test = Test::where('course_id', $course->id)
            ->withCount('questions')
            ->first();

        if (!$test || $test->questions_count === 0) {
            return $this->emptyTestResponse($course, $user);
        }

        $history = TestResult::query()
            ->where('test_id', $test->id)
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        $latest = $history->first();

        return Inertia::render('Tests/Results', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'test' => [
                'id' => $test->id,
                'title' => $test->title,
                'pass_percent' => $test->pass_percent,
                'time_limit_minutes' => $test->time_limit_minutes,
                'max_attempts' => $test->max_attempts,
                'cooldown_minutes' => $test->cooldown_minutes,
            ],
            'results' => [
                'latest' => $latest ? $this->resultPayload($latest) : null,
                'history' => $history->map(fn($result) => $this->resultPayload($result))->values(),
                'latest_details' => $latest ? $this->buildDetailedResult($latest, $test) : [],
            ],
        ]);
    }

    public function submit(Request $request, Course $course)
    {
        $this->abortIfNoAccess($course, $request);
        $test = Test::where('course_id', $course->id)->firstOrFail();

        $attemptsCount = TestResult::where('test_id', $test->id)
            ->where('user_id', $request->user()->id)
            ->count();
        $latest = TestResult::where('test_id', $test->id)
            ->where('user_id', $request->user()->id)
            ->latest()
            ->first();

        [$canTakeTest, $blockedReason] = $this->attemptPolicy($test, $attemptsCount, $latest);
        if (!$canTakeTest) {
            return back()->withErrors(['test' => $blockedReason]);
        }

        $startedAt = session($this->timerSessionKey($test));
        if ($test->time_limit_minutes && $startedAt) {
            $expiresAt = Carbon::parse($startedAt)->addMinutes($test->time_limit_minutes);
            if (now()->greaterThan($expiresAt)) {
                session()->forget($this->timerSessionKey($test));

                return back()->withErrors(['test' => 'Časový limit testu vypršal.']);
            }
        }

        $data = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $questions = $test->questions()
            ->with('options')
            ->orderBy('order')
            ->get();

        $answers = $data['answers'];
        $score = 0;
        $total = $questions->count();

        foreach ($questions as $q) {
            $chosen = $answers[$q->id] ?? null;
            if (!$chosen) {
                continue;
            }

            $correctOptionIds = $q->options
                ->where('is_correct', true)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->sort()
                ->values();
            $chosenIds = collect(is_array($chosen) ? $chosen : [$chosen])
                ->map(fn($id) => (int) $id)
                ->filter()
                ->unique()
                ->sort()
                ->values();

            if ($chosenIds->isNotEmpty() && $chosenIds->all() === $correctOptionIds->all()) {
                $score++;
            }
        }

        $percent = $total > 0 ? round(($score / $total) * 100, 2) : 0;
        $attempt = (int) TestResult::where('test_id', $test->id)
            ->where('user_id', $request->user()->id)
            ->max('attempt') + 1;

        TestResult::create([
            'user_id' => $request->user()->id,
            'test_id' => $test->id,
            'attempt' => $attempt,
            'score' => $score,
            'max_score' => $total,
            'percent' => $percent,
            'passed' => $percent >= $test->pass_percent,
            'answers' => $answers,
        ]);

        session()->forget($this->timerSessionKey($test));

        return redirect()
            ->route('courses.test.results', $course)
            ->with('success', "Výsledok: $score / $total");
    }

    private function abortIfNoAccess(Course $course, Request $request): void
    {
        $user = $request->user();
        $isEnrolled = Enrollment::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->exists();

        $canAccess = $course->is_public
            || $user->isAdmin()
            || ($user->isTeacher() && $course->teacher_id === $user->id)
            || $isEnrolled;

        if (!$canAccess) {
            abort(403);
        }
    }

    private function emptyTestResponse(Course $course, $user)
    {
        return Inertia::render('Tests/Empty', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'can_manage' => $user && ($user->isAdmin() || ($user->isTeacher() && $course->teacher_id === $user->id)),
        ]);
    }

    private function attemptPolicy(Test $test, int $attemptsCount, ?TestResult $latest): array
    {
        if ($test->max_attempts && $attemptsCount >= $test->max_attempts) {
            return [false, 'Dosiahli ste maximálny počet pokusov.'];
        }

        if ($latest && $test->cooldown_minutes > 0) {
            $nextAttemptAt = $latest->created_at?->copy()->addMinutes($test->cooldown_minutes);
            if ($nextAttemptAt && now()->lt($nextAttemptAt)) {
                return [false, 'Ďalší pokus bude dostupný od ' . $nextAttemptAt->format('Y-m-d H:i') . '.'];
            }
        }

        return [true, null];
    }

    private function buildDetailedResult(TestResult $result, Test $test): array
    {
        return $test->questions()
            ->with(['options' => fn($q) => $q->orderBy('id')])
            ->orderBy('order')
            ->get()
            ->map(function ($question) use ($result) {
                $chosen = $result->answers[$question->id] ?? [];
                $chosenIds = collect(is_array($chosen) ? $chosen : [$chosen])
                    ->map(fn($id) => (int) $id)
                    ->filter()
                    ->sort()
                    ->values();
                $correctIds = $question->options
                    ->where('is_correct', true)
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->sort()
                    ->values();

                return [
                    'id' => $question->id,
                    'text' => $question->text,
                    'is_correct' => $chosenIds->all() === $correctIds->all(),
                    'options' => $question->options->map(fn($option) => [
                        'id' => $option->id,
                        'text' => $option->text,
                        'was_selected' => $chosenIds->contains((int) $option->id),
                        'is_correct' => (bool) $option->is_correct,
                    ])->values(),
                ];
            })
            ->values()
            ->all();
    }

    private function resultPayload(TestResult $result): array
    {
        return [
            'id' => $result->id,
            'attempt' => $result->attempt,
            'score' => $result->score,
            'max_score' => $result->max_score,
            'percent' => $result->percent,
            'passed' => $result->passed,
            'created_at' => $result->created_at?->toDateTimeString(),
        ];
    }

    private function prepareTiming(Test $test, bool $canTakeTest): array
    {
        if (!$test->time_limit_minutes || !$canTakeTest) {
            return [
                'started_at' => null,
                'expires_at' => null,
                'expired' => false,
            ];
        }

        $sessionKey = $this->timerSessionKey($test);
        $startedAt = session($sessionKey);

        if (!$startedAt) {
            $startedAt = now();
            session([$sessionKey => $startedAt]);
        }

        $startedAt = Carbon::parse($startedAt);
        $expiresAt = $startedAt->copy()->addMinutes($test->time_limit_minutes);

        return [
            'started_at' => $startedAt->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'expired' => now()->greaterThan($expiresAt),
        ];
    }

    private function timerSessionKey(Test $test): string
    {
        return 'test_started_at_' . $test->id;
    }
}
