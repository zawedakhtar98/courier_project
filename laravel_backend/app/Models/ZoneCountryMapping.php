<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoneCountryMapping extends Model
{
    //
    protected $fillable = [
        'zone_id',
        'country_id',
    ];
}
