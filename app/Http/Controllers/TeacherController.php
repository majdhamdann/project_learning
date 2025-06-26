<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Teacher;
use App\models\Student;

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



}
