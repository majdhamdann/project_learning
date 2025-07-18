<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends User
{
    protected $table = 'users';

    protected static function booted()
    {
        static::addGlobalScope('student', function ($query) {
            $query->where('role_id', 1);
        });
    }

    public function favoredByTeachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_favorite', 'student_id', 'teacher_id');
    }

    public function Subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subjects::class, 'subject_student', 'subject_id', 'student_id')->withPivot('status')->withTimestamps();
    }

}
