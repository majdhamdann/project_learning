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

//عرض الاساتذة
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
        return response()->json(['error' => 'You must be logged in first.'], 401);
    }

    if ($user->role_id != 2) {
        return response()->json(['error' => 'Not allowed. Only teachers can add students to favorites'], 403);
    }

    
    $student = User::where('id', $studentId)->where('role_id', 1)->first();
    if (!$student) {
        return response()->json(['error' => 'The student does not exist or is not a student.'], 404);
    }

   
    $exists = DB::table('teacher_favorite')
        ->where('teacher_id', $user->id)
        ->where('student_id', $studentId)
        ->exists();

    if ($exists) {
        return response()->json(['message' => 'The student is already added to favorites']);
    }

   
    DB::table('teacher_favorite')->insert([
        'teacher_id' => $user->id,
        'student_id' => $studentId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json(['message' => 'The student has been successfully added to favorites']);
}
   


//اضافة مجموعة من الطلاب 

public function addFavoriteStudents(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['error' => 'You must be logged in first'], 401);
    }

    if ($user->role_id != 2) {
        return response()->json(['error' => 'Not allowed. Only teachers can add students to favorites'], 403);
    }

   
    $rawInput = $request->input('student_ids'); 

    if (!$rawInput) {
        return response()->json(['error' => 'Please provide student IDs (student_ids).'], 400);
    }

  
    $studentIds = is_array($rawInput) ? $rawInput : explode(',', $rawInput);

   
    $studentIds = array_unique(array_filter(array_map('trim', $studentIds), fn($id) => is_numeric($id)));

    if (empty($studentIds)) {
        return response()->json(['error' => 'No valid student ID was found.'], 400);
    }

   
    $validStudents = User::whereIn('id', $studentIds)
                        ->where('role_id', 1)
                        ->pluck('id')
                        ->toArray();



     if (empty($validStudents)) {
    return response()->json(['error' => 'No valid students were found in the provided list.'], 404);
}

    $existing = DB::table('teacher_favorite')
                    ->where('teacher_id', $user->id)
                    ->whereIn('student_id', $validStudents)
                    ->pluck('student_id')
                    ->toArray();

    $newStudentIds = array_diff($validStudents, $existing);

    $now = now();
    $insertData = array_map(fn($id) => [
        'teacher_id' => $user->id,
        'student_id' => $id,
        'created_at' => $now,
        'updated_at' => $now,
    ], $newStudentIds);

    if (!empty($insertData)) {
        DB::table('teacher_favorite')->insert($insertData);
    }

    return response()->json([
        'message' => 'Students have been successfully added to favorites',
        'added' => array_values($newStudentIds),
        'skipped' => array_values($existing),
    ]);
}





//حذف طالب من المفضلة 

public function removeFavoriteStudent($studentId)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['error' => 'You must be logged in first.'], 401);
    }

    if ($user->role_id != 2) {
        return response()->json(['error' => 'Not allowed. Only teachers can manage favorites'], 403);
    }

   
    $deleted = DB::table('teacher_favorite')
        ->where('teacher_id', $user->id)
        ->where('student_id', $studentId)
        ->delete();

    if ($deleted) {
        return response()->json(['message' => 'The student has been successfully removed from favorites']);
    } else {
        return response()->json(['message' => 'The student is not in the favorites'], 404);
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
        return response()->json(['message' => 'This user is not a teacher'], 403);
    }

  
    $subject = Subject::findOrFail($validated['subject_id']);

 
    if (
        $teacher->subjectRequests()
            ->where('subject_id', $subject->id)
            ->exists()
    ) {
        return response()->json(['message' => 'A previous request has already been submitted for this subject.'], 400);
    }

  
    $teacher->subjectRequests()->attach($subject->id, ['status' => 'pending']);

    return response()->json([
        'message' => 'Subscription request has been sent successfully',
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
