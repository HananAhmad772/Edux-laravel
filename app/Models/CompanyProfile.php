<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $table = 'company_profiles';

    protected $fillable = [
        'user_id',
        'company_size',
        'industry',
        'bio',
        'website_link',
        'location',
    ];

        public function user()
    {
        return $this->belongsTo(User::class);
    }
}
