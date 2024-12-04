<?php

namespace App\Imports;
use App\Models\Subject;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\YourPrimaryModel;
use Maatwebsite\Excel\Concerns\ToModel;

class MultiTableImport implements ToCollection
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function collection(Collection $rows)
    {
        $subjectId = request('subject_id');; 
        $lesson = null;
    
        foreach ($rows as $index => $row) {
            if ($index == 0) {
                continue; 
            }
    
            $lessonTitle = $row[0]; 
            $mainQuestionText = $row[1]; 
            $subQuestionText = $row[2];
            $explanation = $row[3] ?? null; 
            $correctOptionIndex = intval($row[4] ?? -1);
            $pageNumber = is_numeric($row[5] ?? null) ? intval($row[5]) : null;
    
            if (empty($lessonTitle) || empty($mainQuestionText)) {
                continue; 
            }
    
            if (is_null($lesson) || $lesson->title !== $lessonTitle) {
                $lesson = Lesson::firstOrCreate([
                    'title' => $lessonTitle,
                    'subject_id' => $subjectId,
                ]);
            }
    
            $mainQuestion = Question::firstOrCreate([
                'lesson_id' => $lesson->id,
                'question_text' => $mainQuestionText,
                'parent_question_id' => null,
            ], [
                'explanation' => $explanation,
                'page_number' => $pageNumber,
            ]);
    
            if (!empty($subQuestionText)) {
                $subQuestion = Question::firstOrCreate([
                    'lesson_id' => $lesson->id,
                    'question_text' => $subQuestionText,
                    'parent_question_id' => $mainQuestion->id,
                ], [
                    'page_number' => $pageNumber,
                    'explanation' => $explanation,
                ]);
    
                $optionTexts = [];
                $startIndex = 6; 
                $maxOptions = 4; 
                
                for ($i = $startIndex; $i < $startIndex + $maxOptions; $i++) {
                    if (isset($row[$i]) && !empty(trim($row[$i]))) {
                        $optionTexts[] = $row[$i];
                    }
                }
    
                foreach ($optionTexts as $index => $optionText) {
                    $isCorrect = ($index == $correctOptionIndex);
    
                    Option::firstOrCreate([
                        'question_id' => $subQuestion->id,
                        'option_text' => $optionText,
                    ], [
                        'is_correct' => $isCorrect,
                    ]);
                }
            }
        }
    }
    

    


    
}
