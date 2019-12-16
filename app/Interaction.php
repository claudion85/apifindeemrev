<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class Interaction extends Model
{
    protected $fillable = [
        'user', 'interaction_type', 'interaction_entity',
        'entity_id', 'visibility', 'created_at',
    ];

    protected $dates = [
        'created_at',
    ];
}
