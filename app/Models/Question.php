<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;
    protected $fillable = ['content', 'lesson_id', 'page_number', 'explanation','question_text','parent_question_id'];
    public function tests()
    {
        return $this->belongsToMany(Test::class, 'test_questions', 'question_id', 'test_id');
    }
    public function subQuestions()
    {
        return $this->hasMany(Question::class, 'parent_question_id', 'id');
    }

    public function parentQuestion()
    {
        return $this->belongsTo(Question::class, 'parent_question_id', 'id');
    }
    public function options()
    {
        return $this->hasMany(Option::class);
    }
    public function correctOption()
    {
        return $this->hasOne(Option::class)->where('is_correct', true);
    }
    public function lessons()
    {
        return $this->belongsTo(Lesson::class);
    }
}
