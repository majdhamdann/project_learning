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
use App\Http\Controllers\ConversationController;

use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;
 
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
   



        //اضافة طلاب للمادة
     Route::post('/subjects/{subject}/students', [SubjectController::class, 'addStudentsToSubject']);
       //ازالة طالب من مادة
     Route::post('subjects/{subjectId}/remove-student', [SubjectController::class, 'removeStudentFromSubject']);

      // ادارة الدروس

     

      Route::post('/import_excel', [ImportController::class, 'import']);

      //انشاء اختبار يدوي 
      Route::post('/create/test/by/teacher',[TestController::class,'createTestWithQuestions']);  

      //حذف اختبار 
      Route::delete('/delete/test/{test_id}',[TestController::class,'deleteTest']);
});

Route::middleware(['auth:sanctum'])->group(function () {
   //للاختبارات الخاصة بالطالب
Route::post('/create/test/student', [TestController::class, 'createTest']);
Route::post('/tests/{testId}/submit', [TestController::class, 'submitAnswers']);
Route::get('/tests/{testId}/result', [TestController::class, 'getTestResult']);

 // حسب الاختيار امكانية التصفح
Route::get('/lessons/view/{subjectId}', [LessonController::class, 'viewLessonOrPage']);

//فتح رابط الاختبار
Route::get('/test/{test}', function (Test $test) {
    return response()->json($test->load('questions.options'));
})->name('test.show')->middleware('signed');
});



//            //             قسم الاستاذ 


Route::middleware(['auth:sanctum', 'teacher'])->group(function () {
  //اضافة,حذف سؤال
  Route::post('/add-question', [QuestionController::class, 'addQuestionWithOptions']);

  //حذف سؤال
  Route::delete('/delet-question/{id}', [QuestionController::class, 'deleteQuestion']);

//انشاء رابط للاختبار
  Route::post('/create-test/{subjectId}', [TestController::class, 'generateTest']);

//رؤية اجوبة الطلاب
  Route::get('/view_answar/{testId}', [TestController::class, 'viewStudentAnswers']);
//تصدير الملف كword
  Route::POST('/tests/export', [TestController::class, 'exportTestQuestionsToWord']);

//عرض الطلاب الخاصين بالاستاذ 
Route::get('/teacher_favorite', [TeacherController::class, 'getMyFavoriteStudents']);

//عرض نتائج الاختبارات لجميع الطلاب
Route::get('/soluation-to-student', [TestController::class, 'getStudentSolutionsBySubject']);

//اضافة طالب للاستاذ
Route::post('/add/favorite/student/{student_id}',[TeacherController::class,'addFavoriteStudent']);

// اضافة مجموعة طلاب للمفضلة 

Route::post('/add/favorite/students',[TeacherController::class,'addFavoriteStudents']);

//حذف طالب من المفضلة 
Route::delete('/delete/favorite/student/{student_id}',[TeacherController::class,'removeFavoriteStudent']);


//عرض الطلبات للاشتراك بمادة
Route::get('/admin/subject-requests', [AdminController::class, 'listSubjectRequests']);

//تغيير حالة طلب الاشترك بمادة 
  Route::post('/admin/subject-requests/{request_id}', [AdminController::class, 'handleSubjectRequest']);

//اضافة البيانات الاضافية للاستاذ 
Route::post('/add/details/teacher',[TeacherController::class,'storeTeacherDetails']);

//اضافلة فيديو لدرس
Route::post('/add/video/lesson/{lesson_id}',[LessonController::class,'uploadLessonVideo']);

//اضافة ملخص لدرس 
Route::post('/add/summary/lesson/{lesson_id}',[LessonController::class,'uploadLessonSummary']);


//عرض طلاب استاذ 
Route::get('/get/students/teacher',[StudentController::class,'getStudentsForTeacherSubject']);

//اضافة اختبار للمفضلة 
Route::post('/add/test/to/favorite/teacher/{test_id}',[TeacherController::class,'addFavoriteTest']);

//حذف اختبار من المفضلة 
Route::delete('/delete/test/from/favorite/teacher/{test_id}',[TeacherController::class,'removeFavoriteTest']);

 //اضافة درس 
 Route::post('/add/lesson', [LessonController::class, 'addlesson']);

 //حذف درس
 Route::delete('/delete/lesson/{id}', [LessonController::class, 'deleteLesson']);

 
//طلب الانضمام لمادة 
//اشعار للادمن
//request_teacher_join_subject
Route::post('/request/teacher/join/subject',[TeacherController::class,'requestToJoinSubject']);

//عرض حالة الطلب للانضمام لمادة 
Route::get('/get/request/teacher/subject',[TeacherController::class,'getTeacherRequests']);

//عرض كامل اسئلة الاستاذ 
Route::get('/get/all/questions/by/teacher',[TeacherController::class,'getAllQuestionsByTeacher']);

//انشاء تحدي 
Route::post('/create/challenge',[TeacherController::class,'createChallenge']);

//اضافة سؤال للتحدي 
Route::post('/add/question/challenge/{challenge_id}',[TeacherController::class,'addQuestionToChallenge']);

//عرض تحديات الاستاذ 
Route::get('/get/challenges/teacher',[TeacherController::class,'getChallengesForTeacher']);

//عرض نقاط الطلاب عند الاستاذ
Route::get('/get/points/students/teacher',[TeacherController::class,'getStudentPoints']);

Route::get('/get/questions/teacher',[TeacherController::class,'getTeacherQuestions']);

 });



//           //            قسم الطالب  


Route::middleware(['auth:sanctum', 'student'])->group(function () {

//عرض الاختبارات من المفضلة 
Route::get('/get/tests/from/favorite/{teacher_id}',[StudentController::class,'getTeacherFavoriteTests']);

//عرض الاساتذة اللي ضافو الطالب عالمفضلة 
Route::get('/get/teachers/favorite/student',[StudentController::class,'getFavoriteTeachersForStudent']);

//عرض نقاط طالب 
Route::get('/get/points/student',[StudentController::class,'getStudentPointsByTeacher']);

//اضافة تقييم للاستاذ 
Route::post('/add/rating/{teacher_id}',[StudentController::class,'rateTeacher']);

//عرض مواد الطالب 
Route::get('/get/subjects/student',[StudentController::class,'getAcceptedSubjects']);

});



//سلم الاختبار 
Route::get('/get/solution/test/{test_id}',[TestController::class,'getTestQuestionsWithCorrectOptions']);

// امكانية التصفح
Route::get('/lessons/viewallQuestion/{subjectId}', [LessonController::class, 'viewAllQuestion'])->middleware('auth:sanctum');

Route::get('/subjects/{id}/show', [SubjectController::class, 'show'])->middleware('auth:sanctum');
//عرض الدروس
Route::get('/viewlesson/{subjectId}', [LessonController::class, 'getLessons']);
//عرض المواد
Route::get('get/subjects', [SubjectController::class, 'getSubjects']);

//عرض الاساتذة لمادة 
Route::get('/get/teachers/subject/{subject_id}',[SubjectController::class,'getTeachersForSubject']);


Route::get('/testsall', [TestController::class, 'getAllTest'])->middleware('auth:sanctum');





//عرض اسئلة اختبار معين 
Route::get('get/questions/test/{test_id}',[TestController::class,'getTestQuestions']);
//عرض نتيجة اختبار لطالب 
Route::get('get/test/result/{test_id}/{student_id}',[TestController::class,'getTestReport']);
//عرض الطلاب اللي مجاوبين صح
Route::get('get/full/market/{test_id}',[TestController::class,'getPerfectStudents']);
//عرض اختبار درس معين 
Route::get('get/tests/lesson/{lesson_id}',[TestController::class,'getTestsByLesson']);

//حذف سؤال من اختبار 
Route::delete('/delete/question/test/{test_id}/{question_id}',[TestController::class,'removeQuestionFromTest']);

//اضافة سؤال لاختبار 

Route::post('/add/question/test/{test_id}/{question_id}',[TestController::class,'addQuestionToTest']);

//عرض جميع نتائج الاختبارات لطالب 

Route::get('/get/all/student/result/test/{student_id}',[TestController::class,'getStudentTestReports']);


//طلب الاشترام بمادة 
Route::middleware(['auth:sanctum'])->post('/student/request-subject', [StudentController::class, 'requestSubject']);


// عرض دروس مادة 
Route::middleware('auth:sanctum')->get('/get/lessons/subject/{subject_id}',[StudentController::class,'getLessonsBySubject']);

//عرض اسئلة درس
Route::get('/get/questions/lesson/{lesson_id}',[QuestionController::class,'getQuestionsByLesson']);
//عرض الاسئلة مع الاختيارات 
Route::get('get/questions/options/{lesson_id}',[QuestionController::class,'getQuestionsWithOptionsByLesson']);

//عرض الاساتذة

Route::get('/get/teachers',[TeacherController::class,'getTeachers']);

//عرض الطلاب 
Route::get('/get/students',[StudentController::class,'getStudents']);

// عرض المعلومات الاضافية لاستاذ
Route::get('/get/profile/teacher/{teacher_id}',[TeacherController::class,'getTeacherProfile']);


//اضافة صورة للمستخدم 

Route::middleware('auth:sanctum')->post('/add/image/profile',[AuthController::class,'updateUserImage']);

//عرض الاختبارات 

Route::get('/get/all/tests',[TestController::class,'getAllTests']);

//عرض مستخدم 
Route::middleware('auth:sanctum')->get('/get/user',[AdminController::class,'getUser']);

//عرض اختبار 
Route::post('/get/test',[TestController::class,'getTest']);





//                  //            قسم مشترك 


Route::middleware(['auth:sanctum'])->group(function () {


//اضافة تعليق 
Route::post('/add/comment',[LessonController::class,'addComment']);

//عرض التعليقات 
Route::get('/get/comment/lesson/{lesson_id}',[LessonController::class,'getLessonComments']);

//عرض الردود على تعليق 
Route::get('/get/replies/comment/{comment_id}',[LessonController::class,'getCommentReplies']);

//عرض اختبارت طالب او استاذ
Route::get('get/tests/user',[TestController::class,'getUserTests']);


//عرض الاسئلة من المفضلة 
Route::get('/get/question/lesson/favorite/{teacher_id}',[QuestionController::class,'getQuestionsFavoriteByTeacher']);

//عرض اسئلة المفضلة مع اختيارات 
Route::get('/get/question/lesson/favorite/with/options/{teacher_id}',[QuestionController::class,'getFavoriteQuestionsWithOptionsByTeacher']);


//عرض دروس استاذ 

Route::post('/get/lessons/teacher/{teacher_id}',[LessonController::class,'getLessonsForTeacherSubject']);

//عرض تحدي 
Route::get('/get/challenge/{challenge_id}',[StudentController::class,'getChallengeQuestions']);

//عرض تحديات طالب
Route::get('/get/challenges/student',[StudentController::class,'getChallengesForstudent']);

//الاجابة على التحدي 
Route::post('/submit/challenge/{challenge_id}',[StudentController::class,'submitChallengeAnswers']);

//انشاء محادثة 
Route::post('/create/conversation',[ConversationController::class,'createConversation']);

//ارسال رسالة 
Route::post('/send/message/{conversation_id}',[ConversationController::class,'sendMessage']);

//عرض الرسائل
Route::get('/get/my/massage/{conversation_id}',[ConversationController::class,'getConversationMessages']);

//عرض المحادثات 
Route::get('/get/my/coversation',[ConversationController::class,'myConversations']);



}); 


//               //                    قسم الادمن 




Route::middleware(['auth:sanctum','admin'])->group(function (){

//اضافة مادة 
  Route::post('add/subjects', [SubjectController::class, 'addSubject']);

 //تعديل مادة
  Route::post('update/subjects/{id}', [SubjectController::class, 'update']);

//حذف مادة
  Route::delete('/subjects/{id}', [SubjectController::class, 'destroy']);

  //عرض طلبات انشاء حساب
 Route::get('/get/register/requests',[AdminController::class,'registerRequests']);

//قبول ورفض حالات انشاء حساب 
//////////////////////////////////////////////
Route::post('response/register/{request_id}',[AdminController::class,'updateRequestStatus']);

//اضافة استاذ
Route::post('/register/teacher',[AdminController::class,'registerTeacher']);

//عرض المستخدمين 
Route::get('/get/users',[AdminController::class,'getUsers']);

//عرض طلبات الاساتذة للانضمام لمادة 
Route::get('/get/request/join/subject',[AdminController::class,'getPendingTeacherSubjectRequests']);


///////////////////////////////
//قبول ورفض طلبات الاساتذة 
Route::post('/response/teacher/join/subject/{request_id}',[AdminController::class,'handleTeacherSubjectRequest']);

//حذف مستخدم 
Route::delete('/delete/user/{user_id}',[AdminController::class,'deleteUser']);

//حذف استاذ من مادة 
Route::post('/delete/teacher/from/subject',[AdminController::class,'removeTeacherFromSubject']);

//عرض مواد استاذ 
Route::get('get/subjects/teacher/{teacherId}', [AdminController::class, 'getTeacherSubjects']);
 
//عرض كامل معومات الاستاذ 
Route::get('/get/all/profile/teacher/{teacher_id}',[AdminController::class,'getTeacherDetails']);

//عرض تقييمات الاساتذة 
Route::get('/get/ratings',[AdminController::class,'getTeachersRatings']);

//عرض عدد الدروس 
Route::get('/get/lessons/count',[AdminController::class,'getLessonsCount']);

//عرض المواد مع عدد الدروس 
Route::get('/get/subjects/with/count/lessons',[AdminController::class,'getSubjectsWithLessonsCount']);
       });


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});