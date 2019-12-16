<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class EntityView extends Model
{
    public $timestamps = false;

    protected $table = 'entity_views';

    protected $dates = [
        'created_at',
    ];

    protected $fillable = [];
}
