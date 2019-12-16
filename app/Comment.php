<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class Comment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user', 'event_id', 'group_id', 'comment',
        'likes', 'created_at'
    ];

    protected $dates = [
        'created_at',
    ];
}
