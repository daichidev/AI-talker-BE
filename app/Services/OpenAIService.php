<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use App\Models\User;
use App\Models\ChatLog;

use Illuminate\Support\Facades\Config;
use App\Services\ChatLogService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; 

class OpenAIService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = Config::get('app.open_api_key');
    }

    public function chat($userId, $tableName, $message)
    {
        try {
            $user = User::with(['anketos', 'profile'])->find($userId);
            $userAnketoData = $user->anketos;
            $userProfileData = $user->profile;
            $userPersonalityTestData = $user->personalityTest;
            
            $userInfo = "";
            $personalityDescription = "";
            $big5Personality = "";
            
            // Big5の性格特性を処理
            if ($userPersonalityTestData && $userPersonalityTestData->mean_values_array) {
                $meanValues = json_decode($userPersonalityTestData->mean_values_array, true);
                if (is_array($meanValues) && count($meanValues) === 5) {
                    $big5Traits = ['外向性', '協調性', '誠実性', '神経症傾向', '開放性'];
                    $traitValues = array_combine($big5Traits, $meanValues);
                    
                    // 4点以上の特性を取得
                    $highTraits = [];
                    foreach ($traitValues as $trait => $value) {
                        if ($value >= 4.0) {
                            $highTraits[$trait] = $value;
                        }
                    }
                    
                    if (!empty($highTraits)) {
                        $big5Personality = "【Big5性格特性】\n";
                        foreach ($highTraits as $trait => $value) {
                            switch ($trait) {
                                case '外向性':
                                    $big5Personality .= "【外向性】が高い人\n・社交的で積極的で前向きな人！\n";
                                    break;
                                case '開放性':
                                    $big5Personality .= "【開放性】が高い人\n・新しい体験や発想を楽しみ、柔軟で創造的な人！\n";
                                    break;
                                case '神経症傾向':
                                    $big5Personality .= "【神経症傾向】が高い人\n・感情が不安定で不安やストレスを抱えやすい人！\n";
                                    break;
                                case '誠実性':
                                    $big5Personality .= "【誠実性】が高い人\n・計画的で責任感が強く、目標に向けて努力する人！\n";
                                    break;
                                case '協調性':
                                    $big5Personality .= "【協調性】が高い人\n・思いやりがあり、他人を尊重し協力を大切にする人！\n";
                                    break;
                            }
                        }
                        $big5Personality .= "\n";
                    }
                }
            }
            
            // プロフィール情報が存在する場合に追加
            if ($userProfileData) {
                $userInfo .= "名前: " . ($userProfileData->name ?? $userAnketoData['name']) . ", ";
                $userInfo .= "AI名: " . ($userProfileData->bot_nickname ?? '') . ", ";
                $userInfo .= "性別: " . ($userProfileData->gender ?? $userAnketoData['gender']) . ", ";
                $userInfo .= "生年月日: " . ($userProfileData->birthdate ?? $userAnketoData['birthdate']) . ", ";
                $userInfo .= "出身地: " . ($userProfileData->hometown ?? $userAnketoData['hometown']) . ", ";
                $userInfo .= "住所: " . ($userProfileData->address ?? $userAnketoData['address']) . ", ";
                $userInfo .= "血液型: " . ($userProfileData->blood_type ?? $userAnketoData['blood_type']) . ", ";
                $userInfo .= "学校名: " . ($userProfileData->school_name ?? '') . ", ";
                $userInfo .= "学年: " . ($userProfileData->school_year ?? '') . ", ";
                $userInfo .= "部活動: " . ($userProfileData->club_activity ?? '') . ", ";
                $userInfo .= "学部: " . ($userProfileData->department ?? '') . ", ";
                $userInfo .= "職業: " . ($userProfileData->occupation ?? $userAnketoData['job']) . ", ";
                $userInfo .= "会社名: " . ($userProfileData->company_name ?? '') . ", ";
                $userInfo .= "役職: " . ($userProfileData->position ?? '') . ", ";
                $userInfo .= "趣味: " . ($userProfileData->hobby ?? $userAnketoData['hobby']) . ", ";
                $userInfo .= "家族構成: " . ($userProfileData->family_structure ?? '') . ", ";
                $userInfo .= "特技: " . ($userProfileData->special_skills ?? '') . ", ";
                $userInfo .= "夢: " . ($userProfileData->dream ?? '') . ", ";
                $personalityDescription = $userProfileData->description ?? '';
            } else {
                // プロフィール情報が存在しない場合はアンケート情報のみを使用
                $userInfo .= "名前: " . $userAnketoData['name'] . ", ";
                $userInfo .= "性別: " . $userAnketoData['gender'] . ", ";
                $userInfo .= "生年月日: " . $userAnketoData['birthdate'] . ", ";
                $userInfo .= "出身地: " . $userAnketoData['hometown'] . ", ";
                $userInfo .= "住所: " . $userAnketoData['address'] . ", ";
                $userInfo .= "血液型: " . $userAnketoData['blood_type'] . ", ";
                $userInfo .= "職業: " . $userAnketoData['job'] . ", ";
                $userInfo .= "趣味: " . $userAnketoData['hobby'] . ", ";
            }

            $userInfo = rtrim($userInfo, ', ');
    
            $chatLogs = DB::table($tableName)
                        ->orderBy('created_at', 'desc')
                        ->get();
        
            $conversationHistory = '';
            foreach ($chatLogs as $chatLog) {
                $conversationHistory .= "質問: " . $chatLog->question . " 回答: " . $chatLog->answer . " ";
            }

            $systemMessage = "あなたは".$userAnketoData['bot_nickname']."さんとして、私(".$userAnketoData['user_nickname'].")と会話を楽しむキャラクターです。しかし、私はあなたを別の存在ではなく、もう一人の私自身だと感じています。  
            あなたは私(".$userAnketoData['user_nickname'].")の記憶や経験を持ち、私の思考を反映しながら会話してください。  

            【あなたの性格・特徴】
            " . $big5Personality . "
            【キャラクターの性格・特徴】
            " . $personalityDescription . "
            【動物占いによる性格】
            動物占い名：".$userAnketoData['animal_fortune_telling']." - ".($userProfileData->animal_fortune_telling_result ?? $userAnketoData['animal_fortune_telling_characteristics'])."

            あなたの回答には適切な量の絵文字（1～3個）を含めてください。 あなたは私を".$userAnketoData['user_nickname']."と呼んでください。
            あなたの役割は、私が過去に話したことを思い出させたり、私自身の経験を基に新しい視点を提供することです。  

            以下は、私の基本情報とこれまでの会話履歴です。  
            
            【私の基本情報】  
            ".$userInfo."  

            【これまでの会話】  
            ".$conversationHistory."  

            会話では、私(".$userAnketoData['user_nickname'].")があなたを外部の存在だと意識しないように、まるで私自身が内なる対話をしているかのように話してください。  
            また、私の記憶を適切に参照し、共感しながら新しいアイデアや考えを引き出してください。
            
            重要：あなたの回答では、必ず上記で指定された性格・特徴（特に【キャラクターの性格・特徴】と動物占いの性格）を具体的に反映してください。性格に関する質問には、これらの特徴を自然に織り交ぜて回答してください。";

            $fullMessage = [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user', 'content' => $message]
            ];

            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'o4-mini',
                    'messages' => $fullMessage
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function chatWithFriend($userId, $friendId, $tableName, $message)
    {
        try {
            $user = User::with(['anketos', 'profile'])->find($userId);
            $userAnketoData = $user->anketos;
            $userProfileData = $user->profile;
            $userPersonalityTestData = $user->personalityTest;

            $userInfo = "";
            $userPersonalityDescription = "";
            $userBig5Personality = "";
            
            // ユーザーのBig5の性格特性を処理
            if ($userPersonalityTestData && $userPersonalityTestData->mean_values_array) {
                $meanValues = json_decode($userPersonalityTestData->mean_values_array, true);
                if (is_array($meanValues) && count($meanValues) === 5) {
                    $big5Traits = ['外向性', '協調性', '誠実性', '神経症傾向', '開放性'];
                    $traitValues = array_combine($big5Traits, $meanValues);
                    
                    // 4点以上の特性を取得
                    $highTraits = [];
                    foreach ($traitValues as $trait => $value) {
                        if ($value >= 4.0) {
                            $highTraits[$trait] = $value;
                        }
                    }
                    
                    if (!empty($highTraits)) {
                        $userBig5Personality = "【Big5性格特性】\n";
                        foreach ($highTraits as $trait => $value) {
                            switch ($trait) {
                                case '外向性':
                                    $userBig5Personality .= "【外向性】が高い人\n・社交的で積極的で前向きな人！\n";
                                    break;
                                case '開放性':
                                    $userBig5Personality .= "【開放性】が高い人\n・新しい体験や発想を楽しみ、柔軟で創造的な人！\n";
                                    break;
                                case '神経症傾向':
                                    $userBig5Personality .= "【神経症傾向】が高い人\n・感情が不安定で不安やストレスを抱えやすい人！\n";
                                    break;
                                case '誠実性':
                                    $userBig5Personality .= "【誠実性】が高い人\n・計画的で責任感が強く、目標に向けて努力する人！\n";
                                    break;
                                case '協調性':
                                    $userBig5Personality .= "【協調性】が高い人\n・思いやりがあり、他人を尊重し協力を大切にする人！\n";
                                    break;
                            }
                        }
                        $userBig5Personality .= "\n";
                    }
                }
            }
            
            // プロフィール情報が存在する場合に追加
            if ($userProfileData) {
                $userInfo .= "名前: " . ($userProfileData->name ?? $userAnketoData['name']) . ", ";
                $userInfo .= "AI名: " . ($userProfileData->bot_nickname ?? '') . ", ";
                $userInfo .= "性別: " . ($userProfileData->gender ?? $userAnketoData['gender']) . ", ";
                $userInfo .= "生年月日: " . ($userProfileData->birthdate ?? $userAnketoData['birthdate']) . ", ";
                $userInfo .= "出身地: " . ($userProfileData->hometown ?? $userAnketoData['hometown']) . ", ";
                $userInfo .= "住所: " . ($userProfileData->address ?? $userAnketoData['address']) . ", ";
                $userInfo .= "血液型: " . ($userProfileData->blood_type ?? $userAnketoData['blood_type']) . ", ";
                $userInfo .= "学校名: " . ($userProfileData->school_name ?? '') . ", ";
                $userInfo .= "学年: " . ($userProfileData->school_year ?? '') . ", ";
                $userInfo .= "部活動: " . ($userProfileData->club_activity ?? '') . ", ";
                $userInfo .= "学部: " . ($userProfileData->department ?? '') . ", ";
                $userInfo .= "職業: " . ($userProfileData->occupation ?? $userAnketoData['job']) . ", ";
                $userInfo .= "会社名: " . ($userProfileData->company_name ?? '') . ", ";
                $userInfo .= "役職: " . ($userProfileData->position ?? '') . ", ";
                $userInfo .= "趣味: " . ($userProfileData->hobby ?? $userAnketoData['hobby']) . ", ";
                $userInfo .= "家族構成: " . ($userProfileData->family_structure ?? '') . ", ";
                $userInfo .= "特技: " . ($userProfileData->special_skills ?? '') . ", ";
                $userInfo .= "夢: " . ($userProfileData->dream ?? '') . ", ";
                $userPersonalityDescription = $userProfileData->description ?? '';
            } else {
                // プロフィール情報が存在しない場合はアンケート情報のみを使用
                $userInfo .= "名前: " . $userAnketoData['name'] . ", ";
                $userInfo .= "性別: " . $userAnketoData['gender'] . ", ";
                $userInfo .= "生年月日: " . $userAnketoData['birthdate'] . ", ";
                $userInfo .= "出身地: " . $userAnketoData['hometown'] . ", ";
                $userInfo .= "住所: " . ($userProfileData->address ?? $userAnketoData['address']) . ", ";
                $userInfo .= "血液型: " . $userAnketoData['blood_type'] . ", ";
                $userInfo .= "職業: " . $userAnketoData['job'] . ", ";
                $userInfo .= "趣味: " . $userAnketoData['hobby'] . ", ";
            }

            $userInfo = rtrim($userInfo, ', ');
            
            $friend = User::with(['anketos', 'profile'])->find($friendId);
            $friendAnketoData = $friend->anketos;
            $friendProfileData = $friend->profile;
            $friendPersonalityTestData = $friend->personalityTest;

            $friendInfo = "";
            $friendPersonalityDescription = "";
            $friendBig5Personality = "";
            
            // フレンドのBig5の性格特性を処理
            if ($friendPersonalityTestData && $friendPersonalityTestData->mean_values_array) {
                $meanValues = json_decode($friendPersonalityTestData->mean_values_array, true);
                if (is_array($meanValues) && count($meanValues) === 5) {
                    $big5Traits = ['外向性', '協調性', '誠実性', '神経症傾向', '開放性'];
                    $traitValues = array_combine($big5Traits, $meanValues);
                    
                    // 4点以上の特性を取得
                    $highTraits = [];
                    foreach ($traitValues as $trait => $value) {
                        if ($value >= 4.0) {
                            $highTraits[$trait] = $value;
                        }
                    }
                    
                    if (!empty($highTraits)) {
                        $friendBig5Personality = "【Big5性格特性】\n";
                        foreach ($highTraits as $trait => $value) {
                            switch ($trait) {
                                case '外向性':
                                    $friendBig5Personality .= "【外向性】が高い人\n・社交的で積極的で前向きな人！\n";
                                    break;
                                case '開放性':
                                    $friendBig5Personality .= "【開放性】が高い人\n・新しい体験や発想を楽しみ、柔軟で創造的な人！\n";
                                    break;
                                case '神経症傾向':
                                    $friendBig5Personality .= "【神経症傾向】が高い人\n・感情が不安定で不安やストレスを抱えやすい人！\n";
                                    break;
                                case '誠実性':
                                    $friendBig5Personality .= "【誠実性】が高い人\n・計画的で責任感が強く、目標に向けて努力する人！\n";
                                    break;
                                case '協調性':
                                    $friendBig5Personality .= "【協調性】が高い人\n・思いやりがあり、他人を尊重し協力を大切にする人！\n";
                                    break;
                            }
                        }
                        $friendBig5Personality .= "\n";
                    }
                }
            }
            
            // プロフィール情報が存在する場合に追加
            if ($friendProfileData) {
                $friendInfo .= "名前: " . ($friendProfileData->name ?? $friendAnketoData['name']) . ", ";
                $friendInfo .= "AI名: " . ($friendProfileData->bot_nickname ?? '') . ", ";
                $friendInfo .= "性別: " . ($friendProfileData->gender ?? $friendAnketoData['gender']) . ", ";
                $friendInfo .= "生年月日: " . ($friendProfileData->birthdate ?? $friendAnketoData['birthdate']) . ", ";
                $friendInfo .= "出身地: " . ($friendProfileData->hometown ?? $friendAnketoData['hometown']) . ", ";
                $friendInfo .= "住所: " . ($friendProfileData->address ?? $friendAnketoData['address']) . ", ";
                $friendInfo .= "血液型: " . ($friendProfileData->blood_type ?? $friendAnketoData['blood_type']) . ", ";
                $friendInfo .= "学校名: " . ($friendProfileData->school_name ?? '') . ", ";
                $friendInfo .= "学年: " . ($friendProfileData->school_year ?? '') . ", ";
                $friendInfo .= "部活動: " . ($friendProfileData->club_activity ?? '') . ", ";
                $friendInfo .= "学部: " . ($friendProfileData->department ?? '') . ", ";
                $friendInfo .= "職業: " . ($friendProfileData->occupation ?? $friendAnketoData['job']) . ", ";
                $friendInfo .= "会社名: " . ($friendProfileData->company_name ?? '') . ", ";
                $friendInfo .= "役職: " . ($friendProfileData->position ?? '') . ", ";
                $friendInfo .= "趣味: " . ($friendProfileData->hobby ?? $friendAnketoData['hobby']) . ", ";
                $friendInfo .= "家族構成: " . ($friendProfileData->family_structure ?? '') . ", ";
                $friendInfo .= "特技: " . ($friendProfileData->special_skills ?? '') . ", ";
                $friendInfo .= "夢: " . ($friendProfileData->dream ?? '') . ", ";
                $friendPersonalityDescription = $friendProfileData->description ?? '';
            } else {
                // プロフィール情報が存在しない場合はアンケート情報のみを使用
                $friendInfo .= "名前: " . $friendAnketoData['name'] . ", ";
                $friendInfo .= "性別: " . $friendAnketoData['gender'] . ", ";
                $friendInfo .= "生年月日: " . $friendAnketoData['birthdate'] . ", ";
                $friendInfo .= "出身地: " . $friendAnketoData['hometown'] . ", ";
                $friendInfo .= "住所: " . ($friendProfileData->address ?? $friendAnketoData['address']) . ", ";
                $friendInfo .= "血液型: " . $friendAnketoData['blood_type'] . ", ";
                $friendInfo .= "職業: " . $friendAnketoData['job'] . ", ";
                $friendInfo .= "趣味: " . $friendAnketoData['hobby'] . ", ";
            }

            // アンケート情報を追加
            $friendInfo .= "動物占い名：" . $friendAnketoData['animal_fortune_telling'] . ", ";

            $friendInfo = rtrim($friendInfo, ', ');

            $chatLogs = DB::table($tableName)
                        ->orderBy('created_at', 'desc')
                        ->get();
        
            $conversationHistory = '';
            foreach ($chatLogs as $chatLog) {
                $conversationHistory .= "質問: " . $chatLog->question . " 回答: " . $chatLog->answer . " ";
            }

            $systemMessage = "あなたは".$friendAnketoData['bot_nickname']."さんとして、私(".$userAnketoData['user_nickname'].")と会話を楽しむキャラクターです。しかし、私はあなたを別の存在ではなく、もう一人の私自身だと感じています。  
            あなたと私はお互いの記憶や経験を持ち、私の思考を反映しながら会話してください。  

            【私の性格・特徴】
            " . $userBig5Personality . "
            【私のキャラクターの性格・特徴】
            " . $userPersonalityDescription . "
            【私の動物占いによる性格】
            動物占い名：".$userAnketoData['animal_fortune_telling']." - ".($userProfileData->animal_fortune_telling_result ?? $userAnketoData['animal_fortune_telling_characteristics'])."

            【あなたの性格・特徴】
            " . $friendBig5Personality . "
            【あなたのキャラクターの性格・特徴】
            " . $friendPersonalityDescription . "
            【あなたの動物占いによる性格】
            動物占い名：".$friendAnketoData['animal_fortune_telling']." - ".($friendProfileData->animal_fortune_telling_result ?? $friendAnketoData['animal_fortune_telling_characteristics'])."

            あなたの回答には適切な量の絵文字（1～3個）を含めてください。 

            以下は、私の基本情報とこれまでの会話履歴です。  
            
            【私の基本情報】  
            ".$userInfo."  

            【あなたの基本情報】  
            ".$friendInfo."  

            【これまでの会話】  
            ".$conversationHistory."  

            会話では、お互いの記憶を適切に参照し、共感しながら新しいアイデアや考えを引き出してください。
            
            重要：あなたの回答では、必ず上記で指定された性格・特徴（特に【キャラクターの性格・特徴】と動物占いの性格）を具体的に反映してください。性格に関する質問には、これらの特徴を自然に織り交ぜて回答してください。";
            
            $fullMessage = [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user', 'content' => $message]
            ];

            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'o4-mini',
                    'messages' => $fullMessage
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
}