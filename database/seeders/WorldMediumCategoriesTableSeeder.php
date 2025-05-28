<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WorldMediumCategory;

class WorldMediumCategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mediumCategories = [
            '1' => [
                '学校',
                '都道府県',
                '社会人'
            ],
            '2' => [
                '趣味',
                '恋愛',
            ],
            '3' => [
                'アパレル',
                'ビジュアル',
                'アクセサリー',
                'ルーム',
                'ペット'
            ],
        ];

        foreach ($mediumCategories as $bigCategoryId => $mediumCategory) {
            foreach ($mediumCategory as $mediumCategoryName) {
                WorldMediumCategory::create([
                    'name' => $mediumCategoryName,
                    'world_big_category_id' => $bigCategoryId,
                ]);
            }
        }
    }
}
