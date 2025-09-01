<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MentorProfile extends Model
{
    protected $table = 'mentors_profiles';

    protected $fillable = [
        'user_id',
        'qualifications',
        'area_of_expertise',
        'experience_years',
        'institute',
        'bio',
    ];

        public function user()
    {
        return $this->belongsTo(User::class);
    }
}
