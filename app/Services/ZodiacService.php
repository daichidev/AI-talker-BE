<?php

namespace App\Services;

use Carbon\Carbon;

class ZodiacService
{
    private const LUCKY_COLORS = [
        'レッド',
        'ブルー',
        'グリーン',
        'イエロー',
        'ピンク',
        'パープル',
        'ホワイト',
        'ブラック',
    ];

    private const LUCKY_ITEMS = [
        'ハンカチ',
        'スマホケース',
        '腕時計',
        'アクセサリー',
        'ノート',
        'カフェラテ',
        'スニーカー',
        '本',
    ];

    /** @var array<string, string> */
    private static array $zodiacSignMap = [
        'aries' => 'おひつじ座',
        'taurus' => 'おうし座',
        'gemini' => 'ふたご座',
        'cancer' => 'かに座',
        'leo' => 'しし座',
        'virgo' => 'おとめ座',
        'libra' => 'てんびん座',
        'scorpio' => 'さそり座',
        'sagittarius' => 'いて座',
        'capricorn' => 'やぎ座',
        'aquarius' => 'みずがめ座',
        'pisces' => 'うお座',
    ];

    /**
     * Get Japanese name for a zodiac sign key.
     */
    public static function getZodiacSignName(string $sign): string
    {
        return self::$zodiacSignMap[$sign] ?? $sign;
    }

    /**
     * @return array<string, string>
     */
    public static function getZodiacSignMap(): array
    {
        return self::$zodiacSignMap;
    }

    /**
     * Get zodiac sign key from birth date (e.g. "aries", "taurus").
     *
     * @param Carbon|\DateTimeInterface|string $date
     */
    public function getZodiacSign(Carbon|\DateTimeInterface|string $date): string
    {
        $d = $this->normalizeDate($date);
        $month = (int) $d->format('n'); // 1-12
        $day = (int) $d->format('j');   // 1-31

        if (($month === 3 && $day >= 21) || ($month === 4 && $day <= 19)) {
            return 'aries';
        }
        if (($month === 4 && $day >= 20) || ($month === 5 && $day <= 20)) {
            return 'taurus';
        }
        if (($month === 5 && $day >= 21) || ($month === 6 && $day <= 21)) {
            return 'gemini';
        }
        if (($month === 6 && $day >= 22) || ($month === 7 && $day <= 22)) {
            return 'cancer';
        }
        if (($month === 7 && $day >= 23) || ($month === 8 && $day <= 22)) {
            return 'leo';
        }
        if (($month === 8 && $day >= 23) || ($month === 9 && $day <= 22)) {
            return 'virgo';
        }
        if (($month === 9 && $day >= 23) || ($month === 10 && $day <= 23)) {
            return 'libra';
        }
        if (($month === 10 && $day >= 24) || ($month === 11 && $day <= 22)) {
            return 'scorpio';
        }
        if (($month === 11 && $day >= 23) || ($month === 12 && $day <= 21)) {
            return 'sagittarius';
        }
        if (($month === 12 && $day >= 22) || ($month === 1 && $day <= 19)) {
            return 'capricorn';
        }
        if (($month === 1 && $day >= 20) || ($month === 2 && $day <= 18)) {
            return 'aquarius';
        }

        return 'pisces';
    }

    /**
     * Get daily horoscope for a sign and date.
     *
     * @return array{sign: string, date: string, love: int, work: int, money: int, total: int, luckyColor: string, luckyItem: string, message: string}
     */
    public function getDailyHoroscope(string $sign, Carbon|\DateTimeInterface|string|null $date = null): array
    {
        $d = $date !== null ? $this->normalizeDate($date) : Carbon::today();
        $seed = $this->createSeed($sign, $d);

        $rand = function () use (&$seed): float {
            return $this->nextRandom($seed);
        };

        $love = $this->randomScore($rand);
        $work = $this->randomScore($rand);
        $money = $this->randomScore($rand);

        $avg = ($love + $work + $money) / 3;
        $total = (int) round($avg);
        $total = max(1, min(5, $total));

        $luckyColor = $this->pickFrom($rand, self::LUCKY_COLORS);
        $luckyItem = $this->pickFrom($rand, self::LUCKY_ITEMS);
        $message = $this->buildMessage($sign, $total);

        return [
            'sign' => $sign,
            'date' => $d->format('Y-m-d'),
            'love' => $love,
            'work' => $work,
            'money' => $money,
            'total' => $total,
            'luckyColor' => $luckyColor,
            'luckyItem' => $luckyItem,
            'message' => $message,
        ];
    }

    private function normalizeDate(Carbon|\DateTimeInterface|string $date): Carbon
    {
        if ($date instanceof Carbon) {
            return $date;
        }
        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date);
        }

        return Carbon::parse($date);
    }

    private function createSeed(string $sign, Carbon $date): int
    {
        $y = $date->year;
        $m = $date->month;
        $d = $date->day;
        $base = "{$sign}-{$y}-{$m}-{$d}";

        $hash = 0;
        $len = strlen($base);
        for ($i = 0; $i < $len; $i++) {
            $hash = (($hash << 5) - $hash + ord($base[$i])) & 0xFFFFFFFF;
        }

        $abs = abs($hash);

        return $abs !== 0 ? $abs : 1;
    }

    /**
     * Mulberry32-style PRNG. Modifies $state in place, returns float in [0, 1).
     */
    private function nextRandom(int &$state): float
    {
        $state = ($state + 0x6d2b79f5) & 0xFFFFFFFF;
        $x = $state;
        $x = $this->imul($x ^ ($x >> 15), $x | 1) & 0xFFFFFFFF;
        $x = ($x ^ (($x + $this->imul($x ^ ($x >> 7), $x | 61)) & 0xFFFFFFFF)) & 0xFFFFFFFF;
        $x = ($x ^ ($x >> 14)) & 0xFFFFFFFF;

        return $x / 4294967296;
    }

    private function imul(int $a, int $b): int
    {
        return ($a * $b) & 0xFFFFFFFF;
    }

    /**
     * @param callable(): float $rand
     */
    private function randomScore(callable $rand): int
    {
        return (int) floor($rand() * 5) + 1;
    }

    /**
     * @param callable(): float $rand
     * @param array<int, string> $list
     */
    private function pickFrom(callable $rand, array $list): string
    {
        $index = (int) floor($rand() * count($list));

        return $list[$index];
    }

    private function buildMessage(string $sign, int $total): string
    {
        if ($total >= 5) {
            return '最高の一日になりそう！新しいことに挑戦してみて。';
        }
        if ($total === 4) {
            return '全体的にとても良い運勢。前向きな行動が吉です。';
        }
        if ($total === 3) {
            return '悪くない一日。マイペースに過ごすと良さそう。';
        }
        if ($total === 2) {
            return '少し慎重になった方が◎ 無理は禁物。';
        }

        return '今日は休息モード。ゆっくり休んで明日に備えましょう。';
    }
}
