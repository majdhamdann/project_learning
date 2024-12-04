<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
   

public function addQuestionWithOptions(Request $request)
{
    $validated = $request->validate([
        'lesson_id' => 'required|exists:lessons,id',
        'question_text' => 'required|string',
        'page_number' => 'nullable|integer',
        'options' => 'required|array|min:2',
        'options.*.option_text' => 'sometimes|string',
        'options.*.option_image' => 'sometimes|nullable|file|mimes:jpeg,png,jpg,gif',
        'correct_option' => 'required|integer',
        'explanation' => 'nullable|string',
        'parent_question_id' => 'sometimes|nullable|exists:questions,id'
    ]);

    $question = Question::create([
        'lesson_id' => $validated['lesson_id'],
        'question_text' => $validated['question_text'],
        'page_number' => $validated['page_number'] ?? null,
        'explanation' => $validated['explanation'] ?? null  
    ]);

    foreach ($validated['options'] as $index => $optionData) {
        $isCorrect = $index == $validated['correct_option'];

        $optionText = $optionData['option_text'] ?? null;
        $filePath = null;
        if (isset($optionData['option_image'])) {
            $imageName = time().'.'.$optionData['option_image']->extension();  
          
            $filePath=  $optionData['option_image']->move(public_path('options'), $imageName);
            // $storedPath = $optionData['option_image']->store('options', 'public');
            //$filePath = asset('storage/' . $storedPath);
            $optionText = $filePath;
        } else {
            $optionText = $optionData['option_text'];
        }

        Option::create([
            'question_id' => $question->id,
            'option_text' => $optionText,
            'is_correct' => $isCorrect,
            'explanation' => $optionData['explanation'] ?? null, 
        ]);
    }
    
    return response()->json([
        'message' => 'تم إضافة السؤال مع الخيارات بنجاح',
        'question' => $question->load('options'), 
    ], 201);
}




public function deleteQuestion($id){
        
        if(Question::where('id',$id)->exists()){
            Question::where('id',$id)->with('options')->delete();
            return response()->json([
                'message' => 'تم حذف السؤال مع الخيارات بنجاح',
                
            ], 201);

        }
      
      
}


public function updateQuestion(Request $request, $questionId)
{
    $validated = $request->validate([
        'lesson_id' => 'required|exists:lessons,id',
        'question_text' => 'required|string',
        'page_number' => 'nullable|integer',
        'explanation' => 'nullable|string',
    ]);

    $question = Question::findOrFail($questionId);
    $question->update([
        'lesson_id' => $validated['lesson_id'],
        'question_text' => $validated['question_text'],
        'page_number' => $validated['page_number'] ?? null,
        'explanation' => $validated['explanation'] ?? null
    ]);

    return response()->json([
        'message' => 'تم تعديل السؤال بنجاح',
        'question' => $question,
    ], 200);
}


public function updateOption(Request $request, $questionId, $optionId)
{
    $validated = $request->validate([
        'option_text' => 'sometimes|string',
        'option_image' => 'sometimes|nullable|file|mimes:jpeg,png,jpg,gif',
        'is_correct' => 'nullable|boolean',
        'explanation' => 'nullable|string',
    ]);

    $question = Question::findOrFail($questionId);

    $option = Option::where('question_id', $question->id)->findOrFail($optionId);

    if (isset($validated['option_text'])) {
        $option->option_text = $validated['option_text'];
    }

    if (isset($validated['option_image'])) {
        $option->option_image = $validated['option_image']->store('options', 'public');
    }

    if (isset($validated['is_correct'])) {
        $option->is_correct = $validated['is_correct'];
    }

    if (isset($validated['explanation'])) {
        $option->explanation = $validated['explanation'];
    }

    $option->save();

    return response()->json([
        'message' => 'تم تعديل الخيار بنجاح',
        'option' => $option,
    ], 200);
}



}
