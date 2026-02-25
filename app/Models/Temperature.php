<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Temperature extends Model
{
    use HasFactory;
    protected $table = 'temperatures';

    protected $fillable = [
        'amebas_code',
        'min_temperature',
        'max_temperature'
    ];
}
