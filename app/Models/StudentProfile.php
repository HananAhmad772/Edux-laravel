<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    use HasUlids;
    protected $table = 'student_profiles';

    protected $fillable = [
        'user_id',
        'dob',
        'gender',
        'class_year',
        'institute',
        'major_subject',
        'bio'
    ];

        public function user()
    {
        return $this->belongsTo(User::class);
    }
}
