<?php

namespace App\Services;

class FortuneService
{
    /**
     * @return array{love: int, work: int, money: int, overall: int, message: string, luckyColor: string, luckyColorName: string, luckyItem: string}
     */
    public function generateFortune(string $bloodType): array
    {
        $bloodType = str_replace('型', '', $bloodType);
        $today = now();
        $seed = $today->year * 10000 + $today->month * 100 + $today->day;

        $bloodTypeSeed = $bloodType === 'AB'
            ? ord('A') + ord('B')
            : ord($bloodType);

        $combinedSeed = $seed + $bloodTypeSeed;

        $random1 = $this->seededRandom($combinedSeed);
        $random2 = $this->seededRandom($combinedSeed + 1);
        $random3 = $this->seededRandom($combinedSeed + 2);
        $random4 = $this->seededRandom($combinedSeed + 3);
        $random5 = $this->seededRandom($combinedSeed + 4);
        $random6 = $this->seededRandom($combinedSeed + 5);

        $love = (int) floor($random1 * 5) + 1;
        $work = (int) floor($random2 * 5) + 1;
        $money = (int) floor($random3 * 5) + 1;
        $overall = (int) round(($love + $work + $money) / 3);

        $messages = $this->getMessages();
        $messageList = $messages[$bloodType] ?? $messages['A'];
        $message = $messageList[(int) floor($random4 * count($messageList))];

        $luckyColors = $this->getLuckyColors();
        $luckyColor = $luckyColors[(int) floor($random5 * count($luckyColors))];

        $luckyItems = $this->getLuckyItems();
        $luckyItem = $luckyItems[(int) floor($random6 * count($luckyItems))];

        return [
            'love' => $love,
            'work' => $work,
            'money' => $money,
            'overall' => $overall,
            'message' => $message,
            'luckyColor' => $luckyColor['hex'],
            'luckyColorName' => $luckyColor['name'],
            'luckyItem' => $luckyItem,
        ];
    }

    /**
     * Deterministic pseudo-random in [0, 1), matching the TypeScript seededRandom.
     */
    private function seededRandom(int $seed): float
    {
        $x = sin($seed) * 10000;

        return $x - floor($x);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function getMessages(): array
    {
        return [
            'A' => [
                '今日は計画的に物事を進めることで、大きな成果が得られそうです。細部にこだわることが成功の鍵となります。',
                '周囲との協調性を大切にすることで、思わぬチャンスが訪れるでしょう。丁寧なコミュニケーションを心がけて。',
                '慎重さが功を奏する一日。焦らず、一歩ずつ確実に進むことで目標に近づけます。',
            ],
            'B' => [
                '自由な発想が幸運を呼び込む日。いつもと違うアプローチを試してみると、新しい発見があるかもしれません。',
                '好奇心のままに行動することで、素敵な出会いや経験が待っています。直感を信じて進みましょう。',
                '柔軟な対応が求められる一日。変化を楽しむ気持ちで臨めば、良い結果につながります。',
            ],
            'O' => [
                'リーダーシップを発揮できる絶好の日。周囲を引っ張っていくことで、大きな成果が期待できます。',
                '行動力が運気を高める鍵。思い立ったら即行動することで、チャンスをつかめるでしょう。',
                '前向きな姿勢が周囲に良い影響を与えます。自信を持って挑戦することで道が開けます。',
            ],
            'AB' => [
                'バランス感覚が光る一日。冷静な判断力を活かして、複雑な問題もスムーズに解決できそうです。',
                '独創的なアイデアが評価される時。あなたならではの視点を大切にしてください。',
                '理性と感性のバランスを取ることで、最良の選択ができる日。直感と論理の両方を活用しましょう。',
            ],
        ];
    }

    /**
     * @return array<int, array{name: string, hex: string}>
     */
    private function getLuckyColors(): array
    {
        return [
            ['name' => 'ピンク', 'hex' => '#FF69B4'],
            ['name' => 'ブルー', 'hex' => '#4169E1'],
            ['name' => 'イエロー', 'hex' => '#FFD700'],
            ['name' => 'グリーン', 'hex' => '#32CD32'],
            ['name' => 'オレンジ', 'hex' => '#FF8C00'],
            ['name' => 'パープル', 'hex' => '#9370DB'],
            ['name' => 'レッド', 'hex' => '#DC143C'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getLuckyItems(): array
    {
        return [
            'ハンカチ',
            'ペン',
            '腕時計',
            'イヤリング',
            'ネックレス',
            'スマホケース',
            'キーホルダー',
            'マグカップ',
            '手帳',
            'メガネ',
        ];
    }
}
