<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WorldSmallCategory;

class WorldSmallCategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $categories = [
            '1' => [
                '1' => [
                    '中学生',
                    '高校生',
                    '大学生',
                    '理系',
                    '文系',
                    '受験生',
                    '就活生',
                    '部活'
                ],
                '2' => [
                    '北海道',
                    '青森県',
                    '岩手県',
                    '宮城県',
                    '秋田県',
                    '山形県',
                    '福島県',
                    '茨城県',
                    '栃木県',
                    '群馬県',
                    '埼玉県',
                    '千葉県',
                    '東京都',
                    '神奈川県',
                    '山梨県',
                    '長野県',
                    '新潟県',
                    '富山県',
                    '石川県',
                    '福井県',
                    '岐阜県',
                    '静岡県',
                    '愛知県',
                    '三重県',
                    '滋賀県',
                    '京都府',
                    '大阪府',
                    '兵庫県',
                    '奈良県',
                    '和歌山県',
                    '鳥取県',
                    '島根県',
                    '岡山県',
                    '広島県',
                    '山口県',
                    '徳島県',
                    '香川県',
                    '愛媛県',
                    '高知県',
                    '福岡県',
                    '佐賀県',
                    '長崎県',
                    '熊本県',
                    '大分県',
                    '宮崎県',
                    '鹿児島県',
                    '沖縄県'
                ],
                '3' => [
                    '新入社員',
                    '経年社員',
                    '起業',
                    'バイト',
                    '副業',
                    'フリーランス',
                    'ニート'
                ]
            ],
            '2' => [
                '1' => [
                    'アウトドア',
                    'インドア',
                    'スポーツ',
                    '音楽',
                    '映画',
                    'DIY',
                    'ゲーム',
                    'キャンプ',
                    '推し活',
                    'ダイエット',
                    '筋トレ',
                    '投資',
                    'ギャンブル',
                    '飲食'
                ],
                '2' => [
                    'デート',
                    'お店',
                    '失恋',
                    '彼氏',
                    '彼女',
                    '片想い',
                    'モテたい'
                ]
            ]
        ];

        foreach ($categories as $bigCategoryId => $mediumCategory) {
            foreach ($mediumCategory as $mediumCategoryId => $smallCategory) {
                foreach ($smallCategory as $smallCategoryName) {
                    WorldSmallCategory::create([
                        'name' => $smallCategoryName,
                        'world_big_category_id' => $bigCategoryId,
                        'world_medium_category_id' => $mediumCategoryId,
                    ]);
                }
            }
        }
    }
}
