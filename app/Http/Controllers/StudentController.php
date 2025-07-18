<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Student;
use App\Models\SubjectRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SubjectStudent;
use Illuminate\Support\Facades\DB;
use App\Models\Lesson;


class StudentController extends Controller
{

    public function addStudentsToSubject(Request $request, $subjectId)
{
    $validated = $request->validate([
        'student_ids' => 'required|array',
        'student_ids.*' => 'exists:users,id'
    ]);

    $subject = Subject::findOrFail($subjectId);
    $subject->user()->syncWithoutDetaching($validated['student_ids']);

    return response()->json([
        'message' => 'تم إضافة الطلاب إلى المادة بنجاح'
    ]);
}
public function subscribe(Request $request)
{
    $request->validate([
        'student_id' => 'required|exists:students,id',
        'subject_id' => 'required|exists:subjects,id',
    ]);

    $student = Student::find($request->student_id);
    $subject_id = $request->subject_id;

    // تحقق من وجود اشتراك سابق
    if ($student->subjects()->where('subject_id', $subject_id)->exists()) {
        return response()->json([
            'message' => 'تم تقديم طلب مسبق لهذه المادة.',
        ], 409); // 409 Conflict
    }

    // إنشاء الطلب
    $student->subjects()->attach($subject_id, ['status' => 'pending']);

    return response()->json([
        'message' => 'تم إرسال طلب الاشتراك بنجاح.',
    ], 200);
}
    
public function requestSubject(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'غير مصرح. يرجى تسجيل الدخول أولاً.'], 401);
    }

    $request->validate([
        'subject_id' => 'required|exists:subjects,id',
    ]);

    // منع التكرار
    $existing = SubjectStudent::where('user_id', $user->id)
        ->where('subject_id', $request->subject_id)
        ->where('status', 'pending')
        ->first();

    if ($existing) {
        return response()->json(['message' => 'لقد قدمت طلب مسبقاً لهذه المادة.'], 400);
    }

    SubjectStudent::create([
        'user_id' => $user->id,
        'subject_id' => $request->subject_id,
        'status' => 'pending',
    ]);

    return response()->json(['message' => 'تم إرسال الطلب بنجاح، بانتظار الموافقة.']);
}

//عرض دروس مادة 


public function getLessonsBySubject($subject_id)
{
    // استخراج هوية الطالب من المستخدم المسجل دخول
    $studentId = auth()->id(); // تأكد أنك مفعل auth middleware في المسار

    // التحقق من حالة الطالب في المادة
    $statusRecord = DB::table('subject_student')
        ->where('user_id', $studentId)
        ->where('subject_id', $subject_id)
        ->first();

    if (!$statusRecord) {
        return response()->json(['message' => 'أنت غير مسجّل في هذه المادة.'], 404);
    }

    switch ($statusRecord->status) {
        case 'accepted':
            $lessons = Lesson::where('subject_id', $subject_id)->get();
            return response()->json($lessons);

        case 'rejected':
            return response()->json(['message' => 'تم رفض طلبك للانضمام إلى هذه المادة.'], 403);

        case 'pending':
            return response()->json(['message' => 'طلبك قيد المراجعة حالياً.'], 202);

        default:
            return response()->json(['message' => 'حالة غير معروفة.'], 400);
    }
}



}
