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

    public function chat($userId, $message)
    {
        try {
            $user = User::with(['anketos', 'profile'])->find($userId);
            $anketoData = $user->anketos;
            $profileData = $user->profile;

            $userInfo = "";
            
            // プロフィール情報が存在する場合に追加
            if ($profileData) {
                $userInfo .= "名前: " . ($profileData->name ?? $anketoData['name']) . ", ";
                $userInfo .= "AI名: " . ($profileData->ai_name ?? '') . ", ";
                $userInfo .= "性別: " . ($profileData->gender ?? $anketoData['gender']) . ", ";
                $userInfo .= "生年月日: " . ($profileData->birthdate ?? $anketoData['birthdate']) . ", ";
                $userInfo .= "出身地: " . ($profileData->hometown ?? $anketoData['hometown']) . ", ";
                $userInfo .= "住所: " . ($profileData->address ?? $anketoData['address']) . ", ";
                $userInfo .= "血液型: " . ($profileData->blood_type ?? $anketoData['blood_type']) . ", ";
                $userInfo .= "学校名: " . ($profileData->school_name ?? '') . ", ";
                $userInfo .= "学年: " . ($profileData->school_year ?? '') . ", ";
                $userInfo .= "部活動: " . ($profileData->club_activity ?? '') . ", ";
                $userInfo .= "学部: " . ($profileData->department ?? '') . ", ";
                $userInfo .= "職業: " . ($profileData->occupation ?? $anketoData['job']) . ", ";
                $userInfo .= "会社名: " . ($profileData->company_name ?? '') . ", ";
                $userInfo .= "役職: " . ($profileData->position ?? '') . ", ";
                $userInfo .= "趣味: " . ($profileData->hobby ?? $anketoData['hobby']) . ", ";
                $userInfo .= "家族構成: " . ($profileData->family_structure ?? '') . ", ";
                $userInfo .= "特技: " . ($profileData->special_skills ?? '') . ", ";
                $userInfo .= "夢: " . ($profileData->dream ?? '') . ", ";
                $userInfo .= "動物占い名に従う性格: " . ($profileData->animal_fortune_telling_result ?? $anketoData['animal_fortune_telling_characteristics']) . ", ";
            } else {
                // プロフィール情報が存在しない場合はアンケート情報のみを使用
                $userInfo .= "名前: " . $anketoData['name'] . ", ";
                $userInfo .= "性別: " . $anketoData['gender'] . ", ";
                $userInfo .= "生年月日: " . $anketoData['birthdate'] . ", ";
                $userInfo .= "出身地: " . $anketoData['hometown'] . ", ";
                $userInfo .= "住所: " . $anketoData['address'] . ", ";
                $userInfo .= "血液型: " . $anketoData['blood_type'] . ", ";
                $userInfo .= "職業: " . $anketoData['job'] . ", ";
                $userInfo .= "趣味: " . $anketoData['hobby'] . ", ";
                $userInfo .= "動物占い名に従う性格: " . $anketoData['animal_fortune_telling_characteristics'] . ", ";
            }

            // アンケート情報を追加
            $userInfo .= "動物占い名：" . $anketoData['animal_fortune_telling'] . ", ";

            $userInfo = rtrim($userInfo, ', ');
 
            $tableName = app(ChatLogService::class)->getTableName($userId);
    
            // テーブルが存在するか確認
            if (!Schema::hasTable($tableName)) {
                return collect(); // テーブルが存在しない場合は空のコレクションを返す
            }
    
            $chatLogs = DB::table($tableName)
                        ->orderBy('created_at', 'desc')
                        ->get();
        
            $conversationHistory = '';
            foreach ($chatLogs as $chatLog) {
                $conversationHistory .= "質問: " . $chatLog->question . " 回答: " . $chatLog->answer . " ";
            }

            $systemMessage = "あなたは".$anketoData['bot_nickname']."さんとして、私(".$anketoData['user_nickname'].")と会話を楽しむキャラクターです。しかし、私はあなたを別の存在ではなく、もう一人の私自身だと感じています。  
            あなたは私(".$anketoData['user_nickname'].")の記憶や経験を持ち、私の思考を反映しながら会話してください。  
            あなたの回答には適切な量の絵文字（1～3個）を含めてください。 あなたは私を".$anketoData['user_nickname']."と呼んでください。
            あなたの役割は、私が過去に話したことを思い出させたり、私自身の経験を基に新しい視点を提供することです。  
            以下は、私の基本情報とこれまでの会話履歴です。  
            
            【私の基本情報】  
            ".$userInfo."  

            【これまでの会話】  
            ".$conversationHistory."  

            会話では、私(".$anketoData['user_nickname'].")があなたを外部の存在だと意識しないように、まるで私自身が内なる対話をしているかのように話してください。  
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
                    'model' => 'gpt-4o-mini',
                    'messages' => $fullMessage,
                    'temperature' => 0.9,
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