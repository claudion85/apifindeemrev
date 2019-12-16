<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class AbuseReport extends Model
{
    protected $table = 'abuse_reports';

    protected $fillable = [];
}
