<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class DailyChallenge extends Model
{
    use HasUlids;
    
    protected $table = 'daily_challenges';
    
    protected $fillable = [
        'user_id',
        'roadmap_id',
        'challenge_date',
        'step_name',
        'topic_index',
        'topic_name',
        'challenge_description',
        'challenge_data',
        'student_submission',
        'ai_feedback',
        'points_earned',
        'is_completed'
    ];
    
    protected $casts = [
        'challenge_date' => 'date',
        'challenge_data' => 'array',
        'is_completed' => 'boolean'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function roadmap()
    {
        return $this->belongsTo(StudentRoadmap::class, 'roadmap_id');
    }
}