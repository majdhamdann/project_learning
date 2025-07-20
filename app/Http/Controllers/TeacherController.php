<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Teacher;
use App\models\Student;
use App\Models\Subject;
use App\Models\TeacherSubject;
use Illuminate\Support\Facades\DB;



class TeacherController extends Controller
{
    public function getTeachers()
{
    
    $teachers = User::where('role_id', 2)->get();

    return response()->json($teachers);
}


public function getMyFavoriteStudents()
{
    $teacherId = auth()->id();

    $studentIds = DB::table('teacher_favorite')
        ->where('teacher_id', $teacherId)
        ->pluck('student_id');

    $students = User::whereIn('id', $studentIds)->where('role_id', 1)->get();

    return response()->json([
        'students' => $students
    ]);
}

public function addFavoriteStudent($studentId)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['error' => 'يجب تسجيل الدخول أولاً'], 401);
    }

    if ($user->role_id != 2) {
        return response()->json(['error' => 'غير مسموح، فقط الأساتذة يمكنهم إضافة طلاب للمفضلة'], 403);
    }

    // تحقق أن الطالب موجود و role_id = 1
    $student = User::where('id', $studentId)->where('role_id', 1)->first();
    if (!$student) {
        return response()->json(['error' => 'الطالب غير موجود أو ليس طالباً '], 404);
    }

    // تحقق إذا الطالب موجود بالفعل في المفضلة
    $exists = DB::table('teacher_favorite')
        ->where('teacher_id', $user->id)
        ->where('student_id', $studentId)
        ->exists();

    if ($exists) {
        return response()->json(['message' => 'الطالب مضاف مسبقاً في المفضلة']);
    }

    // أضف الطالب إلى المفضلة
    DB::table('teacher_favorite')->insert([
        'teacher_id' => $user->id,
        'student_id' => $studentId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json(['message' => 'تمت إضافة الطالب إلى المفضلة بنجاح']);
}




//تقديم طلب الانضمام لمادة 


public function requestToJoinSubject(Request $request)
{
    $validated = $request->validate([
        'subject_id' => 'required|exists:subjects,id',
    ]);

    
    $teacher = Teacher::find(auth()->id());

    
    if (!$teacher || $teacher->role_id != 2) {
        return response()->json(['message' => 'هذا المستخدم ليس أستاذاً.'], 403);
    }

  
    $subject = Subject::findOrFail($validated['subject_id']);

 
    if (
        $teacher->subjectRequests()
            ->where('subject_id', $subject->id)
            ->exists()
    ) {
        return response()->json(['message' => 'تم تقديم طلب سابق لهذه المادة.'], 400);
    }

  
    $teacher->subjectRequests()->attach($subject->id, ['status' => 'pending']);

    return response()->json([
        'message' => 'تم إرسال طلب الاشتراك بنجاح.',
    ]);
}




//عرض طلبات الاستاذ


public function getTeacherRequests()
{
    $teacherId = auth()->id();

   
    $requests = TeacherSubject::with('subject')
        ->where('teacher_id', $teacherId)
        ->get();

    return response()->json([
        'requests' => $requests
    ]);
}





}
