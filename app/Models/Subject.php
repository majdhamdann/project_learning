<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'teacher_id','price'];

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subject_student', 'subject_id', 'student_id');
    }
    
    public function tests()
    {
        return $this->hasMany(Test::class);
    }
    public function teacher()
    {
        return $this->hasOne(User::class);
    }
}
