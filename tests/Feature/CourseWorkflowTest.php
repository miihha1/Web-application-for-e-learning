<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_created_course_is_public(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('courses.store'), [
            'title' => 'Admin Course',
            'description' => 'Open course',
        ]);

        $course = Course::where('title', 'Admin Course')->firstOrFail();

        $response->assertRedirect(route('teacher.courses.manage', $course));
        $this->assertTrue($course->is_public);
        $this->assertNull($course->teacher_id);
        $this->assertNull($course->enroll_code);
    }

    public function test_teacher_created_course_requires_enrollment_code(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $response = $this->actingAs($teacher)->post(route('courses.store'), [
            'title' => 'Teacher Course',
            'description' => 'Private course',
        ]);

        $course = Course::where('title', 'Teacher Course')->firstOrFail();

        $response->assertRedirect(route('teacher.courses.manage', $course));
        $this->assertFalse($course->is_public);
        $this->assertSame($teacher->id, $course->teacher_id);
        $this->assertNotEmpty($course->enroll_code);
    }

    public function test_student_needs_correct_code_to_enroll_private_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Private Course',
            'description' => 'Needs code',
            'teacher_id' => $teacher->id,
            'is_public' => false,
            'enroll_code' => 'ABC12345',
        ]);

        $this->actingAs($student)
            ->post(route('courses.enroll', $course), ['code' => 'WRONG'])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $this->actingAs($student)
            ->post(route('courses.enroll', $course), ['code' => 'ABC12345'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_student_can_enroll_public_course_without_code(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Public Course',
            'description' => 'Open',
            'is_public' => true,
            'enroll_code' => null,
        ]);

        $this->actingAs($student)
            ->post(route('courses.enroll', $course))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_course_creator_can_switch_access_type_and_regenerate_code(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = Course::create([
            'title' => 'Configurable Course',
            'description' => 'Access can change',
            'teacher_id' => $teacher->id,
            'is_public' => false,
            'enroll_code' => 'OLD12345',
        ]);

        $this->actingAs($teacher)
            ->put(route('teacher.courses.access.update', $course), ['is_public' => true])
            ->assertSessionHasNoErrors();

        $course->refresh();
        $this->assertTrue($course->is_public);
        $this->assertNull($course->enroll_code);

        $this->actingAs($teacher)
            ->put(route('teacher.courses.access.update', $course), ['is_public' => false])
            ->assertSessionHasNoErrors();

        $course->refresh();
        $this->assertFalse($course->is_public);
        $this->assertNotEmpty($course->enroll_code);

        $previousCode = $course->enroll_code;

        $this->actingAs($teacher)
            ->post(route('teacher.courses.access.regenerate', $course))
            ->assertSessionHasNoErrors();

        $this->assertNotSame($previousCode, $course->refresh()->enroll_code);
    }

    public function test_my_courses_page_shows_student_enrollments(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Enrolled Course',
            'description' => 'Visible in my courses',
            'is_public' => true,
        ]);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $this->actingAs($student)
            ->get(route('courses.my'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Courses/My')
                ->has('courses', 1)
                ->where('courses.0.title', 'Enrolled Course')
            );
    }

    public function test_private_course_detail_shows_lesson_count_without_exposing_lessons(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Private Course',
            'description' => 'Hidden lessons',
            'teacher_id' => $teacher->id,
            'is_public' => false,
            'enroll_code' => 'ABC12345',
        ]);

        foreach (range(1, 3) as $order) {
            Lesson::create([
                'course_id' => $course->id,
                'title' => "Lesson {$order}",
                'content' => "Content {$order}",
                'order' => $order,
            ]);
        }

        $this->actingAs($student)
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Courses/Show')
                ->where('course.lessons_count', 3)
                ->has('course.lessons', 0)
            );
    }
}
