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
    public function createTest(Request $request)
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

    foreach ($validated['answers'] as $questionId => $selectedOptionId) {
        $question = $test->questions->find($questionId);

        if ($question) {
            $isCorrect = $question->options()->where('id', $selectedOptionId)->value('is_correct');
            $isCorrect = $isCorrect !== null ? $isCorrect : false;

            $test->questions()->updateExistingPivot($questionId, [
                'selected_option_id' => $selectedOptionId,
                'is_correct' => $isCorrect,
            ]);
        }
    }

    // إعادة تحميل البيانات بعد التحديث
    $pivotData = $test->questions()->withPivot('is_correct')->get();
    $correctAnswersCount = $pivotData->where('pivot.is_correct', true)->count();
    $incorrectAnswersCount = $pivotData->where('pivot.is_correct', false)->count();

    $test->report()->create([
        'student_id' => $test->student_id,
        'correct_answers_count' => $correctAnswersCount,
        'incorrect_answers_count' => $incorrectAnswersCount,
    ]);

    return response()->json([
        'correct_answers_count' => $correctAnswersCount,
        'incorrect_answers_count' => $incorrectAnswersCount,
    ]);
}

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



//ارجاع الاختبارات التي قام بها طالب معين 
public function getTestsByStudent($studentId)
{
    $tests = Test::with(['lesson', 'subject', 'questions.options'])
        ->where('student_id', $studentId)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'student_id' => $studentId,
        'tests' => $tests
    ]);
}

//الاختبارت لاستاذ معين 
public function getTestsByTeacher($teacherId)
{
    $tests = Test::whereHas('subject', function ($query) use ($teacherId) {
        $query->where('teacher_id', $teacherId);
    })
    ->with([
        'subject',
        'lesson',
        'questions.options' // ← تحميل الأسئلة مع خياراتها
    ])
    ->orderBy('created_at', 'desc')
    ->get();

    return response()->json([
        'teacher_id' => $teacherId,
        'tests' => $tests,
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


//نتيجة الاختبار 
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
                         ->with('student') // نفترض في علاقة student()
                         ->get();

    return response()->json([
        'students' => $reports->map(function ($report) {
            return [
                'student_id' => $report->student_id,
                'name' => $report->student->name ?? 'Unknown', // إذا عندك علاقة
                'correct_answers_count' => $report->correct_answers_count,
            ];
        }),
    ]);
}

//الاختبارات لدرس معين 
public function getTestsByLesson($lessonId)
{
    $tests = Test::with('questions.options') // تحميل الأسئلة مع خياراتها
                 ->where('lesson_id', $lessonId)
                 ->get();

    return response()->json($tests);
}







}
