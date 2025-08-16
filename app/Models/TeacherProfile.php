<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherProfile extends Model
{
    use HasFactory;

    protected $fillable = [ 'teacher_id',
    'teacher_image',
    'teaching_start_date',
    'bio',
    'specialization',
    'province',
    'age'
];


public function user()
{

return $this->belongsTo(User::class);

}

}
