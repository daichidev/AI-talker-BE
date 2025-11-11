<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PersonalityAssessment;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class MatchController extends Controller
{
    /** --------------------------
     * 動物占い 60タイプ: ID→日本語ラベル
     * ------------------------- */
    private const ANIMAL60_ID_TO_NAME = [
        1 => "長距離ランナーのチータ",
        2 => "社交家のたぬき",
        3 => "落ち着きのない猿",
        4 => "フットワークの軽い子守熊",
        5 => "面倒見のいい黒ひょう",
        6 => "愛情あふれる虎",
        7 => "全力疾走するチータ",
        8 => "磨き上げられたたぬき",
        9 => "大きな志をもった猿",
        10 => "母性豊かな子守熊",
        11 => "正直なこじか",
        12 => "人気者のゾウ",
        13 => "ネアカの狼",
        14 => "協調性のないひつじ",
        15 => "どっしりとした猿",
        16 => "コアラのなかの子守熊",
        17 => "強い意志をもったこじか",
        18 => "デリケートなゾウ",
        19 => "放浪の狼",
        20 => "物静かなひつじ",
        21 => "落ち着きのあるペガサス",
        22 => "強靭な翼をもつペガサス",
        23 => "無邪気なひつじ",
        24 => "クリエイティブな狼",
        25 => "穏やかな狼",
        26 => "粘り強いひつじ",
        27 => "波乱に満ちたペガサス",
        28 => "優雅なペガサス",
        29 => "チャレンジ精神旺盛なひつじ",
        30 => "順応性のある狼",
        31 => "リーダーとなるゾウ",
        32 => "しっかり者のこじか",
        33 => "活動的な子守熊",
        34 => "気分屋の猿",
        35 => "頼られると嬉しいひつじ",
        36 => "好感のもたれる狼",
        37 => "長距まっしぐらに突き進むゾウ",
        38 => "華やかなこじか",
        39 => "夢とロマンの子守熊",
        40 => "尽す猿",
        41 => "大器晩成のたぬき",
        42 => "足腰の強いチータ",
        43 => "動きまわる虎",
        44 => "情熱的な黒ひょう",
        45 => "サービス精神旺盛な子守熊",
        46 => "守りの猿",
        47 => "人間味あふれるたぬき",
        48 => "品格のあるチータ",
        49 => "ゆったりとした悠然の虎",
        50 => "落ち込みの激しい黒ひょう",
        51 => "我が道を行くライオン",
        52 => "統率力のあるライオン",
        53 => "感情豊かな黒ひょう",
        54 => "楽天的な虎",
        55 => "パワフルな虎",
        56 => "気どらない黒ひょう",
        57 => "感情的なライオン",
        58 => "傷つきやすいライオン",
        59 => "束縛を嫌う黒ひょう",
        60 => "慈悲深い虎",
    ];

    /** 正規化（全角/空白/大小/ひらカナ揺れを弱く吸収） */
    private static function normJP(?string $s): string
    {
        $s = $s ?? '';
        $s = mb_convert_kana($s, 'KVAS'); // 半角/全角/濁点など
        $s = preg_replace('/\s+/u', '', $s);
        return mb_strtolower($s ?? '');
    }

    /** ベース動物（種族）抽出のための正規表現マップ */
    private const SPECIES_PATTERNS = [
        ['チータ', '/(ﾁｰ?ﾀ|チータ|ﾁﾀ|ﾁｰﾀ|ﾁｰﾀｰ|チーター)/iu'],
        ['たぬき', '/(たぬき|狸|タヌキ)/iu'],
        ['猿', '/(猿|サル)/iu'],
        ['子守熊', '/(子守熊|コアラ)/iu'],
        ['黒ひょう', '/(黒ひょう|黒豹|黒ﾋｮｳ|ｸﾛﾋｮｳ)/iu'],
        ['虎', '/(虎|トラ)/iu'],
        ['こじか', '/(こじか|仔鹿|子鹿)/iu'],
        ['ゾウ', '/(ゾウ|象)/iu'],
        ['狼', '/(狼|オオカミ|おおかみ)/iu'],
        ['ひつじ', '/(ひつじ|羊)/iu'],
        ['ペガサス', '/(ﾍﾟｶﾞｻｽ|ペガサス)/iu'],
        ['ライオン', '/(ライオン)/iu'],
    ];

    /** 逆引き: 名前→ID（完全一致 or 部分一致） */
    private static function animal60NameToId(?string $s): ?int
    {
        if (!$s) return null;
        $key = self::normJP($s);
        // 完全一致
        foreach (self::ANIMAL60_ID_TO_NAME as $id => $name) {
            if (self::normJP($name) === $key) return (int)$id;
        }
        // 部分一致
        foreach (self::ANIMAL60_ID_TO_NAME as $id => $name) {
            if (str_contains(self::normJP($name), $key)) return (int)$id;
        }
        return null;
    }

    /** 英語/短縮も含む “ベース動物（種族）” を抽出。未判定は null */
    private static function extractSpecies(?string $s): ?string
    {
        if (!$s) return null;
        foreach (self::SPECIES_PATTERNS as [$label, $re]) {
            if (preg_match($re, $s)) return $label;
        }
        $lower = mb_strtolower($s);
        if (preg_match('/koala/i', $lower)) return '子守熊';
        if (preg_match('/wolf/i', $lower)) return '狼';
        if (preg_match('/tiger/i', $lower)) return '虎';
        if (preg_match('/sheep/i', $lower)) return 'ひつじ';
        if (preg_match('/lion/i', $lower)) return 'ライオン';
        if (preg_match('/cheetah/i', $lower)) return 'チータ';
        if (preg_match('/elephant/i', $lower)) return 'ゾウ';
        if (preg_match('/pegasus/i', $lower)) return 'ペガサス';
        if (preg_match('/(deer|fawn)/i', $lower)) return 'こじか';
        if (preg_match('/(raccoon|tanuki)/i', $lower)) return 'たぬき';
        if (preg_match('/monkey/i', $lower)) return '猿';
        if (preg_match('/(panther|leopard)/i', $lower)) return '黒ひょう';
        return null;
    }

    /** 利便: animal 入力（ID/ラベル/英語）から {id, label, species} を返す */
    private static function normalizeAnimal60($input): array
    {
        if ($input === null) return ['id' => null, 'label' => null, 'species' => null];
        if (is_int($input)) {
            $id = ($input >= 1 && $input <= 60) ? $input : null;
            $label = $id ? (self::ANIMAL60_ID_TO_NAME[$id] ?? null) : null;
            return ['id' => $id, 'label' => $label, 'species' => self::extractSpecies($label)];
        }
        $id = self::animal60NameToId((string)$input);
        $label = $id ? (self::ANIMAL60_ID_TO_NAME[$id] ?? null) : (string)$input;
        return ['id' => $id, 'label' => $label, 'species' => self::extractSpecies($label)];
    }

    // --- Utils ---
    private static function clamp(float $v, float $lo = 0, float $hi = 100): float
    {
        return max($lo, min($hi, $v));
    }
    private static function clamp01(float $x): float
    {
        return max(0, min(1, $x));
    }
    private static function pct(float $x): float
    {
        return self::clamp01($x) * 100;
    }

    private static function l1Distance(array $a = null, array $b = null, array $keys = []): float
    {
        $sum = 0.0;
        foreach ($keys as $k) {
            $va = $a[$k] ?? 0;
            $vb = $b[$k] ?? 0;
            $sum += abs($va - $vb);
        }
        return $sum;
    }

    private static function jaccard(array $arrA = [], array $arrB = []): float
    {
        $A = array_unique($arrA);
        $B = array_unique($arrB);
        if (!count($A) && !count($B)) return 1.0;
        $inter = count(array_intersect($A, $B));
        $uni = count(array_unique(array_merge($A, $B)));
        return $uni ? ($inter / $uni) : 0.0;
    }

    private static function haversineKm(?array $a, ?array $b): ?float
    {
        if (!$a || !$b) return null;
        if (!isset($a['lat'], $a['lng'], $b['lat'], $b['lng'])) return null;
        $toRad = fn($d) => $d * M_PI / 180;
        $R = 6371.0;
        $dLat = $toRad($b['lat'] - $a['lat']);
        $dLng = $toRad($b['lng'] - $a['lng']);
        $s = pow(sin($dLat / 2), 2) + cos($toRad($a['lat'])) * cos($toRad($b['lat'])) * pow(sin($dLng / 2), 2);
        return 2 * $R * asin(min(1, sqrt($s)));
    }

    // --- Converters ---
    private const ENNEAGRAM_NUM_TO_JP = [1 => '完璧主義者', 2 => '援助者', 3 => '達成者', 4 => '芸術家', 5 => '研究者', 6 => '忠実家', 7 => '楽天家', 8 => '挑戦者', 9 => '平和主義者'];
    private const ALIASES = [
        1 => ['1', 'type1', 't1', '完璧主義者', '改革する人', '改革者', 'reformer', 'perfectionist'],
        2 => ['2', 'type2', 't2', '援助者', 'ヘルパー', '助ける人', 'helper', 'giver'],
        3 => ['3', 'type3', 't3', '達成者', '達成する人', 'achiever', 'performer'],
        4 => ['4', 'type4', 't4', '芸術家', '個性派', 'ロマン派', 'individualist', 'romantic'],
        5 => ['5', 'type5', 't5', '研究者', '観察者', 'investigator', 'observer'],
        6 => ['6', 'type6', 't6', '忠実家', '忠実な人', '忠誠家', 'loyalist', 'skeptic'],
        7 => ['7', 'type7', 't7', '楽天家', '熱中する人', '冒険家', 'enthusiast', 'adventurer'],
        8 => ['8', 'type8', 't8', '挑戦者', '統率者', 'challenger', 'protector'],
        9 => ['9', 'type9', 't9', '平和主義者', '調停者', '仲介者', 'peacemaker', 'mediator'],
    ];

    private static function normalizeText(string $s): string
    {
        $s = mb_convert_kana($s, 'asKV');
        $s = trim(mb_strtolower($s));
        $s = preg_replace('/\s+/u', '', $s);
        return $s;
    }

    private static function aliasToNum(): array
    {
        static $map = null;
        if ($map !== null) return $map;
        $map = [];
        foreach (self::ALIASES as $num => $names) {
            foreach ($names as $n) $map[self::normalizeText($n)] = (int)$num;
            for ($w = 1; $w <= 9; $w++) if ($w !== (int)$num) $map[self::normalizeText($num . 'w' . $w)] = (int)$num;
        }
        return $map;
    }

    private static function enneagramNameToNum($input): ?int
    {
        if ($input === null) return null;
        if (is_numeric($input)) {
            $n = (int)floor($input);
            return ($n >= 1 && $n <= 9) ? $n : null;
        }
        $norm = preg_replace('/[()（）［］\[\]‐\-‒–—―＿_]/u', '', self::normalizeText((string)$input));
        if (preg_match('/^(?:type|t)?([1-9])/u', $norm, $m)) return (int)$m[1];
        $map = self::aliasToNum();
        return $map[$norm] ?? null;
    }

    private static function discVectorToLetter(?array $obj): ?string
    {
        if (!$obj) return null;
        arsort($obj); // 降順
        $keys = array_keys($obj);
        return $keys[0] ?? null;
    }

    private const RIASEC_ORDER = ['R', 'I', 'A', 'S', 'E', 'C'];
    private static function riasecTop2(?array $obj): ?string
    {
        if (!$obj) return null;
        $arr = [];
        foreach (self::RIASEC_ORDER as $k) if (array_key_exists($k, $obj)) $arr[] = [$k, $obj[$k]];
        usort($arr, function ($a, $b) {
            if ($a[1] === $b[1]) return array_search($a[0], self::RIASEC_ORDER) <=> array_search($b[0], self::RIASEC_ORDER);
            return $b[1] <=> $a[1];
        });
        if (!count($arr)) return null;
        $top2 = array_map(fn($x) => $x[0], array_slice($arr, 0, 2));
        $top2 = array_values(array_unique($top2));
        return implode('', $top2);
    }

    private static function big5LikertToPct($arr): ?array
    {
        if (!is_array($arr) || count($arr) < 5) return null;
        $toPct = fn($v) => self::clamp((($v - 1) / 4) * 100);
        return [
            'O' => $toPct($arr[0]),
            'C' => $toPct($arr[1]),
            'E' => $toPct($arr[2]),
            'A' => $toPct($arr[3]),
            'N' => $toPct($arr[4])
        ];
    }

    private static function normalizeHobbies(array $list = []): array
    {
        $out = [];
        foreach ($list as $s) {
            $s = trim(mb_strtolower(mb_convert_kana((string)$s, 'KVas')));
            if ($s !== '') $out[$s] = true;
        }
        return array_keys($out);
    }

    private static function normalizeUser(array $raw): array
    {
        $tokens = null;
        if (isset($raw['enneagram']) && is_string($raw['enneagram'])) {
            $tokens = array_values(array_filter(preg_split('/[\s,]+/u', $raw['enneagram'])));
        } elseif (isset($raw['enneagram']) && is_array($raw['enneagram'])) {
            $tokens = $raw['enneagram'];
        }
        return [
            'mbti' => isset($raw['MBTI']) ? strtoupper((string)$raw['MBTI']) : (isset($raw['mbti']) ? strtoupper((string)$raw['mbti']) : null),
            'enneagram' => $tokens ? self::enneagramNameToNum($tokens[0]) : (isset($raw['enneagram']) && is_numeric($raw['enneagram']) ? (int)$raw['enneagram'] : null),
            'disc' => isset($raw['disc']) && is_array($raw['disc']) ? self::discVectorToLetter($raw['disc']) : (isset($raw['disc']) ? $raw['disc'] : null),
            'socionics' => $raw['Socionics'] ?? ($raw['socionics'] ?? null),
            'big5' => isset($raw['big 5']) && is_array($raw['big 5']) ? self::big5LikertToPct($raw['big 5']) : ($raw['big5'] ?? null),
            'riasec' => isset($raw['RIASEC']) && is_array($raw['RIASEC']) ? self::riasecTop2($raw['RIASEC']) : ($raw['riasec'] ?? null),
            'hobbies' => isset($raw['hobbies']) && is_array($raw['hobbies']) ? self::normalizeHobbies($raw['hobbies']) : [],
            'age' => isset($raw['age']) && is_numeric($raw['age']) ? (int)$raw['age'] : null,
            'living_place' => $raw['living_place'] ?? null,
            'blood' => isset($raw['blood']) ? strtoupper((string)$raw['blood']) : null,
            'animal' => $raw['animal'] ?? null,
            'job' => $raw['job'] ?? null,
            'gender' => $raw['gender'] ?? null,
            'birthplace' => $raw['birthplace'] ?? null,
        ];
    }

    // --- Scoring ---
    private const WEIGHTS = ['mbti' => .25, 'enneagram' => .10, 'socionics' => .10, 'disc' => .05, 'big5' => .15, 'riasec' => .05, 'hobbies' => .05, 'age' => .05, 'location' => .05, 'blood' => .04, 'animal' => .06];

    private const MBTI_COMP = [
        'ENFP' => ['INFJ' => 95, 'INTJ' => 90, 'ENTP' => 78, 'ISFJ' => 60, 'ISTJ' => 55, 'ESTJ' => 45, 'ISTP' => 45],
        'INFJ' => ['ENFP' => 95, 'ENTP' => 88, 'ISFJ' => 70, 'ESTP' => 50],
        'INTJ' => ['ENFP' => 90, 'ENTP' => 85, 'ISFJ' => 62, 'ESFP' => 58, 'ESFJ' => 50],
        'ENTP' => ['INFJ' => 88, 'INTJ' => 85, 'ISFJ' => 60, 'ESFJ' => 60],
        'ISFJ' => ['ENFP' => 60, 'ENTP' => 60, 'INTJ' => 62, 'ISTJ' => 75, 'ESFP' => 80],
        'ISTJ' => ['ESFP' => 88, 'ISFJ' => 75, 'ENFP' => 55, 'ENFJ' => 58],
        'ESFP' => ['ISTJ' => 88, 'INTJ' => 58, 'ISFJ' => 80],
    ];

    private const ENNEA_CENTER = [1 => 'gut', 8 => 'gut', 9 => 'gut', 2 => 'heart', 3 => 'heart', 4 => 'heart', 5 => 'head', 6 => 'head', 7 => 'head'];
    private const ENNEA_BONUS = ['2-9' => true, '9-2' => true, '1-7' => true, '7-1' => true, '3-6' => true, '6-3' => true, '4-5' => true, '5-4' => true];
    private const DISC_COMPLEMENTS = ['D-S' => true, 'S-D' => true, 'I-C' => true, 'C-I' => true];
    private const BLOOD_COMP = [
        'A' => ['A' => 75, 'O' => 85, 'B' => 72, 'AB' => 70],
        'O' => ['A' => 85, 'O' => 75, 'B' => 78, 'AB' => 72],
        'B' => ['A' => 72, 'O' => 78, 'B' => 75, 'AB' => 70],
        'AB' => ['A' => 70, 'O' => 72, 'B' => 70, 'AB' => 75],
    ];

    /** 種族ベース相性 */
    private const SPECIES_COMP = [
        '狼' => ['子守熊' => 86, 'ひつじ' => 80, '狼' => 75, 'たぬき' => 78, '猿' => 72],
        '子守熊' => ['狼' => 86, '虎' => 80, '子守熊' => 75, 'ライオン' => 72],
        '虎' => ['子守熊' => 80, '鹿/こじか' => 78, '虎' => 75, '黒ひょう' => 70],
        'ひつじ' => ['狼' => 80, '猿' => 78, 'ひつじ' => 75, 'たぬき' => 74],
        'チータ' => ['ゾウ' => 80, 'ひつじ' => 76, 'チータ' => 75],
        '猿' => ['ひつじ' => 78, '猿' => 75, 'たぬき' => 74],
        'こじか' => ['虎' => 78, 'ライオン' => 74, 'こじか' => 75, '子守熊' => 76],
        'ゾウ' => ['チータ' => 80, 'ゾウ' => 75, 'ライオン' => 72],
        '黒ひょう' => ['虎' => 70, '黒ひょう' => 75, 'ライオン' => 74, '子守熊' => 76],
        'ペガサス' => ['ペガサス' => 75, '狼' => 74, '子守熊' => 74, 'ライオン' => 70],
        'ライオン' => ['子守熊' => 72, 'こじか' => 74, 'ライオン' => 75, '黒ひょう' => 74],
        'たぬき' => ['猿' => 74, 'ひつじ' => 74, 'たぬき' => 75, '狼' => 78],
    ];

    private static function styleTweak(string $labelA = '', string $labelB = ''): int
    {
        $l = $labelA . ' ' . $labelB;
        $fast = preg_match('/(全力疾走|長距離|足腰の強い|まっしぐら|動きまわる)/u', $l);
        $slow = preg_match('/(ゆったり|物静か|穏やか|デリケート)/u', $l);
        $creative = preg_match('/(クリエイティブ|夢とロマン|華やか)/u', $l);
        $leader = preg_match('/(リーダー|統率力)/u', $l);
        $bonus = 0;
        if ($fast && $slow) $bonus += 3;
        if ($creative && $leader) $bonus += 2;
        $neg = preg_match('/(落ち込み|傷つきやすい|協調性のない|波乱)/u', $l);
        if ($neg) $bonus -= 2;
        return $bonus;
    }

    private static function scoreAnimal60($a, $b): int
    {
        if (!$a || !$b) return 0;
        $A = self::normalizeAnimal60($a);
        $B = self::normalizeAnimal60($b);
        if (!$A['species'] || !$B['species']) return 0;
        $base = self::SPECIES_COMP[$A['species']][$B['species']] ?? self::SPECIES_COMP[$B['species']][$A['species']] ?? 70;
        $tweak = self::styleTweak($A['label'] ?? '', $B['label'] ?? '');
        return (int) self::clamp($base + $tweak, 0, 100);
    }

    private static function normalizeR(string $s = ''): string
    {
        $s = strtoupper(preg_replace('/[^RIASEC]/', '', $s));
        $chars = array_unique(str_split($s));
        return implode('', $chars);
    }

    private static function scoreMBTI(?string $a, ?string $b): int
    {
        if (!$a || !$b) return 0;
        $A = strtoupper($a);
        $B = strtoupper($b);
        $d = self::MBTI_COMP[$A][$B] ?? (self::MBTI_COMP[$B][$A] ?? null);
        if (is_numeric($d)) return (int)$d;
        $s = 0;
        if (($A[1] ?? '') === ($B[1] ?? '')) $s += 30;
        if (($A[2] ?? '') === ($B[2] ?? '')) $s += 30;
        if (($A[3] ?? '') === ($B[3] ?? '')) $s += 15;
        if (($A[0] ?? '') !== ($B[0] ?? '')) $s += 15;
        return (int) min(100, max(0, 40 + $s));
    }

    private static function scoreEnneagram(?int $a, ?int $b): int
    {
        if (!$a || !$b) return 0;
        if ($a === $b) return 72;
        $key = $a . '-' . $b;
        if (isset(self::ENNEA_BONUS[$key])) return 88;
        $ca = self::ENNEA_CENTER[$a] ?? null;
        $cb = self::ENNEA_CENTER[$b] ?? null;
        if ($ca && $cb && $ca !== $cb) return 80;
        return 68;
    }

    private static function scoreDISC(?string $a, ?string $b): int
    {
        if (!$a || !$b) return 0;
        if ($a === $b) return 75;
        return isset(self::DISC_COMPLEMENTS[$a . '-' . $b]) ? 85 : 72;
    }

    private static function socioFamily(string $t = ''): string
    {
        $u = strtoupper($t);
        if ($u === '') return 'UNK';
        if (preg_match('/[IE]I$/', $u)) return 'NF/NT';
        if (preg_match('/[IE]E$/', $u)) return 'SF/ST';
        return 'UNK';
    }

    private static function scoreSocionics(?string $a, ?string $b): int
    {
        if (!$a || !$b) return 0;
        $fa = self::socioFamily($a);
        $fb = self::socioFamily($b);
        if ($fa === 'UNK' || $fb === 'UNK') return 0;
        return $fa !== $fb ? 85 : 72;
    }

    private static function scoreBig5(?array $A, ?array $B): int
    {
        if (!$A || !$B) return 0;
        $dist = self::l1Distance($A, $B, ['O', 'C', 'E', 'A', 'N']);
        $sim = 1 - $dist / 500;
        return (int) round(self::pct($sim));
    }

    private static function scoreRIASEC(?string $a, ?string $b): int
    {
        if (!$a || !$b) return 0;
        $A = self::normalizeR($a);
        $B = self::normalizeR($b);
        if (!$A || !$B) return 0;
        return (int) round(self::pct(self::jaccard(str_split($A), str_split($B))));
    }

    private static function scoreHobbies(array $a = null, array $b = null): int
    {
        return (!($a && $b) || !count($a) || !count($b)) ? 0 : (int) round(self::pct(self::jaccard($a, $b)));
    }

    private static function scoreAge($a, $b): int
    {
        if (!is_numeric($a) || !is_numeric($b)) return 0;
        $d = abs((int)$a - (int)$b);
        $sim = 1 / (1 + exp(($d - 2) / 2));
        return (int) round(self::pct($sim));
    }

    private static function scoreLocation(?array $A, ?array $B): int
    {
        if (!$A || !$B) return 0;
        $km = self::haversineKm($A, $B);
        if ($km === null) return 0;
        $sim = 1 / (1 + log10(1 + $km));
        return (int) round(self::pct($sim));
    }

    private static function scoreBlood(?string $a, ?string $b): int
    {
        if (!$a || !$b) return 0;
        return (int) (self::BLOOD_COMP[$a][$b] ?? 0);
    }

    private static function scoreAnimal($a, $b): int
    {
        return self::scoreAnimal60($a, $b);
    }

    private static function matchTwo(array $A, array $B): array
    {
        $parts = [
            'mbti' => self::clamp01(self::scoreMBTI($A['mbti'] ?? null, $B['mbti'] ?? null) / 100),
            'enneagram' => self::clamp01(self::scoreEnneagram($A['enneagram'] ?? null, $B['enneagram'] ?? null) / 100),
            'socionics' => self::clamp01(self::scoreSocionics($A['socionics'] ?? null, $B['socionics'] ?? null) / 100),
            'disc' => self::clamp01(self::scoreDISC($A['disc'] ?? null, $B['disc'] ?? null) / 100),
            'big5' => self::clamp01(self::scoreBig5($A['big5'] ?? null, $B['big5'] ?? null) / 100),
            'riasec' => self::clamp01(self::scoreRIASEC($A['riasec'] ?? null, $B['riasec'] ?? null) / 100),
            'hobbies' => self::clamp01(self::scoreHobbies($A['hobbies'] ?? [], $B['hobbies'] ?? []) / 100),
            'age' => self::clamp01(self::scoreAge($A['age'] ?? null, $B['age'] ?? null) / 100),
            'location' => self::clamp01(self::scoreLocation($A['living_place'] ?? null, $B['living_place'] ?? null) / 100),
            'blood' => self::clamp01(self::scoreBlood($A['blood'] ?? null, $B['blood'] ?? null) / 100),
            'animal' => self::clamp01(self::scoreAnimal($A['animal'] ?? null, $B['animal'] ?? null) / 100),
        ];

        $score = 0.0;
        foreach (self::WEIGHTS as $k => $w) $score += ($parts[$k] ?? 0) * $w;
        $score *= 100;

        $reasons = [];
        if (($parts['mbti'] ?? 0) > 0.8) $reasons[] = 'MBTIの相性がとても良い組み合わせです。';
        if (($parts['big5'] ?? 0) > 0.75) $reasons[] = 'Big5で価値観が近いです。';
        if (($parts['riasec'] ?? 0) > 0.7) $reasons[] = '仕事/適性の傾向が似ています。';
        if (($parts['hobbies'] ?? 0) > 0.6) $reasons[] = '共通の趣味が多いです。';
        if (($parts['age'] ?? 0) > 0.7) $reasons[] = '年齢差がちょうど良いです。';
        if (($parts['location'] ?? 0) > 0.7) $reasons[] = '生活圏が近く会いやすいです。';
        if (($parts['enneagram'] ?? 0) > 0.75) $reasons[] = 'エニアグラムの中心が補完的です。';
        if (($parts['disc'] ?? 0) > 0.75) $reasons[] = 'DISCで役割が補完的です。';
        if (($parts['socionics'] ?? 0) > 0.75) $reasons[] = 'ソシオニクス的に気質が補完的です。';
        if (($parts['blood'] ?? 0) > 0.75 || ($parts['animal'] ?? 0) > 0.75) $reasons[] = '占い的な相性も良好です。';

        $breakdown = [];
        foreach ($parts as $k => $v) $breakdown[$k] = (int) round($v * 100);

        return [
            'score' => (int) round($score),
            'breakdown' => $breakdown,
            'reasons' => $reasons,
        ];
    }
    /**
     * 候補者プロフィールを配列で取得する
     *
     * @param  int   $userId        // 自分（除外対象）
     * @param  int   $limit         // 取得上限
     * @param  array $filters       // 任意の絞り込み（例: ['gender' => 'female'] など）
     * @return array                // candidates配列（各要素が $user_data と同型）
     */
    function getCandidateProfiles(int $userId, int $limit = 50, array $filters = []): array
    {
        // 取得クエリ（必要に応じて where 条件を追加）
        $query = User::with(['anketos', 'profile', 'personalityTest'])
            ->where('id', '!=', $userId);
        // 例: 性別フィルタ（profile.gender or anketo.gender）
        if (!empty($filters['gender'])) {
            $gender = $filters['gender'];
            $query->where(function ($q) use ($gender) {
                $q->whereHas('profile', fn($qq) => $qq->where('gender', $gender))
                    ->orWhereHas('anketos', fn($qq) => $qq->where('gender', $gender));
            });
        }

        if (!empty($filters['age_min']) || !empty($filters['age_max'])) {
            $min = $filters['age_min'] ?? 18;
            $max = $filters['age_max'] ?? 120;
        }

        $users = $query
            ->inRandomOrder()  // ランダムなら
            ->limit($limit)
            ->get();

        $userIds = $users->pluck('id')->toArray();
        $assessmentsByUser = PersonalityAssessment::whereIn('user_id', $userIds)
            ->get(['user_id','personality_type','result'])
            ->pluck('result', 'personality_type')
            ->toArray();
        return $assessmentsByUser;
        $candidates = [];

        foreach ($users as $u) {
            // パーソナリティ結果（タイプ => result の連想配列）
            // モデルに relation: personalityAssessments がある前提
            $personalities = $assessmentsByUser[$u->id];

            // DISC / RIASEC は文字列JSON or 配列の両対応
            $discRaw   = $personalities['DISC']   ?? null;
            $riasecRaw = $personalities['RIASEC'] ?? null;

            $disc   = is_string($discRaw)   ? json_decode($discRaw, true)   : (is_array($discRaw)   ? $discRaw   : null);
            $riasec = is_string($riasecRaw) ? json_decode($riasecRaw, true) : (is_array($riasecRaw) ? $riasecRaw : null);

            // Big5: personalityTest->mean_values_array が JSON文字列想定
            $big5 = null;
            if ($u->personalityTest && !empty($u->personalityTest->mean_values_array)) {
                $big5 = json_decode($u->personalityTest->mean_values_array, true);
            }

            // 年齢（profile優先→anketosフォールバック）
            $birth = $u->profile->birthdate ?? $u->anketos->birthdate ?? null;
            $age = null;
            if ($birth) {
                try {
                    $age = Carbon::parse($birth)->age;
                } catch (\Throwable $e) {
                    $age = null;
                }
            }

            // hobbies: 「, 」区切り or 「,」区切り両対応 & トリム
            $hobbyStr = $u->profile->hobby ?? $u->anketos->hobby ?? '';
            $hobbies = array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/u', $hobbyStr ?? ''))));

            $candidates[] = [
                // スコアリング側で使い回しやすいよう、IDなども載せておくと便利
                'id'    => $u->id,
                'name'  => $u->name ?? null,

                'animal' => $u->anketos->animal_fortune_telling ?? null,
                'job'    => $u->anketos->job ?? ($u->profile->job ?? null),
                'hobbies' => $hobbies,
                'age'    => $age,

                // living_place はそのまま文字列でもOK（距離スコアはlat/lngがあれば有効）
                // もし住所→座標変換が必要なら、別途ジオコーディングして {lat,lng} を入れてください。
                'living_place' => $u->profile->address ?? ($u->anketos->address ?? null),

                'blood'  => $u->profile->blood_type ?? ($u->anketos->blood_type ?? null),

                // パーソナリティ
                'MBTI'      => $personalities['MBTI']      ?? null,
                'enneagram' => $personalities['Enneagram'] ?? null,
                'disc'      => $disc,
                'RIASEC'    => $riasec,
                'socionics' => $personalities['Socionics'] ?? null,
                'big5'      => $big5,
            ];
        }

        // 年齢レンジの後段フィルタ（必要なら）
        if (!empty($filters['age_min']) || !empty($filters['age_max'])) {
            $min = $filters['age_min'] ?? 18;
            $max = $filters['age_max'] ?? 120;
            $candidates = array_values(array_filter($candidates, function ($c) use ($min, $max) {
                return is_numeric($c['age'] ?? null) ? ($c['age'] >= $min && $c['age'] <= $max) : false;
            }));
        }

        return $candidates;
    }
    /**
     * POST /api/match/rank
     *
     * Body JSON:
     * {
     *   "you": { ... プロフィール ... },
     *   "candidates": [ { ... }, { ... } ]
     * }
     */
    public function rank(Request $req): JsonResponse
    {
        $req->validate([
            'user_id' => 'required|integer',
        ]);
        $userId = $req->user_id;
        $user = User::with(['anketos', 'profile', 'personalityTest'])->find($userId);

        $personalities = PersonalityAssessment::where('user_id', $userId)
            ->get(['personality_type', 'result'])
            ->pluck('result', 'personality_type')
            ->toArray();
        $user_data = [
            'animal' => $user->anketos->animal_fortune_telling ?? null,
            'job' => $user->anketos->job ?? $user->profile->job ?? null,
            'hobbies' => explode(', ', $user->profile->hobby ?? $user->anketos->hobby) ?? [],
            'age' => $user->profile->birthdate ? date('Y') - date('Y', strtotime($user->profile->birthdate)) : ($user->anketos->birthdate ? date('Y') - date('Y', strtotime($user->anketos->birthdate)) : null),
            'living_place' => $user->profile->address ?? $user->anketos->address ?? null,
            'blood' => $user->profile->blood_type ?? $user->anketos->blood_type ?? null,
            'MBTI' => $personalities['MBTI'] ?? null,
            'enneagram' => $personalities['Enneagram'] ?? null,
            'disc' => json_decode($personalities['DISC'], true) ?? null,
            'RIASEC' => json_decode($personalities['RIASEC'], true) ?? null,
            'socionics' => $personalities['Socionics'] ?? null,
            'big5' => json_decode($user->personalityTest->mean_values_array, true) ?? null,
            'candidates' => $this->getCandidateProfiles($userId, 10),
        ];
        return response()->json(['user' => $user_data]);

        $youRaw = (array) $req->input('you', []);
        $candsRaw = (array) $req->input('candidates', []);

        $you = self::normalizeUser($youRaw);
        $out = [];
        foreach ($candsRaw as $p) {
            $B = self::normalizeUser((array)$p);
            $res = self::matchTwo($you, $B);
            $out[] = [
                'profile' => $B,
                'score' => $res['score'],
                'breakdown' => $res['breakdown'],
                'reasons' => $res['reasons'],
            ];
        }

        usort($out, fn($a, $b) => $b['score'] <=> $a['score']);
        return response()->json(['you' => $you, 'results' => $out]);
    }
}
