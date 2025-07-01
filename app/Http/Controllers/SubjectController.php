<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubjectController extends Controller
{
    public function addSubject(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric',
            'teacher_id' => 'required|exists:users,id', 
        ]);

        $subject = Subject::create($validated);

        return response()->json([
            'message' => 'Subject created successfully',
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
        'teacher_id' => 'sometimes|required|exists:teachers,id',
    ]);

    $subject = Subject::findOrFail($id); 

    $subject->update($validated);

    return response()->json([
        'message' => 'Subject updated successfully',
        'subject' => $subject,
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
