<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallengeQuestion extends Model
{
    use HasFactory;



    protected $table = 'challenge_question';

    protected $fillable = [
        'challenge_id',
        'question_id',
    ];
}
