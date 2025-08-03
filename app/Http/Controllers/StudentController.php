<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Student;
use App\Models\User;
use App\Models\SubjectRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SubjectStudent;
use Illuminate\Support\Facades\DB;
use App\Models\Lesson;


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

    // تحقق من وجود اشتراك سابق
    if ($student->subjects()->where('subject_id', $subject_id)->exists()) {
        return response()->json([
            'message' => 'تم تقديم طلب مسبق لهذه المادة.',
        ], 409); // 409 Conflict
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


//عرض دروس مادة 


public function getLessonsBySubject($subject_id)
{
    
    $studentId = auth()->id(); 

    
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

public function getTeacherFavoriteTests( $teacherId)
{
    $studentId = auth()->id(); 

   
    $isFavorite = DB::table('teacher_favorite')
        ->where('teacher_id', $teacherId)
        ->where('student_id', $studentId)
        ->exists();

    if (!$isFavorite) {
        return response()->json(['message' => 'You are not in the teacher favorites list.'], 403);
    }

    
    $tests = DB::table('tests_teacher_favorite')
        ->join('tests', 'tests.id', '=', 'tests_teacher_favorite.test_id')
        ->where('tests_teacher_favorite.teacher_id', $teacherId)
        ->select('tests.*')
        ->get();

    return response()->json([
        'teacher_id' => $teacherId,
        'tests' => $tests
    ]);
}



}
