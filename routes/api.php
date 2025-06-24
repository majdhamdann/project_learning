<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->post('/user/update-profile-image', [AuthController::class, 'updateImage']);

Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum','teacher'])->group(function () {

    //ادارة مستخدم
     Route::get('/users', [UserController::class, 'index']); 
     Route::post('/users', [UserController::class, 'create']); 
     Route::put('/users/{id}', [UserController::class, 'update']); 
     Route::delete('/users/{id}', [UserController::class, 'delete']); 
   
     // ادارة المواد
     Route::get('/subjects', [SubjectController::class, 'index']);
     Route::post('/subjects', [SubjectController::class, 'addSubject']);
     Route::put('/subjects/{id}', [SubjectController::class, 'update']);
     Route::delete('/subjects/{id}', [SubjectController::class, 'destroy']);
        //اضافة طلاب للمادة
     Route::post('/subjects/{subject}/students', [SubjectController::class, 'addStudentsToSubject']);
       //ازالة طالب من مادة
     Route::post('subjects/{subjectId}/remove-student', [SubjectController::class, 'removeStudentFromSubject']);

      // ادارة الدروس
      Route::post('lesson', [LessonController::class, 'addlesson']);
      Route::delete('lesson/{id}', [LessonController::class, 'deleteLesson']);

      Route::post('/import_excel', [ImportController::class, 'import']);


});

Route::middleware(['auth:sanctum'])->group(function () {
   //للاختبارات الخاصة بالطالب
Route::get('/tests', [TestController::class, 'createTest']);
Route::post('/tests/{testId}/submit', [TestController::class, 'submitAnswers']);
Route::get('/tests/{testId}/result', [TestController::class, 'getTestResult']);

 // حسب الاختيار امكانية التصفح
Route::get('/lessons/view/{subjectId}', [LessonController::class, 'viewLessonOrPage']);

//فتح رابط الاختبار
Route::get('/test/{test}', function (Test $test) {
    return response()->json($test->load('questions.options'));
})->name('test.show')->middleware('signed');
});

Route::middleware(['auth:sanctum', 'teacher'])->group(function () {
  //اضافة,حذف سؤال
  Route::post('/add-question', [QuestionController::class, 'addQuestionWithOptions']);
  Route::delete('/delet-question/{id}', [QuestionController::class, 'deleteQuestion']);

//انشاء رابط للاختبار
  Route::get('/create-test/{subjectId}', [TestController::class, 'generateTest']);

//رؤية اجوبة الطلاب
  Route::get('/view_answar/{testId}', [TestController::class, 'viewStudentAnswers']);
//تصدير الملف كword
  Route::POST('/tests/export', [TestController::class, 'exportTestQuestionsToWord']);



 });

// امكانية التصفح
Route::get('/lessons/viewallQuestion/{subjectId}', [LessonController::class, 'viewAllQuestion'])->middleware('auth:sanctum');

Route::get('/subjects/{id}/show', [SubjectController::class, 'show'])->middleware('auth:sanctum');
//عرض الدروس
Route::get('/viewlesson/{subjectId}', [LessonController::class, 'getLessons']);

Route::get('/subjects', [SubjectController::class, 'index']);



Route::get('/testsall', [TestController::class, 'getAllTest'])->middleware('auth:sanctum');

