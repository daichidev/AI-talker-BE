<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalityTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'personality_answers_array',
        'mean_values_array'
    ];
}
