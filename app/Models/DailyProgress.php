<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class DailyProgress extends Model
{
    use HasUlids;
    
    protected $table = 'daily_progress';
    
    protected $fillable = [
        'user_id',
        'roadmap_id',
        'progress_date',
        'step_name',
        'topic_index',
        'topic_name',
        'topic_completed',
        'xp_earned',
        'time_spent_minutes',
        'completed_tasks',
        'meta'
    ];
    
    protected $casts = [
        'progress_date' => 'date',
        'topic_completed' => 'boolean',
        'completed_tasks' => 'array',
        'meta' => 'array'
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

