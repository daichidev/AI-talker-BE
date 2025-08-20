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

            $userInfo = "";
            $personalityDescription = "";
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
                $userInfo .= "動物占い名に従う性格: " . ($userProfileData->animal_fortune_telling_result ?? $userAnketoData['animal_fortune_telling_characteristics']) . ", ";
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
                $userInfo .= "動物占い名に従う性格: " . $userAnketoData['animal_fortune_telling_characteristics'] . ", ";
            }

            // アンケート情報を追加
            $userInfo .= "動物占い名：" . $userAnketoData['animal_fortune_telling'] . ", ";

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

            あなたの性格は以下の通りです：
            【キャラクターの性格・特徴】
            " . $personalityDescription . "

            あなたの回答には適切な量の絵文字（1～3個）を含めてください。 あなたは私を".$userAnketoData['user_nickname']."と呼んでください。
            あなたの役割は、私が過去に話したことを思い出させたり、私自身の経験を基に新しい視点を提供することです。  

            以下は、私の基本情報とこれまでの会話履歴です。  
            
            【私の基本情報】  
            ".$userInfo."  

            【これまでの会話】  
            ".$conversationHistory."  

            会話では、私(".$userAnketoData['user_nickname'].")があなたを外部の存在だと意識しないように、まるで私自身が内なる対話をしているかのように話してください。  
            また、私の記憶を適切に参照し、共感しながら新しいアイデアや考えを引き出してください。";
            
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
                    'messages' => $fullMessage,
                    // 'temperature' => 0.95,
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

            $userInfo = "";
            $userPersonalityDescription = "";
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
                $userInfo .= "動物占い名に従う性格: " . ($userProfileData->animal_fortune_telling_result ?? $userAnketoData['animal_fortune_telling_characteristics']) . ", ";
                $userPersonalityDescription = $userProfileData->description ?? '';
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
                $userInfo .= "動物占い名に従う性格: " . $userAnketoData['animal_fortune_telling_characteristics'] . ", ";
            }

            // アンケート情報を追加
            $userInfo .= "動物占い名：" . $userAnketoData['animal_fortune_telling'] . ", ";

            $userInfo = rtrim($userInfo, ', ');
            
            $friend = User::with(['anketos', 'profile'])->find($friendId);
            $friendAnketoData = $friend->anketos;
            $friendProfileData = $friend->profile;

            $friendInfo = "";
            $friendPersonalityDescription = "";
            // プロフィール情報が存在する場合に追加
            if ($friendProfileData) {
                $userInfo .= "名前: " . ($friendProfileData->name ?? $friendAnketoData['name']) . ", ";
                $userInfo .= "AI名: " . ($friendProfileData->bot_nickname ?? '') . ", ";
                $userInfo .= "性別: " . ($friendProfileData->gender ?? $friendAnketoData['gender']) . ", ";
                $userInfo .= "生年月日: " . ($friendProfileData->birthdate ?? $friendAnketoData['birthdate']) . ", ";
                $userInfo .= "出身地: " . ($friendProfileData->hometown ?? $friendAnketoData['hometown']) . ", ";
                $userInfo .= "住所: " . ($friendProfileData->address ?? $friendAnketoData['address']) . ", ";
                $userInfo .= "血液型: " . ($friendProfileData->blood_type ?? $friendAnketoData['blood_type']) . ", ";
                $userInfo .= "学校名: " . ($friendProfileData->school_name ?? '') . ", ";
                $userInfo .= "学年: " . ($friendProfileData->school_year ?? '') . ", ";
                $userInfo .= "部活動: " . ($friendProfileData->club_activity ?? '') . ", ";
                $userInfo .= "学部: " . ($friendProfileData->department ?? '') . ", ";
                $userInfo .= "職業: " . ($friendProfileData->occupation ?? $friendAnketoData['job']) . ", ";
                $userInfo .= "会社名: " . ($friendProfileData->company_name ?? '') . ", ";
                $userInfo .= "役職: " . ($friendProfileData->position ?? '') . ", ";
                $userInfo .= "趣味: " . ($friendProfileData->hobby ?? $friendAnketoData['hobby']) . ", ";
                $userInfo .= "家族構成: " . ($friendProfileData->family_structure ?? '') . ", ";
                $userInfo .= "特技: " . ($friendProfileData->special_skills ?? '') . ", ";
                $userInfo .= "夢: " . ($friendProfileData->dream ?? '') . ", ";
                $userInfo .= "動物占い名に従う性格: " . ($friendProfileData->animal_fortune_telling_result ?? $friendAnketoData['animal_fortune_telling_characteristics']) . ", ";
                $friendPersonalityDescription = $friendProfileData->description ?? '';
            } else {
                // プロフィール情報が存在しない場合はアンケート情報のみを使用
                $userInfo .= "名前: " . $friendAnketoData['name'] . ", ";
                $userInfo .= "性別: " . $friendAnketoData['gender'] . ", ";
                $userInfo .= "生年月日: " . $friendAnketoData['birthdate'] . ", ";
                $userInfo .= "出身地: " . $friendAnketoData['hometown'] . ", ";
                $userInfo .= "住所: " . $friendAnketoData['address'] . ", ";
                $userInfo .= "血液型: " . $friendAnketoData['blood_type'] . ", ";
                $userInfo .= "職業: " . $friendAnketoData['job'] . ", ";
                $userInfo .= "趣味: " . $friendAnketoData['hobby'] . ", ";
                $userInfo .= "動物占い名に従う性格: " . $friendAnketoData['animal_fortune_telling_characteristics'] . ", ";
            }

            // アンケート情報を追加
            $userInfo .= "動物占い名：" . $friendAnketoData['animal_fortune_telling'] . ", ";

            $userInfo = rtrim($userInfo, ', ');

            $chatLogs = DB::table($tableName)
                        ->orderBy('created_at', 'desc')
                        ->get();
        
            $conversationHistory = '';
            foreach ($chatLogs as $chatLog) {
                $conversationHistory .= "質問: " . $chatLog->question . " 回答: " . $chatLog->answer . " ";
            }

            $systemMessage = "あなたは".$friendAnketoData['bot_nickname']."さんとして、私(".$userAnketoData['user_nickname'].")と会話を楽しむキャラクターです。しかし、私はあなたを別の存在ではなく、もう一人の私自身だと感じています。  
            あなたと私はお互いの記憶や経験を持ち、私の思考を反映しながら会話してください。  

            私の性格は以下の通りです：
            【キャラクターの性格・特徴】
            " . $userPersonalityDescription . "

            あなたの性格は以下の通りです：
            【キャラクターの性格・特徴】
            " . $friendPersonalityDescription . "

            あなたの回答には適切な量の絵文字（1～3個）を含めてください。 

            以下は、私の基本情報とこれまでの会話履歴です。  
            
            【私の基本情報】  
            ".$userInfo."  

            【あなたの基本情報】  
            ".$friendInfo."  

            【これまでの会話】  
            ".$conversationHistory."  

            会話では、お互いの記憶を適切に参照し、共感しながら新しいアイデアや考えを引き出してください。";
            
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
                    'messages' => $fullMessage,
                    // 'temperature' => 0.95,
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