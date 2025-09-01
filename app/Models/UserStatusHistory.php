<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStatusHistory extends Model
{
    protected $table = 'user_status_history';

    protected $fillable = [
        'user_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by'
    ];
}
