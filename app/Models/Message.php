<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasUlids;
    
    protected $table = 'messages';
    
    protected $fillable = [
        'user_id',
        'roadmap_id',
        'message_body',
        'role'
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