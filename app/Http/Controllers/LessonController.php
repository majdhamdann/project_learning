<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\Request;
use App\Models\TeacherSubject;

class LessonController extends Controller
{
    public function viewLessonOrPage(Request $request,$subjectId)
    {
        $isRegistered =$request->user()->subjects()->where('subject_id', $subjectId)->exists();
       
        $validated = $request->validate([
          'lesson_id' => 'required|exists:lessons,id', 
          'page_number' => 'nullable|integer', 
        ]);
       if (!$validated['lesson_id']) {
            return response()->json(['message' => 'حقل lesson_id مطلوب.'], 400);
        }
       $lesson = Lesson::findOrFail($validated['lesson_id']);

        $query = Question::where('lesson_id', $lesson->id);

    if ($validated['page_number'] !== null) {
        $query->where('page_number', $validated['page_number']);
    }

     $questions = $query->get();

if ($questions->isEmpty()) {
    return response()->json([
        'message' => 'لا توجد أسئلة متاحة لهذا الدرس أو الصفحة.',
    ], 404);
}

$response = $questions->map(function($question) {
    $correctOption = $question->options()->where('is_correct', true)->first();
    return [
        'question' => $question->question_text,
        'correct_answer' => $correctOption ? $correctOption->option_text : null, 
        'explanation' =>   $question->explanation, 
    ];
});
if (!$isRegistered) {
    return response()->json([
        'messege' =>'you should to register in this subject',
            
    ]);       
}
else{
    return response()->json([
        'lesson' => $lesson->title,
        'question' => $response, 
            
    ]);
}


    }
   
    public function viewAllQuestion($subjectId,Request $request)
{
     $isRegistered =$request->user()->subjects()->where('subject_id', $subjectId)->exists();

     
    $lessons = Lesson::where('subject_id', $subjectId)
        ->with(['questions.options', 'questions.correctOption']) 
        ->get();

    if ($lessons->isEmpty()) {
        return response()->json([
            'message' => 'لا توجد أسئلة متاحة لهذا الموضوع.',
        ], 404);
    }

    $response = $lessons->flatMap(function ($lesson) use ($isRegistered) {
        $questions = $lesson->questions;

        if (!$isRegistered) {
            $questions = $questions->take(20);
        }

        return $questions->map(function ($question) use ($lesson) {
            return [
                'lesson' => $lesson->title,
                'page_number' => $question->page_number,
                'question' => $question->question_text,
                'correct_answer' => $question->correctOption ? $question->correctOption->option_text : null,
                'explanation' => $question->explanation,
            ];
        });
    });

    return response()->json([
        'questions' => $response,
    ]);
}

    public function getLessons($subjectId){
       
      
            $lessons = Lesson::where('subject_id', $subjectId)
            ->get(['id','title']);
            return response()->json([
                'questions' => $lessons 
            ]);
    }
    
    public function addlesson(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required', 
            'subject_id' => 'required|exists:subjects,id',
            'video_path' => 'nullable|string|max:500',
            'summary_path' => 'nullable|string|max:500',
        ]);
    
        $teacherId = auth()->id(); 
    
        
        $teacherSubject = TeacherSubject::where('teacher_id', $teacherId)
            ->where('subject_id', $validated['subject_id'])
            ->where('status', 'accepted')
            ->first();
    
        if (!$teacherSubject) {
            return response()->json([
                'error' => 'You are not authorized to add lessons to this subject.'
            ], 403);
        }
    
       
        $lesson = Lesson::create([
            'title' => $validated['title'],
            'subject_id' => $validated['subject_id'],
            'video_path' => $validated['video_path'] ?? null,
            'summary_path' => $validated['summary_path'] ?? null,
            'teacher_id' => $teacherId, 
        ]);
    
        return response()->json([
            'lesson' => $lesson,
            'message' => 'Lesson added successfully.'
        ]);
    }
    
    public function deleteLesson($id){
      $lesson=Lesson::where('id',$id)->with('questions.options')->delete();
      if($lesson){
        return response()->json([
            'messege'=>'تم حذف الدرس بنجاح'
        ]);
      }
      else{
        return response()->json([
            'messege'=>'الدرس غير موجود'
        ]);
      }
    }

    

    public function getLessonsForTeacherSubject(Request $request,$teacherId)
    {
       
    
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
        ]);
    
        $lessons = Lesson::where('teacher_id', $teacherId)
            ->where('subject_id', $request->subject_id)
            ->get();
    
        if ($lessons->isEmpty()) {
            return response()->json([
                'message' => 'No lessons found for this subject by the current teacher.'
            ], 404);
        }
    
        return response()->json([
            'lessons' => $lessons
        ]);
    }
    

   

}
