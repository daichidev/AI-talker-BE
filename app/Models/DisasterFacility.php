<?php
// app/Models/DisasterFacility.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisasterFacility extends Model
{
    protected $fillable = [
        'longitude',
        'latitude',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];
}
