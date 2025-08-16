<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Teacher;
use App\models\Student;
use App\Models\Subject;
use App\Models\Test;
use App\Models\Challenge;
use Illuminate\Support\Facades\Auth;

use App\Models\TeacherProfile;
use App\Models\Question;
use App\Models\Lesson;
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

    
    $teacher->subjectRequests()->attach($subject->id, [
        'status' => 'pending',
        
    ]);

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





// اضافة بيانات الاستاذ الاضافية 

public function storeTeacherDetails(Request $request)
{
    $request->validate([
        'bio' => 'nullable|string',
        'specialization' => 'nullable|string',
        'experience_years' => 'nullable|integer',
        'city' => 'nullable|string',
        'province' => 'nullable|string',
        'teaching_start_date' => 'nullable|date',
        'age' => 'nullable|string'
    ]);

    $teacherId = auth()->id(); 

    $data = $request->all();

    $teacherInfo = TeacherProfile::updateOrCreate(
        ['teacher_id' => $teacherId],
        $data
    );

    return response()->json([
        'message' => 'Teacher info saved successfully',
        'teacher_info' => $teacherInfo,
    ]);
}


//عرض المعومات الاضافية للاستاذ
public function getTeacherProfile($teacherId)
{
   

    $profile = TeacherProfile::where('teacher_id', $teacherId)->first();

    if (!$profile) {
        return response()->json(['message' => 'Teacher profile not found'], 404);
    }

    return response()->json($profile);

}



//اضافة اختبار للمفضلة 


public function addFavoriteTest($testId)
{
    

    $teacher = Auth::user();
   

 
    $test = Test::find($testId);

   
    if ($test->user_id !== $teacher->id) {
        return response()->json(['message' => 'You can only add tests you created to favorites.'], 403);
    }

   
    if (!$teacher->favoriteTests()->where('test_id', $testId)->exists()) {
        $teacher->favoriteTests()->attach($testId);
        return response()->json(['message' => 'Test added to favorites successfully.']);
    }

    return response()->json(['message' => 'Test is already in favorites.']);
}

// حذف اختبار من المفضلة 

public function removeFavoriteTest($testId)
{
    $teacherId = auth()->id(); 

    
    $deleted = DB::table('tests_teacher_favorite')
        ->where('teacher_id', $teacherId)
        ->where('test_id', $testId)
        ->delete();

    if ($deleted) {
        return response()->json(['message' => 'Test removed from favorites successfully.']);
    } else {
        return response()->json(['message' => 'Favorite test not found or not authorized.'], 404);
    }
}




//عرض كل اسئلة الاستاذ
public function getAllQuestionsByTeacher()
{
    $teacherId = auth()->id(); 

    
    $lessonIds = Lesson::where('teacher_id', $teacherId)->pluck('id');

    if ($lessonIds->isEmpty()) {
        return response()->json(['message' => 'No lessons found for this teacher.'], 404);
    }

    
    $questions = Question::with('options')
        ->whereIn('lesson_id', $lessonIds)
        ->get();

    if ($questions->isEmpty()) {
        return response()->json(['message' => 'No questions found for this teacher.'], 404);
    }

    return response()->json($questions);
}

//انشاء تحدي 

public function createChallenge(Request $request)
{
    $teacherId = auth()->id();

    if (!$teacherId) {
        return response()->json(['message' => 'User not authenticated'], 401);
    }

    $validated = $request->validate([
        'title'   => 'required|string|max:255',
        'start_time'       => 'required|date|after_or_equal:now',
        'duration_minutes' => 'required|integer|min:1',
        'question_ids'     => 'nullable|array',
        'question_ids.*'   => 'exists:questions,id',
    ]);

        $challenge = Challenge::create([
        'teacher_id'       => $teacherId,
        'title'   => $validated['title'],
        'start_time'       => $validated['start_time'],
        'duration_minutes' => $validated['duration_minutes'],
    ]);

   
    if (!empty($validated['question_ids'])) {
        $challenge->questions()->attach($validated['question_ids']);
    }

    return response()->json([
        'message'   => 'Challenge created successfully.',
        'challenge' => $challenge
    ], 201);
}

//اضافة سؤال للتحدي 



public function addQuestionToChallenge(Request $request, $challengeId)
{
    $request->validate([
        'question_id' => 'required|exists:questions,id'
    ]);

  
    $challenge = Challenge::find($challengeId);
    if (!$challenge) {
        return response()->json(['message' => 'Challenge not found'], 404);
    }

    
    if ($challenge->teacher_id !== auth()->id()) {
        return response()->json(['message' => 'You are not authorized to modify this challenge'], 403);
    }

    
    if ($challenge->questions()->where('question_id', $request->question_id)->exists()) {
        return response()->json(['message' => 'Question already exists in this challenge'], 409);
    }

   
    $challenge->questions()->attach($request->question_id);

    return response()->json([
        'message' => 'Question added to challenge successfully',
        'challenge' => $challenge->load('questions')
    ]);
}


}






