<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoneCountryMapping extends Model
{
    //
    public $table = 'zone_country_mapping';
    protected $fillable = [
        'zone_id',
        'country_id',
    ];
}
