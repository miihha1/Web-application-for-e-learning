<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class PerformanceSeeder extends Seeder {
 public function run(): void {
  if (!app()->environment('performance') || !str_contains(config('database.connections.sqlite.database'), 'performance-testing')) throw new \RuntimeException('Dedicated performance database required');
  if (DB::table('users')->exists()) throw new \RuntimeException('Use a new empty performance database');
  mt_srand(202627); $stamp='2026-09-01 10:00:00'; $hash=Hash::make('Perf-local-2026!');
  DB::transaction(function() use($stamp,$hash) {
   $uid=1;$lid=1;$qid=1;$oid=1;$rid=1;$eid=1;$pid=1;
   $user=function($id,$role,$email)use($stamp,$hash){DB::table('users')->insert(['id'=>$id,'name'=>"Performance $role $id",'email'=>$email,'password'=>$hash,'role'=>$role,'active'=>1,'email_verified_at'=>$stamp,'created_at'=>$stamp,'updated_at'=>$stamp]);};
   $user($uid++,'teacher','teacher@performance.test');
   $user($uid++,'admin','admin@performance.test');
   $manifest=[];
   foreach([1=>50,2=>250,3=>1000] as $cid=>$n){
    DB::table('courses')->insert(['id'=>$cid,'title'=>"Performance $n",'teacher_id'=>1,'is_public'=>1,'created_at'=>$stamp,'updated_at'=>$stamp]);
    $lessons=[];for($l=0;$l<20;$l++){ $lessons[]=$lid; DB::table('lessons')->insert(['id'=>$lid++,'course_id'=>$cid,'title'=>"Lesson $l",'content'=>str_repeat('Study content. ',100),'order'=>$l+1,'created_at'=>$stamp,'updated_at'=>$stamp]); }
    DB::table('tests')->insert(['id'=>$cid,'course_id'=>$cid,'title'=>'Performance test','questions'=>'[]','pass_percent'=>60,'cooldown_minutes'=>0,'created_at'=>$stamp,'updated_at'=>$stamp]);
    $options=[];for($q=0;$q<10;$q++){ $id=$qid++; DB::table('questions')->insert(['id'=>$id,'test_id'=>$cid,'text'=>"Question $q",'order'=>$q+1,'created_at'=>$stamp,'updated_at'=>$stamp]);$options[$id]=[];for($o=0;$o<4;$o++){ $options[$id][]=$oid;DB::table('answer_options')->insert(['id'=>$oid++,'question_id'=>$id,'text'=>"Option $o",'is_correct'=>$o==0,'created_at'=>$stamp,'updated_at'=>$stamp]); } }
    $progressCount=0;
    for($s=0;$s<$n;$s++) { $id=$uid++;$user($id,'student',"student-$cid-$s@performance.test");DB::table('enrollments')->insert(['id'=>$eid++,'user_id'=>$id,'course_id'=>$cid,'created_at'=>$stamp,'updated_at'=>$stamp]);
     foreach($lessons as $j=>$lesson){if(($s+$j)%3!==0){DB::table('lesson_progress')->insert(['id'=>$pid++,'user_id'=>$id,'lesson_id'=>$lesson,'progress'=>100,'completed_at'=>$stamp,'created_at'=>$stamp,'updated_at'=>$stamp]);$progressCount++;}}
     for($a=1;$a<=5;$a++){ $answers=[];$score=0;foreach($options as $question=>$opts){$choice=mt_rand(0,3);$answers[$question]=[$opts[$choice]];$score+=($choice==0);}$time="2026-09-0$a 10:00:00";DB::table('test_results')->insert(['id'=>$rid++,'test_id'=>$cid,'user_id'=>$id,'attempt'=>$a,'score'=>$score,'max_score'=>10,'percent'=>$score*10,'passed'=>$score>=6,'answers'=>json_encode($answers),'created_at'=>$time,'updated_at'=>$time]); }
    }
    $manifest[]=['course_id'=>$cid,'students'=>$n,'lessons'=>20,'questions'=>10,'options'=>40,'results'=>$n*5,'completed_progress'=>$progressCount];
   }
   file_put_contents(base_path('performance-testing/reports/dataset.json'),json_encode(['seed'=>202627,'datasets'=>$manifest],JSON_PRETTY_PRINT));
  });
 }
}
