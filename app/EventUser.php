<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class EventUser extends Model
{
    public $timestamps = false;
    protected $table = 'events_users';

    protected $fillable = [];

    protected $dates = [
        'created_at',
    ];
}
