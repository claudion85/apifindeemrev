<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user', 'notification_type', 'notification_entity',
        'entity_id', 'read', 'created_at',
    ];
}
