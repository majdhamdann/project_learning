<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends User
{
    protected $table = 'users';

    protected static function booted()
    {
        static::addGlobalScope('teacher', function ($query) {
            $query->where('role_id', 2);
        });
    }

    public function favoriteStudents()
    {
        return $this->belongsToMany(Student::class, 'teacher_favorite', 'teacher_id', 'student_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    

/*
    public function subjectRequests()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subject')
                    ->withPivot('status')
                    ->withTimestamps();
    }
  */  

}
