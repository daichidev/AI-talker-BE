<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnketosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('anketos')->insert([
            [
                'user_id' => 1,
                'name' => '今野大地',
                'animal_fortune_telling' => 'チーター',
                'animal_fortune_telling_characteristics' => '好奇心旺盛で冒険好き',
                'birthdate' => '1990.05.12',
                'gender' => '男',
                'user_nickname' => 'サタン',
                'bot_nickname' => 'アバドン',
                'hometown' => '東京都渋谷区',
                'address' => '（例：愛知県名古屋市）',
                'blood_type' => 'O',
                'job' => '中学生',
                'hobby' => '卓球',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
