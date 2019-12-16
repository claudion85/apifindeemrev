<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class Category extends Model
{
    public $timestamps = false;

    protected $fillable = [];
}
