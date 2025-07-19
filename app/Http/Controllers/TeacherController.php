<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Teacher;
use App\models\Student;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;



class TeacherController extends Controller
{
    public function getTeachers()
{
    // جلب كل اليوزرات اللي role_id تبعهم 1 (أساتذة)
    $teachers = User::where('role_id', 2)->get();

    return response()->json($teachers);
}
public function getFavoriteStudents($teacherId)
{
    $teacher = Teacher::where('id', $teacherId)->firstOrFail();

    $favoriteStudents = $teacher->favoriteStudents;

    return response()->json($favoriteStudents);
}


public function addFavoriteStudent($teacher_id, $student_id)
{
    $teacher = Teacher::findOrFail($teacher_id);
    $student = Student::findOrFail($student_id);

    // تأكد إنه الطالب مش مضاف مسبقاً
    if (!$teacher->favoriteStudents()->where('student_id', $student_id)->exists()) {
        $teacher->favoriteStudents()->attach($student_id);
        return response()->json(['message' => 'تم إضافة الطالب إلى المفضلة'], 200);
    }

    return response()->json(['message' => 'الطالب موجود مسبقاً'], 409);
}



//تقديم طلب الانضمام لمادة 


public function requestToJoinSubject(Request $request)
{
    $validated = $request->validate([
        'subject_id' => 'required|exists:subjects,id',
    ]);

    // جلب الأستاذ باستخدام موديل Teacher
    $teacher = Teacher::find(auth()->id());

    // التحقق من أنه فعلاً أستاذ
    if (!$teacher || $teacher->role_id != 2) {
        return response()->json(['message' => 'هذا المستخدم ليس أستاذاً.'], 403);
    }

    // التحقق من المادة
    $subject = Subject::findOrFail($validated['subject_id']);

    // التحقق من وجود طلب سابق
    if (
        $teacher->subjectRequests()
            ->where('subject_id', $subject->id)
            ->exists()
    ) {
        return response()->json(['message' => 'تم تقديم طلب سابق لهذه المادة.'], 400);
    }

    // إنشاء الطلب مع حالة pending
    $teacher->subjectRequests()->attach($subject->id, ['status' => 'pending']);

    return response()->json([
        'message' => 'تم إرسال طلب الاشتراك بنجاح.',
    ]);
}



}
