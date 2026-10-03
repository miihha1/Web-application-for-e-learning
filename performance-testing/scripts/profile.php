<?php
require __DIR__.'/../../vendor/autoload.php';
$app=require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!$app->environment('performance')) throw new RuntimeException('Performance environment required');
$queries=[];Illuminate\Support\Facades\DB::listen(function($q)use(&$queries){$queries[]=['sql'=>$q->sql,'ms'=>$q->time];});
$cid=(int)($argv[1]??3);$samples=[];
for($i=0;$i<6;$i++){
 $queries=[];$start=hrtime(true);$course=App\Models\Course::findOrFail($cid);
 $req=Illuminate\Http\Request::create('/teacher/courses/'.$cid.'/manage');$req->setUserResolver(fn()=>App\Models\User::find(1));
 $response=(new App\Http\Controllers\TeacherCourseController)->manage($req,$course);
 $props=$response->toResponse($req)->getOriginalContent()['page']['props']??null;
 $samples[]=['ms'=>(hrtime(true)-$start)/1e6,'memory_peak'=>memory_get_peak_usage(true),'queries'=>$queries,'sql_ms'=>array_sum(array_column($queries,'ms'))];
}
echo json_encode(['dataset'=>$cid,'samples'=>$samples],JSON_PRETTY_PRINT);
