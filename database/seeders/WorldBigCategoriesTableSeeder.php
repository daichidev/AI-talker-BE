<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WorldBigCategory;

class WorldBigCategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bigCategories = [
            'ソーシャル',
            'ライフスタイル',
            'Shop'
        ];

        foreach ($bigCategories as $bigCategory) {
            WorldBigCategory::create([
                'name' => $bigCategory,
            ]);
        }
    }
}
