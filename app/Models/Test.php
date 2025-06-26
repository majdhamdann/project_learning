<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;
    protected $fillable = ['lesson_id', 'student_id','subject_id'];
    public function questions()
    {
        return $this->belongsToMany(Question::class, 'test_questions')
                ->withPivot('selected_option_id', 'is_correct');
    }
    

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
    public function students()
    {
        return $this->belongsToMany(User::class, 'student_test','student_id','test_id');
    }
    public function report()
    {
        return $this->hasOne(Report::class);
    }

    public function lesson()
{
    return $this->belongsTo(Lesson::class);
}

public function subject()
{
    return $this->belongsTo(Subject::class);
}
}
