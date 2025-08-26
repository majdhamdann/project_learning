<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Notifications\CommentNotification  ;

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
            'summary_path' => $validated['summary_path'] ?? null,
            'teacher_id' => $teacherId, 
        ]);
    
        return response()->json([
            'lesson' => $lesson,
            'message' => 'Lesson added successfully.'
        ]);
    }
    public function deleteLesson($id)
    {
        $teacherId = auth()->id(); 
    
        
        $lesson = Lesson::find($id);
    
        
        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson not found.'
            ], 404);
        }
    
        
        if ($lesson->teacher_id !== $teacherId) {
            return response()->json([
                'error' => 'You are not authorized to delete this lesson.'
            ], 403);
        }
    
        
        $lesson->delete();
    
        return response()->json([
            'message' => 'Lesson deleted successfully.'
        ]);
    }
    

    
    public function getLessonsForTeacherSubject(Request $request, $teacherId)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
        ]);
    
        $userId = auth()->id();
        $user = User::find($userId);
    
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }
    
       
        if ($user->role_id != 2) {
            
            $isAccepted = DB::table('subject_student')
                ->where('user_id', $userId)
                ->where('teacher_id', $teacherId)
                ->where('subject_id', $request->subject_id)
                ->where('status', 'accepted')
                ->exists();
    
            if (!$isAccepted) {
                return response()->json([
                    'message' => 'You are not authorized to view lessons for this subject.'
                ], 403);
            }
        }
    
       
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
    
    

   

//اضافة فيديو لدرس  



public function uploadLessonVideo(Request $request, $lessonId)
{
    $request->validate([
        'video' => 'required|mimes:mp4,mov,avi,wmv|max:124000', 
    ], [
        'video.required' => 'Video file is required.',
        'video.mimes' => 'Only MP4, MOV, AVI, and WMV formats are allowed.',
        'video.max' => 'Video size must not exceed 100MB.',
    ]);

  
    $lesson = Lesson::findOrFail($lessonId);

    if ($request->hasFile('video')) {
        $file = $request->file('video');
        $fileName = time() . '_' . $file->getClientOriginalName();

       
        $file->move(public_path('lessons_video'), $fileName);

      
        $lesson->video_path = url('lessons_video/' . $fileName);
        $lesson->save();

        return response()->json([
            'message' => 'Video uploaded and saved successfully.',
            'video_url' => $lesson->video_path,
        ]);
    }

    return response()->json(['message' => 'No video file found.'], 400);
}



// اضافة ملخص لدرس 



public function uploadLessonSummary(Request $request, $lessonId)
{
    $request->validate([
        'summary' => 'required|mimes:pdf,doc,docx,txt|max:10240', 
    ], [
        'summary.required' => 'Summary file is required.',
        'summary.mimes' => 'Only PDF, DOC, DOCX and TXT files are allowed.',
        'summary.max' => 'Summary size must not exceed 10MB.',
    ]);

    
    $lesson = Lesson::findOrFail($lessonId);

    if ($request->hasFile('summary')) {
        $file = $request->file('summary');
        $fileName = time() . '_' . $file->getClientOriginalName();

        
        $file->move(public_path('lessons_summary'), $fileName);

       
        $lesson->summary_path = url('lessons_summary/' . $fileName);
        $lesson->save();

        return response()->json([
            'message' => 'Summary uploaded and saved successfully.',
            'summary_url' => $lesson->summary_path,
        ]);
    }

    return response()->json(['message' => 'No summary file found.'], 400);
}




// اضافة تعليق على فيديو 


// public function addComment1(Request $request)
// {
//     $validated = $request->validate([
//         'lesson_id' => 'required|exists:lessons,id',
//         'content' => 'required|string',
//         'parent_id' => 'nullable|exists:comments,id', 
//     ]);

//     $userId = auth()->id();

//     $comment = Comment::create([
//         'lesson_id' => $validated['lesson_id'],
//         'user_id' => $userId,
//         'content' => $validated['content'],
//         'parent_id' => $validated['parent_id'] ?? null,
//     ]);

//     return response()->json([
//         'message' => 'Comment added successfully',
//         'comment' => $comment
//     ], 201);
// }
public function addComment(Request $request)
{
    $validated = $request->validate([
        'lesson_id' => 'required|exists:lessons,id',
        'content'   => 'required|string',
        'parent_id' => 'nullable|exists:comments,id',
    ]);

    $user = auth()->user();
    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $comment = Comment::create([
        'lesson_id' => $validated['lesson_id'],
        'user_id'   => $user->id,
        'content'   => $validated['content'],
        'parent_id' => $validated['parent_id'] ?? null,
    ]);

    $lesson = Lesson::with('subject')->findOrFail($validated['lesson_id']);
    
    if ($lesson->subject) {
        $subject = $lesson->subject;
        
        $teacher = Teacher::find($subject->teacher_id);
        if ($teacher && $teacher->user_id !== $user->id) {
            User::find($teacher->user_id)?->notify(new CommentNotification(
                $user->name,
                $lesson->title,
                $comment->content
            ));
        }

        $studentUserIds = DB::table('subject_student')
            ->where('subject_id', $subject->id)
            ->where('status', 'accepted')
            ->pluck('user_id'); 

        $studentUsers = User::whereIn('id', $studentUserIds)
            ->where('id', '!=', $user->id)
            ->get();

        foreach ($studentUsers as $studentUser) {
            $studentUser->notify(new CommentNotification(
                $user->name,
                $lesson->title,
                $comment->content
            ));
        }
    }

    return response()->json([
        'message' => 'Comment added successfully',
        'comment' => $comment
    ], 201);
}



//عرض التعليقات 

public function getLessonComments($lessonId)
{
    $comments = Comment::where('lesson_id', $lessonId)
        ->whereNull('parent_id') 
        ->with('user') 
        ->get();

    return response()->json($comments);
}


// عرض الردود على تعليق


public function getCommentReplies($commentId)
{
    $replies = Comment::where('parent_id', $commentId)
        ->with('user') 
        ->get();

    return response()->json($replies);
}



}
