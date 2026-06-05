<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TeacherActivationController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\TeacherCourseController;
use App\Http\Controllers\UploadController;

Route::middleware(['auth', 'active', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'admin', 'active'])->group(function () {
    Route::get('/admin/users', [UserManagementController::class, 'index'])
        ->name('admin.users');

    Route::patch('/admin/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])
        ->name('admin.users.toggleActive');

    Route::patch('/admin/users/{user}/role', [UserManagementController::class, 'setRole'])
        ->name('admin.users.setRole');
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

Route::middleware(['auth', 'teacher', 'active'])->group(function () {
    Route::get('/teacher/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/teacher/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/teacher/courses/{course}/manage', [TeacherCourseController::class, 'manage'])->name('teacher.courses.manage');
    Route::put('/teacher/courses/{course}', [TeacherCourseController::class, 'updateCourse'])->name('teacher.courses.update');
    Route::get('/teacher/courses/{course}/lessons/create', [TeacherCourseController::class, 'createLesson'])->name('teacher.courses.lessons.create');
    Route::post('/teacher/courses/{course}/lessons', [TeacherCourseController::class, 'storeLesson'])->name('teacher.courses.lessons.store');
    Route::get('/teacher/courses/{course}/lessons/{lesson}/edit', [TeacherCourseController::class, 'editLesson'])->name('teacher.courses.lessons.edit');
    Route::put('/teacher/courses/{course}/lessons/{lesson}', [TeacherCourseController::class, 'updateLesson'])->name('teacher.courses.lessons.update');
    Route::delete('/teacher/courses/{course}/lessons/{lesson}', [TeacherCourseController::class, 'deleteLesson'])->name('teacher.courses.lessons.delete');
    Route::post('/teacher/courses/{course}/lessons/{lesson}/materials', [TeacherCourseController::class, 'storeMaterial'])->name('teacher.courses.lessons.materials.store');
    Route::delete('/teacher/courses/{course}/lessons/{lesson}/materials/{material}', [TeacherCourseController::class, 'deleteMaterial'])->name('teacher.courses.lessons.materials.delete');
    Route::post('/teacher/uploads/images', [UploadController::class, 'storeImage'])->name('teacher.uploads.images');

    Route::put('/teacher/courses/{course}/access', [TeacherCourseController::class, 'updateAccess'])->name('teacher.courses.access.update');
    Route::post('/teacher/courses/{course}/access/regenerate', [TeacherCourseController::class, 'regenerateCode'])->name('teacher.courses.access.regenerate');

    Route::put('/teacher/courses/{course}/test', [TeacherCourseController::class, 'updateTest'])->name('teacher.courses.test.update');
    Route::get('/teacher/courses/{course}/test/questions/create', [TeacherCourseController::class, 'createQuestion'])->name('teacher.courses.questions.create');
    Route::post('/teacher/courses/{course}/test/questions', [TeacherCourseController::class, 'storeQuestion'])->name('teacher.courses.questions.store');
    Route::get('/teacher/courses/{course}/test/questions/{question}/edit', [TeacherCourseController::class, 'editQuestion'])->name('teacher.courses.questions.edit');
    Route::put('/teacher/courses/{course}/test/questions/{question}', [TeacherCourseController::class, 'updateQuestion'])->name('teacher.courses.questions.update');
    Route::delete('/teacher/courses/{course}/test/questions/{question}', [TeacherCourseController::class, 'deleteQuestion'])->name('teacher.courses.questions.delete');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/become-teacher', [TeacherActivationController::class, 'create'])->name('teacher.activate.form');
    Route::post('/teacher/activate', [TeacherActivationController::class, 'store'])->name('teacher.activate');
});

Route::get('/courses/{course}/lessons/{lesson}', [LessonController::class, 'show'])
    ->name('lessons.show');

Route::middleware(['auth', 'active', 'verified'])->group(function () {
    Route::get('/courses/{course}/test', [TestController::class, 'show'])->name('courses.test.show');
    Route::post('/courses/{course}/test', [TestController::class, 'submit'])->name('courses.test.submit');
    Route::get('/courses/{course}/test/results', [TestController::class, 'results'])->name('courses.test.results');
    Route::post('/courses/{course}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');
    Route::delete('/courses/{course}/enroll', [CourseController::class, 'unenroll'])->name('courses.unenroll');
    Route::get('/my-courses', [CourseController::class, 'myCourses'])->name('courses.my');
});

require __DIR__.'/settings.php';
