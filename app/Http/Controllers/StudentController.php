<?php

namespace App\Http\Controllers;
use Carbon\Carbon;

use App\Models\Subject;
use App\Models\Student;
use App\Models\User;
use App\Models\TeacherRating;
use App\Models\Test;
use App\Models\Challenge;
use App\Models\SubjectRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SubjectStudent;
use Illuminate\Support\Facades\DB;
use App\Models\Lesson;
use App\Models\Point;




class StudentController extends Controller
{

    public function addStudentsToSubject(Request $request, $subjectId)
{
    $validated = $request->validate([
        'student_ids' => 'required|array',
        'student_ids.*' => 'exists:users,id'
    ]);

    $subject = Subject::findOrFail($subjectId);
    $subject->user()->syncWithoutDetaching($validated['student_ids']);

    return response()->json([
        'message' => 'تم إضافة الطلاب إلى المادة بنجاح'
    ]);
}



public function subscribe(Request $request)
{
    $request->validate([
        'student_id' => 'required|exists:students,id',
        'subject_id' => 'required|exists:subjects,id',
    ]);

    $student = Student::find($request->student_id);
    $subject_id = $request->subject_id;

   
    if ($student->subjects()->where('subject_id', $subject_id)->exists()) {
        return response()->json([
            'message' => 'تم تقديم طلب مسبق لهذه المادة.',
        ], 409); 
    }

  
    $student->subjects()->attach($subject_id, ['status' => 'pending']);

    return response()->json([
        'message' => 'تم إرسال طلب الاشتراك بنجاح.',
    ], 200);
}


public function requestSubject(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthorized. Please log in first.'], 401);
    }

    $request->validate([
        'subject_id' => 'required|exists:subjects,id',
        'teacher_id' => 'required|exists:users,id', 
    ]);

    // Check for existing pending request
    $existing = SubjectStudent::where('user_id', $user->id)
        ->where('subject_id', $request->subject_id)
        ->where('teacher_id', $request->teacher_id)
        ->where('status', 'pending')
        ->first();

    if ($existing) {
        return response()->json(['message' => 'You have already submitted a request for this subject with this teacher.'], 400);
    }

    SubjectStudent::create([
        'user_id' => $user->id,
        'subject_id' => $request->subject_id,
        'teacher_id' => $request->teacher_id,
        'status' => 'pending',
    ]);

    return response()->json(['message' => 'Request submitted successfully and is pending approval.']);
}




public function getLessonsBySubject($subject_id)
{
    // استخراج هوية الطالب من المستخدم المسجل دخول
    $studentId = auth()->id(); // تأكد أنك مفعل auth middleware في المسار

    // التحقق من حالة الطالب في المادة
    $statusRecord = DB::table('subject_student')
        ->where('user_id', $studentId)
        ->where('subject_id', $subject_id)
        ->first();

    if (!$statusRecord) {
        return response()->json(['message' => 'أنت غير مسجّل في هذه المادة.'], 404);
    }

    switch ($statusRecord->status) {
        case 'accepted':
            $lessons = Lesson::where('subject_id', $subject_id)->get();
            return response()->json($lessons);

        case 'rejected':
            return response()->json(['message' => 'تم رفض طلبك للانضمام إلى هذه المادة.'], 403);

        case 'pending':
            return response()->json(['message' => 'طلبك قيد المراجعة حالياً.'], 202);

        default:
            return response()->json(['message' => 'حالة غير معروفة.'], 400);
    }
}
   


//عرض الطلاب 


public function getStudents()
{
    
    $students = User::where('role_id', 1)->get();

    return response()->json($students);
}


  //عرض طلاب استاذ
public function getStudentsForTeacherSubject()
{
    $teacherId =auth()->id();
    $students = SubjectStudent::with('user')
        ->where('teacher_id', $teacherId)
        ->where('status', 'accepted')
        ->get();

    return response()->json([
        'teacher_id' => $teacherId,
        'students' => $students->map(function ($record) {
            return [
                'student_id' => $record->user_id,
                'student' => $record->user, 
            ];
        })
    ]);
}

//عرض الاختبارات من المفضلة 


public function getTeacherFavoriteTests($teacherId)
{
    $student = auth()->user();

  
    $exists = DB::table('teacher_favorite')
        ->where('teacher_id', $teacherId)
        ->where('student_id', $student->id)
        ->exists();

    if (!$exists) {
        return response()->json(['message' => 'You are not allowed to view this teacher tests.'], 403);
    }

    
    $tests = Test::with('questions.options')
        ->where('user_id', $teacherId)
        ->where('is_favorite', true)
        ->get();

    return response()->json(['test '=> $tests]);
}


// عرض الاساتذة اللي ضافت الطالب عالمفضلة 


public function getFavoriteTeachersForStudent()
{
    $studentId = auth()->id(); 

    
    $teachers = DB::table('teacher_favorite')
        ->join('users', 'teacher_favorite.teacher_id', '=', 'users.id')
        ->where('teacher_favorite.student_id', $studentId)
        ->select('users.id', 'users.name', 'users.email','users.phone') 
        ->get();

    if ($teachers->isEmpty()) {
        return response()->json([
            'message' => 'No favorite teachers found for this student.'
        ], 404);
    }

    return response()->json([
        'teachers' => $teachers
    ]);
}


//عرض التحدي 


public function getChallengeQuestions($challengeId)
{
    $user = auth()->user();

    $challenge = Challenge::with('questions.options')->find($challengeId);

    if (!$challenge) {
        return response()->json(['message' => 'Challenge not found or already expired.'], 404);
    }

    
    if ($user->id === $challenge->teacher_id) {
        return response()->json($challenge->questions);
    }

    $now = Carbon::now();

   
    if ($now->lt(Carbon::parse($challenge->start_time))) {
        return response()->json(['message' => 'Challenge has not started yet.'], 403);
    }

   
    $relation = SubjectStudent::where('user_id', $user->id)
        ->where('teacher_id', $challenge->teacher_id)
        ->where('status', 'accepted')
        ->first();

    if (!$relation) {
        return response()->json(['message' => 'You are not authorized to view this challenge.'], 403);
    }

    return response()->json($challenge->questions);
}



//عرض تحديات طالب

public function getChallengesForstudent()
{
    $studentId = auth()->id();

    if (!$studentId) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    
    $acceptedTeachers = DB::table('subject_student')
        ->where('user_id', $studentId)
        ->where('status', 'accepted')
        ->pluck('teacher_id');

    if ($acceptedTeachers->isEmpty()) {
        return response()->json(['message' => 'No accepted teachers found for this student.'], 404);
    }

    
    $challenges = Challenge::whereIn('teacher_id', $acceptedTeachers)->get();

    if ($challenges->isEmpty()) {
        return response()->json(['message' => 'No challenges found for accepted teachers.'], 404);
    }

    return response()->json($challenges);
}



// الاجابة على التحدي 

public function submitChallengeAnswers(Request $request, $challengeId)
{
    $validated = $request->validate([
        'answers' => 'required|array',
    ]);

    $challenge = Challenge::with('questions.options')->findOrFail($challengeId);

   
    $allChallengeQuestions = $challenge->questions;
    $allQuestionIds = $allChallengeQuestions->pluck('id')->toArray();

    $submittedAnswers = $validated['answers'];

    
    foreach (array_keys($submittedAnswers) as $submittedQuestionId) {
        if (!in_array($submittedQuestionId, $allQuestionIds)) {
            return response()->json([
                'message' => "Question ID $submittedQuestionId does not belong to this challenge."
            ], 400);
        }
    }

    $correctAnswersCount = 0;
    $incorrectAnswersCount = 0;

    foreach ($submittedAnswers as $questionId => $selectedOptionId) {
        $question = $allChallengeQuestions->find($questionId);

        if ($question) {
            $isCorrect = $question->options()
                ->where('id', $selectedOptionId)
                ->value('is_correct');

            if ($isCorrect) {
                $correctAnswersCount++;
            } else {
                $incorrectAnswersCount++;
            }
        }
    }

    
    $challenge->reports()->create([
        'student_id' => Auth::id(),
        'correct_answers_count' => $correctAnswersCount,
        'incorrect_answers_count' => $incorrectAnswersCount,
    ]);

    return response()->json([
        'correct_answers_count' => $correctAnswersCount,
        'incorrect_answers_count' => $incorrectAnswersCount,
    ]);
}


//عرض نقاط الطالب 


public function getStudentPointsByTeacher()
{
    
    $studentId = Auth::id(); 

  
    $pointsRecords = Point::where('student_id', $studentId)
                           ->with('teacher')
                           ->get();

 
    if ($pointsRecords->isEmpty()) {
        return response()->json([
            'message' => 'No points registered for this student.'
        ], 404);
    }

    
    return response()->json([
        'student_id' => $studentId,
        'points_by_teachers' => $pointsRecords
    ]);
}


//اضافة تقييم لاستاذ 



public function rateTeacher(Request $request, $teacherId)
{
    $student = auth()->user();

   
    $request->validate([
        'rating' => 'required|integer|min:1|max:5',
    ]);

   
    $rating = TeacherRating::updateOrCreate(
        [
            'student_id' => $student->id,
            'teacher_id' => $teacherId,
        ],
        [
            'rating' => $request->rating,
        ]
    );

    return response()->json([
        'message' => 'Rating saved successfully.',
        'data'    => $rating,
    ], 200);
}



//عرض مواد الطالب 

public function getAcceptedSubjects()
{
    $student = auth()->user();

    $subjects = SubjectStudent::with(['subject.teacher'])
        ->where('user_id', $student->id)
        ->where('status', 'accepted')
        ->get();

    return response()->json($subjects);
}

}
