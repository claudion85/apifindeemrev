<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class EventRating extends Model
{
    public $timestamps = false;
    protected $table = 'events_ratings';

    protected $fillable = [];

    protected $dates = [
        'created_at',
    ];
}
