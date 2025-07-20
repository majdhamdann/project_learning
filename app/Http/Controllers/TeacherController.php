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
