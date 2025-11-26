<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    use HasUlids;
    
    protected $table = 'user_progress';
    
    protected $fillable = [
        'user_id',
        'roadmap_id',
        'current_step',
        'current_topic_index',
        'last_active_at'
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