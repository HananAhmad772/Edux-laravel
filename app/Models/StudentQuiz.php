<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentQuiz extends Model
{
    protected $table = 'student_quiz';
    
    protected $fillable = [
        'student_id',
        'questions',
        'answers',
        'score'
    ];
    
    protected $casts = [
        'questions' => 'array',
        'answers' => 'array',
        'score' => 'decimal:2'
    ];
    
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}