<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class Token extends Model
{
    protected $fillable = [
        'user', 'type', 'user_agent', 'token',
    ];
}
