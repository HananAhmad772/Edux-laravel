<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class StudentQuiz extends Model
{
    use HasUlids;
    
    protected $table = 'student_quiz';
    
    protected $fillable = [
        'student_id',
        'questions',
        'answers',
        'score'
    ];
    
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}