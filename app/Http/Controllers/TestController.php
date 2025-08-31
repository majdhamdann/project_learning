<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestQuestion;
use App\Models\User;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class TestController extends Controller
{
   /* public function createTest(Request $request)
    {
        $validated = $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
            'questions_count' => 'required|integer|min:1',
        ]);
    
        $questions = Question::where('lesson_id', $validated['lesson_id'])
            ->with('subQuestions.options', 'parentQuestion')
            ->inRandomOrder()
            ->limit($validated['questions_count'])
            ->get();
    
        if ($questions->isEmpty()) {
            return response()->json(['message' => 'No questions available for this lesson'], 404);
        }
    
        if (Auth::check()) {
            $student_id = Auth::id();
        } else {
            return response()->json(['message' => 'User not authenticated'], 401);
        }
    
        $lesson = Lesson::find($validated['lesson_id']);
        $subject_id = $lesson->subject_id;
    
        $test = Test::create([
            'lesson_id' => $validated['lesson_id'],
            'student_id' => $student_id,
            'subject_id' => $subject_id
        ]);
    
        foreach ($questions as $question) {
            $test->questions()->attach($question->id);
            foreach ($question->subQuestions as $subQuestion) {
                $test->questions()->attach($subQuestion->id);
            }
        }
    
        $formattedQuestions = $questions->map(function ($question) {
            return [
                'super_question' => $question->parentQuestion ? $question->parentQuestion->question_text : null,  // نص السؤال الأب
                'id' => $question->id,
                'question_text' => $question->question_text,
                'options' => $question->options->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'text' => $option->text ?? 'No text available',
                    ];
                }),
            ];
        });
    
        return response()->json(['test' => $test, 'questions' => $formattedQuestions->values()], 201);
    }
    
    
*/

// انشاء اختبارات عن طريق الطالب 

public function createTest(Request $request)
{
    $lessonIds = json_decode($request->input('lesson_ids'), true);

    if (!is_array($lessonIds) || empty($lessonIds)) {
        return response()->json(['message' => 'Invalid or empty lesson_ids'], 422);
    }

    $request->merge(['lesson_ids' => $lessonIds]);

    $validated = $request->validate([
        'lesson_ids' => 'required|array|min:1',
        'lesson_ids.*' => 'exists:lessons,id',
        'questions_count' => 'required|integer|min:1',
    ]);

    $questions = Question::whereIn('lesson_id', $validated['lesson_ids'])
        ->with(['options', 'subQuestions.options', 'parentQuestion'])
        ->inRandomOrder()
        ->limit($validated['questions_count'])
        ->get();

    if ($questions->isEmpty()) {
        return response()->json(['message' => 'No questions available for the selected lessons'], 404);
    }

    if (!Auth::check()) {
        return response()->json(['message' => 'User not authenticated'], 401);
    }

    $user_id = Auth::id();

    $firstLesson = Lesson::find($validated['lesson_ids'][0]);
    $subject_id = $firstLesson->subject_id;

    
    $test = Test::create([
        'user_id' => $user_id,
        'subject_id' => $subject_id,
    ]);

  
    $test->lessons()->attach($validated['lesson_ids']);

    // ربط الأسئلة بالاختبار
    foreach ($questions as $question) {
        $test->questions()->attach($question->id);
        foreach ($question->subQuestions as $subQuestion) {
            $test->questions()->attach($subQuestion->id);
        }
    }

   
    $questions->load('options', 'subQuestions.options', 'parentQuestion');

    $test->test_name = 'test_' . $test->id;
    $test->save();

    return response()->json([
        'test' => collect($test)->merge([
            'user_id' => $test->user_id
        ])->except(['student_id']),
        'questions' => $questions,
    ], 201);
}




// انشاء اختبار عن طريق الاستاذ
public function createTestWithQuestions(Request $request)
{
    $questionIds = $request->has('question_ids') ? json_decode($request->input('question_ids'), true) : [];

    if (!is_array($questionIds)) {
        return response()->json(['message' => 'Invalid question_ids format'], 422);
    }

    $request->merge(['question_ids' => $questionIds]);

    $validated = $request->validate([
        'question_ids' => 'nullable|array',
        'question_ids.*' => 'exists:questions,id',
        'is_favorite' => 'sometimes|boolean'
    ]);

    if (!Auth::check()) {
        return response()->json(['message' => 'User not authenticated'], 401);
    }

    $teacherId = Auth::id();

    $subject_id = DB::table('teacher_subject')
        ->where('teacher_id', $teacherId)
        ->value('subject_id');

    $questions = collect();
    if (!empty($validated['question_ids'])) {
        $questions = Question::with(['options', 'subQuestions.options', 'parentQuestion'])
            ->whereIn('id', $validated['question_ids'])
            ->get();
    }

    $test = Test::create([
        'user_id' => $teacherId,
        'subject_id' => $subject_id,
        'is_favorite' => $validated['is_favorite'] ?? true,
    ]);

    foreach ($questions as $question) {
        $test->questions()->attach($question->id);
        foreach ($question->subQuestions as $subQuestion) {
            $test->questions()->attach($subQuestion->id);
        }
    }

    if ($questions->isNotEmpty()) {
        $questions->load('options', 'subQuestions.options', 'parentQuestion');
    }

    $test->test_name = 'test_' . $test->id;
    $test->save();

    return response()->json([
        'test' => collect($test)->merge([
            'user_id' => $test->user_id
        ])->except(['student_id']),
        'questions' => $questions,
    ], 201);
}


    public function getTestResult($testId)
    {
        $test = Test::with('questions')->findOrFail($testId);

        return response()->json([
          
            'result' => [
                'correct_answers_count' => $test->questions()->wherePivot('is_correct', true)->count(),
                'incorrect_answers_count' => $test->questions()->wherePivot('is_correct', false)->count(),
            ]
        ]);
    }

    
/*
    public function generateTest(Request $request,$subjectId)
    {
        $validated = $request->validate([
            'lesson_ids' => 'required|array',
            'question_count' => 'required|integer|min:1',
        ]);

        $lessonIds = $validated['lesson_ids'];
        $questionCount = $validated['question_count'];

        $questions = Question::with('options') 
        ->whereIn('lesson_id', $lessonIds)
        ->inRandomOrder()
        ->take($questionCount)
        ->get();
        if ($questions->count() < $questionCount) {
            return response()->json([
                'message' => 'Not enough questions available for the requested count'
            ], 400);
        }
        $test = Test::create([
            'student_id' => auth()->user()->id, 
            'subject_id'=>$subjectId,
            'lesson_id' => $lessonIds[0],
        ]);

        foreach ($questions as $question) {
            TestQuestion::create([
                'test_id' => $test->id,
                'question_id' => $question->id,
            ]);
        }

        $testLink = URL::temporarySignedRoute(
            'test.show', 
            now()->addHours(48),
            ['test' => $test->id] 
        );

        return response()->json([
            'message' => 'Test created successfully',
            'test_link' => $testLink, 
            'test_id' => $test->id,
            'questions' => $questions->pluck('id', 'question_text'), 
        ], 201);
    }
*/




    public function exportTestQuestionsToWord(Request $request)
    {
        $validated = $request->validate([
            'test_id' => 'required|exists:tests,id',
        ]);
        $testId=$request->test_id;
        $test = Test::with('questions.options')->findOrFail($testId);
    
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section = $phpWord->addSection([
            'rtl' => true 
        ]);
        $section->addTitle('اسم الاختبار ' . $request->input('test_name'), 1);
        $section->addText('المعلم: ' . $request->input('teacher_name'));
        $section->addText('المادة: ' . $request->input('subject_name'));
        $section->addText('الصف: ' . $request->input('class_name'));
        $section->addText('المدرسة: ' . $request->input('school_name'));
        $section->addText('الدرجة: ' . $request->input('grade'));
        $section->addTextBreak(1);
    
        $questionNumber = 1;
        foreach ($test->questions as $question) {
            $section->addTitle($questionNumber . '. ' . $question->question_text, 2);
            foreach ($question->options as $option) {
                $section->addText('- ' . $option->option_text);
            }
            $questionNumber++;
        }
    
        $section->addTextBreak(2);
        $section->addText('انتهاء الاختبار', ['bold' => true, 'size' => 12]);
    
        $filePath = public_path('exports/test_questions_' . $testId . '.docx');
        $phpWord->save($filePath, 'Word2007');
    
        $downloadUrl = url('exports/test_questions_' . $testId . '.docx');
    
        return response()->json([
            'status' => 'success',
            'message' => 'The test questions have been exported successfully.',
            'download_url' => $downloadUrl
        ]);
    }
    
    public function submitAnswers(Request $request, $testId)
    { 
        $validated = $request->validate([
            'answers' => 'required|array',
        ]);
    
        $test = Test::with('questions.options')->findOrFail($testId);
    
        
        $allTestQuestions = $test->questions;
        $allQuestionIds = $allTestQuestions->pluck('id')->toArray();
    
        $submittedAnswers = $validated['answers'];
    
      
        foreach (array_keys($submittedAnswers) as $submittedQuestionId) {
            if (!in_array($submittedQuestionId, $allQuestionIds)) {
                return response()->json([
                    'message' => "السؤال رقم $submittedQuestionId غير موجود في هذا الاختبار."
                ], 400);
            }
        }
    
       
        $answeredQuestionIds = [];
    
        foreach ($submittedAnswers as $questionId => $selectedOptionId) {
            $question = $allTestQuestions->find($questionId);
    
            if ($question) {
                $answeredQuestionIds[] = $questionId;
    
                $isCorrect = $question->options()
                    ->where('id', $selectedOptionId)
                    ->value('is_correct');
    
                $isCorrect = $isCorrect !== null ? $isCorrect : false;
    
                $test->questions()->updateExistingPivot($questionId, [
                    'selected_option_id' => $selectedOptionId,
                    'is_correct' => $isCorrect,
                ]);
            }
        }
    
       
        $unansweredQuestionIds = array_diff($allQuestionIds, $answeredQuestionIds);
    
        foreach ($unansweredQuestionIds as $unansweredId) {
            $test->questions()->updateExistingPivot($unansweredId, [
                'selected_option_id' => null,
                'is_correct' => false,
            ]);
        }
    
      
        $pivotData = $test->questions()->withPivot('is_correct')->get();
        $correctAnswersCount = $pivotData->where('pivot.is_correct', true)->count();
        $incorrectAnswersCount = $pivotData->where('pivot.is_correct', false)->count();
    
      
        $test->report()->create([
            'student_id' => Auth::id(),
            'correct_answers_count' => $correctAnswersCount,
            'incorrect_answers_count' => $incorrectAnswersCount,
        ]);
    
        return response()->json([
            'correct_answers_count' => $correctAnswersCount,
            'incorrect_answers_count' => $incorrectAnswersCount,
        ]);
    }
    
    

// public function getStudentSolutionsBySubject($subjectId)
// {
//     $teacher = auth()->user();
    

//     return TestQuestion::with([
//             'question',
//             'selectedOption',
//             'correctOption',
//             'test' => function($query) use ($subjectId) {
//                 $query->where('subject_id', $subjectId)
//                       ->with('lessons'); 
//             },
//             'test.student',
//             'test.lessons' 
//         ])
//         ->whereHas('test', function($query) use ($subjectId, $teacher) {
//             $query->where('subject_id', $subjectId)
//                   ->whereHas('subjectStudents', function($q) use ($teacher) {
//                       $q->where('teacher_id', $teacher->id)
//                         ->where('status', 'accepted');
//                   })
//                   ->whereHas('teacherSubject', function($q) use ($teacher, $subjectId) {
//                       $q->where('teacher_id', $teacher->id)
//                         ->where('subject_id', $subjectId)
//                         ->where('status', 'accepted');
//                   });
//         })
//         ->get()
//         ->groupBy('test.student.name')
//         ->map(function($solutions, $studentName) {
//             $firstSolution = $solutions->first();
            
//             return [
//                 'student_name' => $studentName,
//                 'student_id' => $firstSolution->test->student_id,
//                 'lessons' => $firstSolution->test->lessons->pluck('name')->implode(', '),
//                 'solutions' => $solutions->map(function($item) {
//                     return [
//                         'question_id' => $item->question_id,
//                         'question_text' => $item->question->question_text,
//                         'student_answer' => $item->selectedOption ? $item->selectedOption->option_text : null,
//                         'correct_answer' => $item->correctOption ? $item->correctOption->option_text : null,
//                         'is_correct' => $item->is_correct,
//                         'test_id' => $item->test_id,
//                         'test_date' => $item->test->created_at->format('Y-m-d H:i'),
//                         'lesson_names' => $item->test->lessons->pluck('name')->implode(', ')
//                     ];
//                 })
//             ];
//         });
// }

// public function submitLessonAnswers(Request $request, $lessonId)
// {
//     $request->validate([
//         'answers' => 'required|array',
//         'answers.*' => 'exists:options,id'
//     ]);

//     if (auth()->user()->role != 'student') {
//         abort(403, 'غير مصرح للطلاب فقط');
//     }

//     $studentId = auth()->id();
//     $answers = $request->answers;
//     $lesson = Lesson::with('questions.options')->findOrFail($lessonId);

//     DB::beginTransaction();

//     try {
//         $test = Test::create([
//             'student_id' => $studentId,
//             'subject_id' => $lesson->subject_id,
//             'type' => 'lesson',
//             'status' => 'completed'
//         ]);

//         $test->lessons()->attach($lessonId);

//         $correctAnswersCount = 0;

//         foreach ($answers as $questionId => $selectedOptionId) {
//             $question = $lesson->questions->find($questionId);
            
//             if (!$question) {
//                 continue; 
//             }

//             $isCorrect = $question->options()
//                                 ->where('id', $selectedOptionId)
//                                 ->where('is_correct', true)
//                                 ->exists();

//             if ($isCorrect) {
//                 $correctAnswersCount++;
//             }

//             TestQuestion::create([
//                 'test_id' => $test->id,
//                 'question_id' => $questionId,
//                 'selected_option_id' => $selectedOptionId,
//                 'is_correct' => $isCorrect
//             ]);
//         }

//         $totalQuestions = $lesson->questions->count();
//         $scorePercentage = $totalQuestions > 0 
//             ? round(($correctAnswersCount / $totalQuestions) * 100, 2)
//             : 0;

//         DB::table('test_reports')::create([
//             'test_id' => $test->id,
//             'student_id' => $studentId,
//             'correct_answers_count' => $correctAnswersCount,
//             'incorrect_answers_count' => $totalQuestions - $correctAnswersCount,
//             'score_percentage' => $scorePercentage
//         ]);

//         DB::commit();

//         return response()->json([
//             'success' => true,
//             'message' => 'تم تسجيل الإجابات بنجاح',
//             'data' => [
//                 'test_id' => $test->id,
//                 'total_questions' => $totalQuestions,
//                 'correct_answers' => $correctAnswersCount,
//                 'score_percentage' => $scorePercentage,
//                 'lesson_id' => $lessonId,
//                 'lesson_name' => $lesson->name,
//                 'subject_name' => $lesson->subject->name
//             ]
//         ]);

//     } catch (\Exception $e) {
//         DB::rollBack();
        
//         return response()->json([
//             'success' => false,
//             'message' => 'حدث خطأ أثناء حفظ الإجابات',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }
public function startTest($test_id)
{
    $test = Test::with('questions.options')->findOrFail($test_id);

    return response()->json([
        'test' => $test,
        'questions' => $test->questions->map(function($question) {
            return [
                'question_text' => $question->question_text,
                'options' => $question->options->pluck('option_text')
            ];
        })
    ]);
    }


    public function viewStudentAnswers($testId)
{
    $test = Test::with(['questions.options'])->findOrFail($testId);

  
    $studentAnswers = $test->questions->map(function ($question) {
        $selectedOption = $question->options->where('id', $question->pivot->selected_option_id)->first();
        return [
            'question_id' => $question->id,
            'question_text' => $question->question_text,
            'selected_option' => $selectedOption ? [
                'id' => $selectedOption->id,
                'option_text' => $selectedOption->option_text,
                'is_correct' => $question->pivot->is_correct
            ] : null,
            'options' => $question->options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'option_text' => $option->option_text,
                    'is_correct' => $option->is_correct,
                ];
            })
        ];
    });

    return response()->json([
        'test_id' => $test->id,
        'student_id' => $test->student->name,
        'student_answers' => $studentAnswers,
    ]);
    }
    public function getAllTest(){
        return  Test::with(['questions.options'])->get();
    
    }



    //عرض اختبار 
    public function getTest(Request $request){


$test = Test::where('test_name',$request->test_name)->first();
if(!$test){
    return response()->json(['massage'=>'not found']);
}

return response()->json(['test'=>$test]);

    }








// عرض اختبارات مستخدم 

public function getUserTests()
{

    $userId = Auth()->id();


    $tests = Test::with(['questions.options'])
        ->where('user_id', $userId)
       // ->where('is_favorite', false)
        ->latest()
        ->get();

    if ($tests->isEmpty()) {
        return response()->json([
            'message' => 'No tests found for this user.'
        ], 404);
    }

    $formattedTests = $tests->map(function ($test) {
        return [
            'test_id' => $test->id,
            'subject_id' => $test->subject_id,
            'is_favorite' =>$test->is_favorite,
        
            'questions' => $test->questions->map(function ($question) {
                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'options' => $question->options->map(function ($option) {
                        return [
                            'id' => $option->id,
                            'option' => $option->option_text,
                        ];
                    }),
                ];
            }),
        ];
    });

    return response()->json([
        'tests' => $formattedTests
    ]);
}











public function getTestQuestions($testId)
{
    $test = Test::with('questions.options')->find($testId);

    if (!$test) {
        return response()->json(['message' => 'Test not found'], 404);
    }

    return response()->json($test->questions);
}



public function getTestReport($testId, $studentId)
{
    $report = Report::where('test_id', $testId)
                        ->where('student_id', $studentId)
                        ->first();

    if (!$report) {
        return response()->json([
            'message' => 'Report not found for this student and test.'
        ], 404);
    }

    return response()->json([
        'correct_answers_count' => $report->correct_answers_count,
        'incorrect_answers_count' => $report->incorrect_answers_count,
    ]);
}

//الطلاب اللي مجاوبين صح 
public function getPerfectStudents($testId)
{
    $reports = Report::where('test_id', $testId)
                         ->where('incorrect_answers_count', 0)
                         ->with('student') 
                         ->get();

    return response()->json([
        'students' => $reports->map(function ($report) {
            return [
                'student_id' => $report->student_id,
                'name' => $report->student->name ?? 'Unknown', 
                'correct_answers_count' => $report->correct_answers_count,
            ];
        }),
    ]);
}

//الاختبارات لدرس معين 
public function getTestsByLesson($lessonId)
{
    $tests = Test::with('questions.options', 'user') 
        ->whereHas('lessons', function ($query) use ($lessonId) {
            $query->where('lessons.id', $lessonId);
        })
        ->where('is_favorite', false)
        ->whereHas('user', function ($query) {
            $query->where('role_id', 2);
        })
        ->get();

    if ($tests->isEmpty()) {
        return response()->json(['message' => 'No tests found for this lesson.'], 404);
    }

    return response()->json($tests);
}




// حذف اختبار 
public function deleteTest($testId)
{
    $test = Test::find($testId);

    if (!$test) {
        return response()->json([
            'message' => 'Test not found.'
        ], 404);
    }

   
    if ($test->student_id !== Auth::id()) {
        return response()->json([
            'message' => 'You are not authorized to delete this test.'
        ], 403);
    }

   
    $test->delete();

    return response()->json([
        'message' => 'Test deleted successfully.'
    ], 200);
}



//حذف سؤال من اختبار 

public function removeQuestionFromTest($testId, $questionId)
{
    $test = Test::find($testId);

    if (!$test) {
        return response()->json([
            'message' => 'Test not found.'
        ], 404);
    }

   
    if (!$test->questions()->where('question_id', $questionId)->exists()) {
        return response()->json([
            'message' => 'This question is not linked to the test.'
        ], 400);
    }

   
    $test->questions()->detach($questionId);

    return response()->json([
        'message' => 'Question removed from test successfully.'
    ], 200);
}


//اضافة سؤال لاختبار 


public function addQuestionToTest($testId, $questionId)
{
    $test = Test::find($testId);

    if (!$test) {
        return response()->json([
            'message' => 'Test not found.'
        ], 404);
    }

   
    $questionExists = Question::where('id', $questionId)->exists();
    if (!$questionExists) {
        return response()->json([
            'message' => 'Question not found.'
        ], 404);
    }

   
    if ($test->questions()->where('question_id', $questionId)->exists()) {
        return response()->json([
            'message' => 'Question already exists in this test.'
        ], 409);
    }

    
    $test->questions()->attach($questionId);

    return response()->json([
        'message' => 'Question added to test successfully.'
    ], 201);
}

//عرض نتائج جميع الاختبارات لطالب 



public function getStudentTestReports($studentId)
{
    $reports = Report::where('student_id', $studentId)
        ->latest()
        ->get();

    if ($reports->isEmpty()) {
        return response()->json([
            'message' => 'No test reports found for this student.'
        ], 404);
    }

    $formattedReports = $reports->map(function ($report) {
        return [
            'report_id' => $report->id,
            'test_id' => $report->test_id,
            'correct_answers_count' => $report->correct_answers_count,
            'incorrect_answers_count' => $report->incorrect_answers_count,
            'created_at' => $report->created_at->toDateTimeString(),
        ];
    });

    return response()->json([
        'reports' => $formattedReports
    ]);
}


//عرض كل الاختبارات 

public function getAllTests() {


   
    {
        $tests = Test::all();
    
        if ($tests->isEmpty()) {
            return response()->json(['message' => 'No tests found.'], 404);
        }
    
        return response()->json([
            'tests' => $tests
        ]);
    }


}


//عرض سلم الاختبار 

public function getTestQuestionsWithCorrectOptions($testId)
{
    $test = Test::with(['questions.options' => function ($query) {
        $query->where('is_correct', 1)->select('id', 'question_id', 'option_text');
    }])->find($testId);

    if (!$test) {
        return response()->json(['message' => 'Test not found'], 404);
    }

    $result = $test->questions->map(function ($question) {
        return [
            'question_text' => $question->question_text,
            'correct_option' => $question->options->first()->option_text ?? null,
        ];
    });

    return response()->json($result);
}


}
