<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    use HasFactory;


    protected $fillable = [
        'teacher_id',
        'title',
        'description',
        'start_time',       
        'duration_minutes',  
    ];

   
    public function getEndTimeAttribute()
    {
        return \Carbon\Carbon::parse($this->start_time)->addMinutes($this->duration_minutes);
    }

 
    public function questions()
    {
        return $this->belongsToMany(Question::class, 'challenge_question', 'challenge_id', 'question_id');
    }

    
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');

    }

    public function reports()
{
    return $this->hasMany(ChallengeReport::class, 'challenge_id');
}


}
