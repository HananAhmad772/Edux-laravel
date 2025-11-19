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
        'roadmap_content'
    ];
    
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}