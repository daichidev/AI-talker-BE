<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'device_id',
        'fcm_device_token',
        'face_photo',
        'anketo_status',
        'match_user_id',
        'remember_token',
        'role',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function anketos()
    {
        return $this->hasOne(Anketo::class);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function personalityTest()
    {
        return $this->hasOne(PersonalityTest::class);
    }

    public function avatars()
    {
        return $this->hasMany(Avatar::class);
    }

    public function latestAvatar()
    {
        return $this->hasOne(Avatar::class)->latestOfMany();
    }
}
