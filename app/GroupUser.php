<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class GroupUser extends Model
{
    protected $table = 'groups_users';

    protected $fillable = [];

    protected $dates = [
        'created_at',
    ];
}
