<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class StudentRoadmap extends Model
{
    use HasUlids;
    
    protected $table = 'student_roadmaps';
    
    protected $fillable = [
        'student_id',
        'roadmap_content',
        'roadmap_json',
        'status'
    ];
    
    protected $casts = [
        'roadmap_json' => 'object'
    ];
    
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
    
    /**
     * Get the structured roadmap data
     *
     * @return array|null
     */
    public function getStructuredRoadmap()
    {
        if (is_string($this->roadmap_json)) {
            return json_decode($this->roadmap_json, true);
        }
        return $this->roadmap_json;
    }
}