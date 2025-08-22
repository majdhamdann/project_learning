<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallengeReport extends Model
{
    use HasFactory;


    protected $fillable=['student_id','challenge_id','correct_answers_count','incorrect_answers_count','challenge_points'];

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}


