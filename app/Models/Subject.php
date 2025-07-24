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
 /*   public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subject_student', 'subject_id', 'student_id')->withPivot('status')->withTimestamps();
     }
    */
    public function tests()
    {
        return $this->hasMany(Test::class);
    }
    //public function teacher()
    //{
      //  return $this->hasOne(User::class);
    //}

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'teacher_subject', 'subject_id', 'teacher_id')
                    ->withPivot('status', 'teacher_image', 'teaching_start_date')
                    ->withTimestamps();
    }
    
/*
public function requestedTeachers()
{
    return $this->belongsToMany(Teacher::class, 'teacher_subject')
                ->withPivot('status')
                ->withTimestamps();
}
*/


}
