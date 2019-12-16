<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class Follower extends Model
{
    public $timestamps = false;

    protected $fillable = [];

    protected $dates = [
        // 'created_at',
    ];
}
