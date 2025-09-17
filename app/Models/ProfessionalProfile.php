<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalProfile extends Model
{
    protected $table = 'professionals_profiles';

    protected $fillable = [
        'user_id',
        'executive_summary',
        'skills',
        'current_position',
        'year_of_experience',
        'bio',
    ];

        public function user()
    {
        return $this->belongsTo(User::class);
    }
}
