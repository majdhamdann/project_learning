<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\AdminController;
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
Route::post('/register/admin', [AdminController::class, 'registerAdmin']);

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

    // Route::middleware(['auth:sanctum', 'can:isAdmin'])->post('add/subjects', [SubjectController::class, 'addSubject']);


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
Route::post('/tests', [TestController::class, 'createTest']);
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


//عرض الطلاب الخاصين بالاستاذ 
Route::get('/teacher_favorite/{id}', [TeacherController::class, 'getFavoriteStudents']);
//اضافة طالب للاستاذ
Route::post('/add/favorite/student/{teacher_id}/{student_id}',[TeacherController::class,'addFavoriteStudent']);
//عرض اختبارت طالب
Route::get('get/tests/student/{student_id}',[TestController::class,'getTestsByStudent']);
//عرض اختبارات استاذ
Route::get('get/tests/teacher/{teacher_id}',[TestController::class,'getTestsByTeacher']);
//عرض اسئلة اختبار معين 
Route::get('get/questions/test/{test_id}',[TestController::class,'getTestQuestions']);
//عرض نتيجة اختبار لطالب 
Route::get('get/test/result/{test_id}/{student_id}',[TestController::class,'getTestReport']);
//عرض الطلاب اللي مجاوبين صح
Route::get('get/full/market/{test_id}',[TestController::class,'getPerfectStudents']);
//عرض اختبار درس معين 
Route::get('get/tests/lesson/{lesson_id}',[TestController::class,'getTestsByLesson']);





//طلب الاشترام بمادة 
Route::middleware(['auth:sanctum'])->post('/student/request-subject', [StudentController::class, 'requestSubject']);


/*
Route::middleware(['auth:sanctum', 'can:isAdmin'])->group(function () {
  // الآن يتأكد إنه مسجل دخول AND إنه أدمن
 //عرض الطلبات للاشتراك بمادة
  Route::get('/admin/subject-requests', [AdminController::class, 'listSubjectRequests']);

//تغيير حالة طلب الاشترك بمادة 
  Route::post('/admin/subject-requests/{request_id}', [AdminController::class, 'handleSubjectRequest']);

//Route::post('add/subjects', [SubjectController::class, 'addSubject']);

});
*/
// عرض دروس مادة 
Route::middleware('auth:sanctum')->get('/get/lessons/subject/{subject_id}',[StudentController::class,'getLessonsBySubject']);

//عرض اسئلة درس
Route::get('/get/questions/lesson/{lesson_id}',[QuestionController::class,'getQuestionsByLesson']);
//عرض الاسئلة مع الاختيارات 
Route::get('get/questions/options/{lesson_id}',[QuestionController::class,'getQuestionsWithOptionsByLesson']);




//                                      قسم الادمن 

Route::middleware(['auth:sanctum','admin'])->group(function (){

//اضافة مادة 
  Route::post('add/subjects', [SubjectController::class, 'addSubject']);

  //تعديل مادة
  Route::post('update/subjects/{id}', [SubjectController::class, 'update']);
//حذف مادة

  Route::delete('/subjects/{id}', [SubjectController::class, 'destroy']);

//عرض الطلبات للاشتراك بمادة
  Route::get('/admin/subject-requests', [AdminController::class, 'listSubjectRequests']);

//تغيير حالة طلب الاشترك بمادة 
  Route::post('/admin/subject-requests/{request_id}', [AdminController::class, 'handleSubjectRequest']);

  //عرض طلبات انشاء حساب 
  Route::get('/get/register/requests',[AdminController::class,'registerRequests']);

//قبول ورفض حالات انشاء حساب 
Route::post('response/register/{request_id}',[AdminController::class,'updateRequestStatus']);

//اضافة استاذ
Route::post('/register/teacher',[AdminController::class,'registerTeacher']);



       });