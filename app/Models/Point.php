<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    use HasFactory;

    protected $fillable = ['points', 'student_id', 'teacher_id'];
    public function teacher()
{
    return $this->belongsTo(User::class, 'teacher_id');
}
public function student()
{
    return $this->belongsTo(User::class, 'student_id');
}

}
