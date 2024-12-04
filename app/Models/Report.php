<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable=['student_id','test_id','correct_answers_count	','incorrect_answers_count'];
    protected $table = 'test_reports';
    use HasFactory;
    public function test()
    {
        return $this->belongsTo(Test::class);
    }
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
