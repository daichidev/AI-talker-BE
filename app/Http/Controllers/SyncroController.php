<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Syncro;
use App\Models\Profile;
use App\Models\User;

class SyncroController extends Controller
{
    private $taskPoints = [
        'profile' => 1,
        'animal_fortune' => 10,
        'big5_analysis' => 20,
        'kakeai' => 20,
        'login' => 50,
        'ai_talk' => 10,
        'friend_invite_sent' => 100,
        'friend_invite_received' => 20,
        'personality_test' => 30,
        'account_link' => 30,
        'sns_link' => 30,
        'location_info' => 50,
        'cookie_on' => 30,
    ];

    private $levelThresholds = [
        1 => 0,
        2 => 3,
        3 => 7,
        4 => 13,
        5 => 22,
        6 => 36,
        7 => 57,
        8 => 89,
        9 => 137,
        10 => 209,
        11 => 317,
        12 => 479,
        13 => 722,
        14 => 1087,
        15 => 1635,
        16 => 2457,
        17 => 3690,
        18 => 5540,
        19 => 8315,
        20 => 12478,
        21 => 18723,
        22 => 28091,
        23 => 42143,
        24 => 63221,
        25 => 94838,
        26 => 142264,
        27 => 213403,
        28 => 320112,
        29 => 480176,
        30 => 720272,
        31 => 1080416,
        32 => 1620632,
        33 => 2430956,
        34 => 3646442,
        35 => 5469671,
        36 => 8204515,
        37 => 12306781,
        38 => 18460180,
        39 => 27690279,
        40 => 41535428,
        41 => 62303152,
        42 => 93454738,
        43 => 140182117,
        44 => 210273186,
        45 => 315409790,
        46 => 473114696,
        47 => 709672055,
        48 => 1064508094,
        49 => 1596762153,
        50 => 2395143242,
    ];

    public function show($userId)
    {
        $syncro = Syncro::where('user_id', $userId)->first();
        $totalPoints = $this->calculateTotalPoints($syncro);
        $limitPoints = 0;
        $bot_nickname = 'なし';

        $syncLevel = $this->calculateSyncLevel($totalPoints);
        $limitPoints = $syncLevel == 50 ? 2395143242 : $this->levelThresholds[$syncLevel + 1];

        $profile = Profile::where('user_id', $userId)->first();
        if ($profile) {
            $bot_nickname = $profile->bot_nickname ?? 'なし';
        }

        return response()->json([
            'totalPoints' => $totalPoints,
            'limitPoints' => $limitPoints,
            'syncLevel' => $syncLevel,
            'bot_nickname' => $bot_nickname,
        ]);
    }

    public function getPoint($userId)
    {
        $user = User::where('id', $userId)->first();
        $userPoint = $user ? $user->point : 0;
        
        // Calculate total points from syncro data using existing method
        $syncro = Syncro::where('user_id', $userId)->first();
        $totalPoints = $this->calculateTotalPoints($syncro);

        return response()->json([
            'point' => $userPoint + $totalPoints,
        ]);
    }

    public function addPoint(Request $request, $userId)
    {
        return $this->updateUserPoints($userId, $request->point, 'add');
    }

    public function removePoint(Request $request, $userId)
    {
        return $this->updateUserPoints($userId, $request->point, 'remove');
    }

    private function updateUserPoints($userId, $points, $operation)
    {
        $user = User::where('id', $userId)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Calculate total points from syncro data using existing method
        $syncro = Syncro::where('user_id', $userId)->first();
        $totalPoints = $this->calculateTotalPoints($syncro);

        // Update user points based on operation
        if ($operation === 'add') {
            $user->point += $points;
        } else {
            $availablePoints = $user->point + $totalPoints;
            if ($availablePoints < $points) {
                return response()->json([
                    'success' => false,
                    'error' => 'ポイントが足りません'
                ]);
            }
            
            $user->point -= $points;
        }
        $user->save();

        return response()->json([
            'success' => true,
            'point' => $user->point + $totalPoints,
        ]);
    }

    public function calculateSyncLevel($totalPoints)
    {
        $syncLevel = 1;
        foreach ($this->levelThresholds as $level => $threshold) {
            if ($totalPoints >= $threshold) {
                $syncLevel = $level;
            } else {
                break;
            }
        }
        return $syncLevel;
    }

    public function calculateTotalPoints($syncro)
    {
        if (!$syncro) {
            return 0;
        }

        $totalPoints = 0;
        $taskMap = [
            'score_profile' => 'profile',
            'done_animal_fortune' => 'animal_fortune',
            'done_big5_analysis' => 'big5_analysis',
            'done_kakeai' => 'kakeai',
            'score_login' => 'login',  
            'score_ai_talk' => 'ai_talk',
            'score_friend_invite_sent' => 'friend_invite_sent',
            'score_friend_invite_received' => 'friend_invite_received',
            'done_personality_test' => 'personality_test',
            'score_account_link' => 'account_link',
            'score_sns_link' => 'sns_link',
            'done_location_info' => 'location_info',
            'done_cookie_on' => 'cookie_on',
        ];

        foreach ($taskMap as $field => $taskKey) {
            if ($syncro->$field) {
                $points = $this->taskPoints[$taskKey] * $syncro->$field ?? 0;
                $totalPoints += is_numeric($points) ? $points : 0;
            }
        }

        return $totalPoints;
    }
}