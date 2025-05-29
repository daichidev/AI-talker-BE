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
        'bot_nickname',
        'gender',
        'birthdate',
        'hometown',
        'address',
        'blood_type',
        'school_name',
        'school_year',
        'club_activity',
        'department',
        'job',
        'company_name',
        'position',
        'hobby',
        'family_structure',
        'special_skills',
        'dream',
        'animal_fortune_telling_result',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}