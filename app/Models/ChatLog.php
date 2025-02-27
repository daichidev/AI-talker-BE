<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatLog extends Model
{
    use HasFactory;

    protected $table = 'chat_logs';

    protected $fillable = [
        'user_id',
        'question',
        'answer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}