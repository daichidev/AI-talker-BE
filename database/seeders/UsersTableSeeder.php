<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            'name' => null,
            'email' => 'test123456@gmail.com',
            'email_verified_at' => null,
            'password' => Hash::make('test123456'), // Use real password or same hash
            'device_id' => '1',
            'fcm_device_token' => '2',
            'face_photo' => 'face_id_photos/test.jpg',
            'anketo_status' => 0,
            'remember_token' => null,
            'role'=>'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => null,
            'email' => 'test111111@gmail.com',
            'email_verified_at' => null,
            'password' => Hash::make('test111111'), // Use real password or same hash
            'device_id' => '2',
            'fcm_device_token' => '3',
            'face_photo' => 'face_id_photos/test.jpg',
            'anketo_status' => 0,
            'remember_token' => null,
            'role'=>'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => null,
            'email' => 'test222222@gmail.com',
            'email_verified_at' => null,
            'password' => Hash::make('test222222'), // Use real password or same hash
            'device_id' => '3',
            'fcm_device_token' => '1',
            'face_photo' => 'face_id_photos/test.jpg',
            'anketo_status' => 0,
            'remember_token' => null,
            'role'=>'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}