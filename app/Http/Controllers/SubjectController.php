<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\User;
use App\Models\TeacherSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubjectController extends Controller
{
    
    public function addSubject(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric',
        ]);
    
        $subject = Subject::create([
            'title' => $validated['title'],
            'price' => $validated['price'],
           
        ]);
    
        return response()->json([
            'message' => 'تم إنشاء المادة بنجاح ',
            'subject' => $subject,
        ], 201);
    }
    
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric',
            'teacher_id' => 'sometimes|required|exists:users,id',
        ]);
    
        $subject = Subject::findOrFail($id);
    
        $updateData = [];
    
        if (array_key_exists('title', $validated)) {
            $updateData['title'] = $validated['title'];
        }
    
        if (array_key_exists('price', $validated)) {
            $updateData['price'] = $validated['price'];
        }
    
        if (array_key_exists('teacher_id', $validated)) {
            $teacher = User::find($validated['teacher_id']);
    
            if ($teacher->role_id != 2) {
                return response()->json([
                    'message' => 'المستخدم المحدد ليس أستاذًا.',
                ], 403);
            }
    
            $updateData['teacher_id'] = $teacher->id;
        }
    
        // تحديث بيانات المادة
        $subject->update($updateData);
    
        return response()->json([
            'message' => 'تم تحديث المادة بنجاح.',
            'subject' => $subject->fresh(),
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

    $isStudent = User::where('id', $validated['student_id'])->where('role_id', 1)->exists();
    
    if (!$isStudent) {
        return response()->json([
            'message' => 'المستخدم المحدد ليس طالباً.',
        ], 404);
    }

    $isEnrolled = $subject->students()->where('user_id', $validated['student_id'])->exists();
    
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



public function getSubjects()
{
    $subjects = Subject::all();

    return response()->json([
        'subjects' => $subjects,
    ]);
}


//عرض  الاساتذة لمادة 
public function getTeachersForSubject($subjectId)
{
    $subject = Subject::with(['teachers' => function ($query) {
        $query->where('role_id', 2); // فقط المدرّسين
    }])->findOrFail($subjectId);

    $teachers = $subject->teachers->map(function ($teacher) {
        return [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'email' => $teacher->email,
            'teacher_image' => $teacher->pivot->teacher_image,
            'teaching_start_date' => $teacher->pivot->teaching_start_date,
        ];
    });

    return response()->json($teachers);
}





    
}
