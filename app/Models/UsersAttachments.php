<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersAttachments extends Model
{
    protected $table = 'user_attachments';

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'category',
        'path'
    ];
}
