<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'phone', 'password', 'role_id','profile_image','email'
    ];



    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
   /* protected $casts = [
        'email_verified_at' => 'datetime',
    ];*/
    public function createdTests()
    {
        return $this->hasMany(Test::class, 'teacher_id');
    }

    public function takenTests()
    {
        return $this->hasMany(Test::class, 'student_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'student_id');
    }
    public function tests()
    {
        return $this->belongsToMany(Test::class, 'student_test','test_id','student_id');
    }
    public function subject()
    {
        return $this->hasOne(Subject::class);
    }
    public function subjects(): BelongsToMany
{
    return $this->belongsToMany(Subject::class, 'subject_student', 'student_id', 'subject_id');
}
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
