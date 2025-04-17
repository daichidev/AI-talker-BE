<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AvatarsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('avatars')->insert([
            'avatar_link' => 'test.jpg',
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}