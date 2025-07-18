<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubjectController extends Controller
{public function addSubject(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric',
            'phone' => 'required|string',
        ]);
    
        // ابحث عن المستخدم بواسطة رقم الهاتف
        $teacher = User::where('phone', $validated['phone'])->first();
    
        // تحقق إن كان موجوداً
        if (!$teacher) {
            return response()->json([
                'message' => 'لا يوجد مستخدم بهذا الرقم.',
            ], 404);
        }
    
        // تحقق إن كان أستاذ (role_id = 2)
        if ($teacher->role_id != 2) {
            return response()->json([
                'message' => 'الرقم المدخل لا يعود لأستاذ.',
            ], 403);
        }
    
        // إنشاء المادة وربطها بالأستاذ
        $subject = Subject::create([
            'title' => $validated['title'],
            'price' => $validated['price'],
            'teacher_id' => $teacher->id,
        ]);
    
        return response()->json([
            'message' => 'تم إنشاء المادة بنجاح.',
            'subject' => $subject,
        ], 201);
    }
    
    public function index()
    {
        $subjects = Subject::with('teacher')->get(); 
        return response()->json($subjects);
    }
    
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric',
            'phone' => 'sometimes|required|string',
        ]);
    
        $subject = Subject::findOrFail($id);
    
        $updateData = [];
    
        if (array_key_exists('title', $validated)) {
            $updateData['title'] = $validated['title'];
        }
    
        if (array_key_exists('price', $validated)) {
            $updateData['price'] = $validated['price'];
        }
    
        if (array_key_exists('phone', $validated)) {
            $teacher = User::where('phone', $validated['phone'])->first();
    
            if (!$teacher) {
                return response()->json([
                    'message' => 'لا يوجد مستخدم بهذا الرقم.',
                ], 404);
            }
    
            if ($teacher->role_id != 2) {
                return response()->json([
                    'message' => 'الرقم المدخل لا يعود لأستاذ.',
                ], 403);
            }
    
            $updateData['teacher_id'] = $teacher->id;
        }
    
        // تحديث بيانات المادة
        $subject->update($updateData);
    
        return response()->json([
            'message' => 'تم تحديث المادة بنجاح.',
            'subject' => $subject->fresh(), // تحديث العرض
        ]);
    }
    
    
public function destroy($id)
{
    if(Subject::where('id',$id)->exists()){
        $subject = Subject::findOrFail($id); 

        $subject->delete();
    
        return response()->json([
            'message' => 'Subject deleted successfully',
        ]);
    }
    return response()->json([
        'message' => 'Subject donot found',
    ]);
    
}
public function show($id)
{
    if(Subject::where('id',$id)->exists()){
        $subject = Subject::where('id',$id)->get(['title','price','teacher_id']); 
    
        return response()->json([
            'subject'=> $subject
        ]);
    }
    return response()->json([
        'message' => 'Subject donot found',
    ]);
    
}
public function addStudentsToSubject(Request $request, $subjectId)
{
    $validated = $request->validate([
        'student_ids' => 'required|array',
        'student_ids.*' => 'exists:users,id',
        
    ]);
    
    $subject = Subject::findOrFail($subjectId);
    $subject->students()->syncWithoutDetaching($validated['student_ids']);

    return response()->json([
        'message' => 'تم إضافة الطلاب إلى المادة بنجاح'
    ]);
}

public function removeStudentFromSubject(Request $request, $subjectId)
{
    $validated = $request->validate([
        'student_id' => 'required|exists:users,id',
    ]);
    $subject = Subject::findOrFail($subjectId);

    $isEnrolled = $subject->students()->where('student_id', $validated['student_id'])->exists();
    if (!$isEnrolled) {
        return response()->json([
            'message' => 'هذا الطالب غير مسجل في هذه المادة.',
        ], 404);
    }
    $subject->students()->detach($validated['student_id']);

    return response()->json([
        'message' => 'تم إزالة الطالب من المادة بنجاح.',
    ]);
}





    
}
