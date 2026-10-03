<?php
require __DIR__.'/../../vendor/autoload.php';
$app=require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!$app->environment('performance')) throw new RuntimeException('Performance environment required');
$output=[];
foreach ([1,2,3] as $cid) {
    $request=Illuminate\Http\Request::create('/teacher/courses/'.$cid.'/manage');
    $request->setUserResolver(fn()=>App\Models\User::find(1));
    $response=(new App\Http\Controllers\TeacherCourseController)->manage($request,App\Models\Course::findOrFail($cid));
    $page=$response->toResponse($request)->getOriginalContent()['page'];
    $output[$cid]=$page['props']['analytics'];
}
echo json_encode($output, JSON_PRETTY_PRINT);
