<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalityAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'personality_type',
        'result',
    ];

    // Relationship: each assessment belongs to one user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
