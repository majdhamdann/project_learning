<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectStudent extends Model
{
    use HasFactory;

    protected $table = 'subject_student';

    protected $fillable = ['user_id', 'subject_id', 'status','teacher_id'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function subject() {
        return $this->belongsTo(Subject::class);
    }
}
