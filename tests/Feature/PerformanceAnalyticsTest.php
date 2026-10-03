<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User,Course,Lesson,LessonProgress,Enrollment,Test as CourseTest,Question,AnswerOption,TestResult};
class PerformanceAnalyticsTest extends TestCase {
 use RefreshDatabase;
 private function fixture(): array {
  $teacher=User::factory()->withoutTwoFactor()->create(['role'=>'teacher']);
  $course=Course::create(['title'=>'Analytics','teacher_id'=>$teacher->id,'is_public'=>false]);
  $students=User::factory()->count(3)->withoutTwoFactor()->create();
  foreach($students as $s) Enrollment::create(['course_id'=>$course->id,'user_id'=>$s->id]);
  $l1=Lesson::create(['course_id'=>$course->id,'title'=>'First','order'=>1]);
  $l2=Lesson::create(['course_id'=>$course->id,'title'=>'Second','order'=>2]);
  LessonProgress::create(['user_id'=>$students[0]->id,'lesson_id'=>$l1->id,'completed_at'=>now(),'progress'=>100]);
  LessonProgress::create(['user_id'=>$students[0]->id,'lesson_id'=>$l2->id,'completed_at'=>null,'progress'=>50]);
  $test=CourseTest::create(['course_id'=>$course->id,'title'=>'Quiz','questions'=>[],'pass_percent'=>60]);
  $q=Question::create(['test_id'=>$test->id,'text'=>'Q','order'=>1]);
  $correct=AnswerOption::create(['question_id'=>$q->id,'text'=>'Yes','is_correct'=>true]);
  $wrong=AnswerOption::create(['question_id'=>$q->id,'text'=>'No','is_correct'=>false]);
  foreach([[0,1,100,$correct->id,'2026-01-01'],[0,2,0,$wrong->id,'2026-01-02'],[1,1,100,$correct->id,'2026-01-01']] as [$s,$a,$percent,$option,$date]) {
   $r=TestResult::create(['test_id'=>$test->id,'user_id'=>$students[$s]->id,'attempt'=>$a,'score'=>$percent/100,'max_score'=>1,'percent'=>$percent,'passed'=>$percent>=60,'answers'=>[$q->id=>[$option]]]);$r->created_at=$date;$r->save();
  }
  return [$teacher,$course,$students,$test];
 }
 public function test_latest_results_progress_counts_average_and_wrong_answers(): void {
  [$teacher,$course]=$this->fixture();
  $this->actingAs($teacher)->get(route('teacher.courses.manage',$course))->assertOk()->assertInertia(fn($p)=>$p->component('Teacher/CourseManage')
   ->where('analytics.enrolled_count',3)->where('analytics.test_attempts_count',3)->where('analytics.students_attempted_count',2)
   ->where('analytics.average_percent',50)->where('analytics.students.0.completed_lessons',1)
   ->where('analytics.students.0.completed_lesson_titles.0','First')->where('analytics.students.0.test_attempt',2)
   ->where('analytics.students.0.test_percent',0)->where('analytics.students.0.test_passed',false)
   ->where('analytics.students.2.test_attempted',false)->where('analytics.students.2.test_percent',null)
   ->where('analytics.wrong_questions.0.wrong_count',1)->where('analytics.wrong_questions.0.answered_count',3));
 }
 public function test_unauthorized_teacher_student_and_guest_cannot_read_analytics():void {
  [$teacher,$course,$students]=$this->fixture();
  $this->get(route('teacher.courses.manage',$course))->assertRedirect('/login');
  $this->actingAs($students[0])->get(route('teacher.courses.manage',$course))->assertForbidden();
  $other=User::factory()->create(['role'=>'teacher']);
  $this->actingAs($other)->get(route('teacher.courses.manage',$course))->assertForbidden();
 }
 public function test_empty_course_and_admin_access():void {
  $admin=User::factory()->create(['role'=>'admin']);$c=Course::create(['title'=>'Empty']);
  $this->actingAs($admin)->get(route('teacher.courses.manage',$c))->assertOk()->assertInertia(fn($p)=>$p->where('analytics.enrolled_count',0)->where('analytics.test_attempts_count',0)->where('analytics.average_percent',null)->has('analytics.students',0)->has('analytics.wrong_questions',0));
 }
}
