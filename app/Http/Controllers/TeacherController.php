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



// عرض المفضلة لاستاذ

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



//اضافة طالب للمفضلة 

public function addFavoriteStudent($studentId)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['error' => 'يجب تسجيل الدخول أولاً'], 401);
    }

    if ($user->role_id != 2) {
        return response()->json(['error' => 'غير مسموح، فقط الأساتذة يمكنهم إضافة طلاب للمفضلة'], 403);
    }

    
    $student = User::where('id', $studentId)->where('role_id', 1)->first();
    if (!$student) {
        return response()->json(['error' => 'الطالب غير موجود أو ليس طالباً '], 404);
    }

   
    $exists = DB::table('teacher_favorite')
        ->where('teacher_id', $user->id)
        ->where('student_id', $studentId)
        ->exists();

    if ($exists) {
        return response()->json(['message' => 'الطالب مضاف مسبقاً في المفضلة']);
    }

   
    DB::table('teacher_favorite')->insert([
        'teacher_id' => $user->id,
        'student_id' => $studentId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json(['message' => 'تمت إضافة الطالب إلى المفضلة بنجاح']);
}
   

//حذف طالب من المفضلة 

public function removeFavoriteStudent($studentId)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['error' => 'يجب تسجيل الدخول أولاً'], 401);
    }

    if ($user->role_id != 2) {
        return response()->json(['error' => 'غير مسموح، فقط الأساتذة يمكنهم إدارة المفضلة'], 403);
    }

    // تنفيذ الحذف من جدول teacher_favorite
    $deleted = DB::table('teacher_favorite')
        ->where('teacher_id', $user->id)
        ->where('student_id', $studentId)
        ->delete();

    if ($deleted) {
        return response()->json(['message' => 'تم حذف الطالب من المفضلة بنجاح']);
    } else {
        return response()->json(['message' => 'الطالب غير موجود في المفضلة'], 404);
    }
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
