<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\Student;
use App\Models\User;
use App\Models\SubjectStudent;
use App\Models\TeacherSubject;
use App\Models\Teacher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;






class AdminController extends Controller
{
    public function listSubjectRequests()
{
    $requests = SubjectStudent::with('user', 'subject')->where('status', 'pending')->get();
    return response()->json($requests);
}



public function handleSubjectRequest(Request $request, $id)
{
    $SubjectStudent = SubjectStudent::findOrFail($id);

    if ($request->status == 'accept') {
        $SubjectStudent->status = 'accepted'; 
    } elseif ($request->status == 'reject') {
        $SubjectStudent->status = 'rejected';
    } else {
        return response()->json(['message' => 'الإجراء غير صالح.'], 400);
    }

    $SubjectStudent->save();

    return response()->json(['message' => 'تم تحديث حالة الطلب.']);
}



//عرض طلبات انشاء حساب 


public function registerRequests()
{
    $users = User::where('status', 'pending')->get();

    return response()->json([
        'pending_requests' => $users,
    ]);
}




//قبول ورفض حالات انشاء حساب 

public function updateRequestStatus(Request $request, $id)
{
    $validated = $request->validate([
        'status' => 'required|in:accept,reject', // نستقبل accept أو reject فقط
    ]);

    $user = User::findOrFail($id);

    // تحويل القيمة
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



//عرض الملفات الشخصية 

public function getUser()
{
    $students = User::where('role_id', 1)->get();
    $teachers = User::where('role_id', 2)->get();

    return response()->json([
        'students' => $students,
        'teachers' => $teachers,
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

    // تحويل القيمة
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
}
