<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'ai_name',
        'gender',
        'birthdate',
        'hometown',
        'address',
        'blood_type',
        'school_name',
        'company_name',
        'income_or_allowance',
        'hobby',
        'family_structure',
        'special_skills',
        'dream',
        'favorite_type',
        'weakness',
        'animal_fortune_telling_result',
    ];
}
