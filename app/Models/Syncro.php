<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Syncro extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'score_profile',
        'done_animal_fortune',
        'done_big5_analysis',
        'done_kakeai',
        'score_login',
        'score_ai_talk',
        'score_friend_invite_sent',
        'score_friend_invite_received',
        'done_personality_test',
        'score_account_link',
        'score_sns_link',
        'done_location_info',
        'done_cookie_on'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}