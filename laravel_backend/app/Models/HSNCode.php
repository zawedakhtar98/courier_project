<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HSNCode extends Model
{
    public $table = 'hsn_codes';

    protected $fillable = [
        'hsn_code',
        'description',
        'status',
    ];
}
