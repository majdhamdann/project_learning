<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class StudentController extends Controller
{

    public function addStudentsToSubject(Request $request, $subjectId)
{
    $validated = $request->validate([
        'student_ids' => 'required|array',
        'student_ids.*' => 'exists:users,id'
    ]);

    $subject = Subject::findOrFail($subjectId);
    $subject->students()->syncWithoutDetaching($validated['student_ids']);

    return response()->json([
        'message' => 'تم إضافة الطلاب إلى المادة بنجاح'
    ]);
}
    
}
