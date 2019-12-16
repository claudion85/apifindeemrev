<?php

namespace App;

use App\Scopes\EnabledScope;
use Jenssegers\Mongodb\Eloquent\Model;

class Event extends Model
{
    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
    ];

    protected $fillable=['main_category'];

    public static function boot()
    {
        static::addGlobalScope(new EnabledScope);
    }
}
