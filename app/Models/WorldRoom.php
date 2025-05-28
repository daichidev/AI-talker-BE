<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorldRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'world_big_category_id',
        'world_medium_category_id',
        'world_small_category_id'
    ];
}