<?php
// app/Models/Earthquake.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Earthquake extends Model
{
    protected $fillable = [
        'external_id',
        'version',
        'reported_at',
        'occurred_at',
        'hypocenter_name',
        'latitude',
        'longitude',
        'depth_km',
        'magnitude',
        'max_scale',
        'max_scale_label',
        'tsunami_code',
        'tsunami_label',
        'raw',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'occurred_at' => 'datetime',
        'raw' => 'array',
    ];
}
