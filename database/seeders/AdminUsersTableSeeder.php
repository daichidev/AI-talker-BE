<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUsersTableSeeder extends Seeder
{
    public function run()
    {

        DB::table('users')->insert([
            'name' => "admin",
            'email' => 'admin-test@gmail.com',
            'email_verified_at' => null,
            'password' => Hash::make('test123456'), // Use real password or same hash
            'device_id' => '4',
            'face_photo' => 'face_id_photos/test.jpg',
            'anketo_status' => 0,
            'remember_token' => null,
            'role'=>'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}