<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoneMaster extends Model
{
    //
    public $table = 'zone_master';
    protected $fillable = [
        'zone_name',
        'status',
    ];

    //relation
    public function mapCountries()
    {
        return $this->belongsToMany(Country::class, 'zone_country_mapping', 'zone_id', 'country_id');
    }
}
