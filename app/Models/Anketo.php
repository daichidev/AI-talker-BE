<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anketo extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'animal_fortune_telling', 'animal_fortune_telling_characteristics',
        'birthdate', 'gender', 'user_nickname', 'bot_nickname', 'hometown',
        'address', 'blood_type', 'job', 'hobby'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
