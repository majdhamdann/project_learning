<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\Student;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Comment;
use App\Models\SubjectStudent;
use App\Models\TeacherSubject;
use App\Models\Teacher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Notifications\SubscriptionRequestStatusNotification;





class AdminController extends Controller
{

    // عرض طلبات الطلاب 

    public function listSubjectRequests()
    {
        $teacher = auth()->user();
    
        if (!$teacher) {
            return response()->json(['message' => 'Unauthorized. Please log in.'], 401);
        }
    
        $requests = SubjectStudent::with(['user', 'subject'])
            ->where('status', 'pending')
            ->where('teacher_id', $teacher->id)
            ->get();
    
        return response()->json($requests);
    }
    


//قبول ورفض طلبات الاشتراك بمادة

public function handleSubjectRequest(Request $request, $id)
{
    $teacher = auth()->user();

    if (!$teacher) {
        return response()->json(['message' => 'Unauthorized. Please log in.'], 401);
    }

    $subjectStudent = SubjectStudent::findOrFail($id);

   
    if ($subjectStudent->teacher_id !== $teacher->id) {
        return response()->json(['message' => 'You are not authorized to manage this request.'], 403);
    }

    if ($request->status === 'accept') {
        $subjectStudent->status = 'accepted';
    } elseif ($request->status === 'reject') {
        $subjectStudent->status = 'rejected';
    } else {
        return response()->json(['message' => 'Invalid action.'], 400);
    }

    $subjectStudent->save();
    $student = $subjectStudent->user;
    $student->notify(new SubscriptionRequestStatusNotification(
        $subjectStudent->status,
        $subjectStudent->subject->name
    ));

    return response()->json(['message' => 'Request status updated successfully.']);
}



//عرض طلبات انشاء حساب 


public function registerRequests()
{
    $users = User::where('status', 'pending')->get();

    return response()->json([
        'pending_requests' => $users,
    ]);
}






public function updateRequestStatus(Request $request, $id)
{
    $validated = $request->validate([
        'status' => 'required|in:accept,reject', 
    ]);

    $user = User::findOrFail($id);

  
    if ($validated['status'] === 'accept') {
        $user->status = 'accepted';
    } elseif ($validated['status'] === 'reject') {
        $user->status = 'rejected';
    }

    $user->save();

    return response()->json([
        'message' => 'تم تحديث حالة الطلب بنجاح.',
        'user'    => $user,
    ]);
}




public function registerTeacher(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name'     => 'required|string|max:255',
        'phone'    => 'required|unique:users,phone',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|string|min:8',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422);
    }

    $user = User::create([
        'name'     => $request->name,
        'phone'    => $request->phone,
        'email'    => $request->email,
        'password' => Hash::make($request->password),
        'role_id'  => 2,
        
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'User created successfully',
        'user'    => $user,
        'token'   => $token,
    ], 201);
}



//عرض جميع المستخدمين  

public function getUsers()
{
    $admins = User::where('role_id',3)->get();
    $students = User::where('role_id', 1)->get();
    $teachers = User::where('role_id', 2)->get();

    return response()->json([
        'students' => $students,
        'teachers' => $teachers,
        'admins' => $admins,
    ]);
}
  


//عرض مستخدم معين 


public function getUser()
{

$user_id = auth()->id();
$user = User::where('id',$user_id)->get();

return response()->json([

'user' => $user

]);



}


//عرض طلبات الاساتذة للانضمام لمادة 

public function getPendingTeacherSubjectRequests()
{
    $requests = TeacherSubject::with(['teacher', 'subject'])
        ->where('status', 'pending')
        ->latest()
        ->get();

    return response()->json([
        'pending_requests' => $requests
    ]);
}


//قبول ورفض طلبات الاساتذة 



public function handleTeacherSubjectRequest(Request $request,$id)
{
    $validated = $request->validate([
        'status' => 'required|in:accept,reject',
    ]);

    $requestRow = TeacherSubject::findOrFail($id);

   
    $statusMap = [
        'accept' => 'accepted',
        'reject' => 'rejected',
    ];

    $requestRow->status = $statusMap[$validated['status']];
    $requestRow->save();

    return response()->json([
        'message' => 'تم تحديث حالة طلب الاشتراك بنجاح.',
        'request' => $requestRow->load(['teacher', 'subject']),
    ]);
}

//حذف مستخدم 


public function deleteUser($id)
{
    $user = User::find($id);

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    $user->delete();

    return response()->json(['message' => 'User and related data deleted successfully'], 200);
}


// حذف استاذ من مادة 


public function removeTeacherFromSubject(Request $request)
{
    $validated = $request->validate([
        'teacher_id' => 'required|exists:teacher_subject,teacher_id',
        'subject_id' => 'required|exists:teacher_subject,subject_id',
    ]);

    
    TeacherSubject::where('teacher_id', $validated['teacher_id'])
        ->where('subject_id', $validated['subject_id'])
        ->delete();

  
    Lesson::where('teacher_id', $validated['teacher_id'])
        ->where('subject_id', $validated['subject_id'])
        ->delete();

    return response()->json([
        'message' => 'Teacher and their lessons removed from subject successfully'
    ], 200);
}



// عرض مواد استاذ

public function getTeacherSubjects($teacherId)
{
    $teacher = User::with('subjects')->find($teacherId);

    if (!$teacher) {
        return response()->json(['message' => 'Teacher not found'], 404);
    }

    return response()->json([
        'teacher_id' => $teacher->id,
        'subjects' => $teacher->subjects
    ]);
}


 //عرض كامل معلومات الاستاذ


 public function getTeacherDetails($teacherId)
 {
     $teacher = User::findOrFail($teacherId);
 
     
     $teacherProfile = DB::table('teacher_profiles')
         ->where('teacher_id', $teacherId)
         ->first();
 
    
     $subjects = DB::table('subjects')
         ->join('teacher_subject', 'subjects.id', '=', 'teacher_subject.subject_id')
         ->where('teacher_subject.teacher_id', $teacherId)
         ->select('subjects.*')
         ->get();
 
     return response()->json([
         'teacher' => $teacher,
         'profile' => $teacherProfile,
         'subjects' => $subjects
     ]);
 }
 
// عرض تقييمات الاساتذة 


public function getTeachersRatings()
{
    $teachers = User::where('role_id', '2') 
        ->withCount('ratings') 
        ->withAvg('ratings', 'rating') 
        ->get();

    return response()->json($teachers);
}

}
