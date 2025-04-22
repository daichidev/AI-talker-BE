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

class GeminiService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = Config::get('app.gemini_api_key');
    }

    public function chat($userId, $message)
    {
        try {
            $user = User::with('anketos')->find($userId);
            $anketoData = $user->anketos;
 
            $userInfo = "動物占い名：".$anketoData['animal_fortune_telling'].", 動物占い名に従う性格：".$anketoData['animal_fortune_telling_characteristics'].", 名前: ".$anketoData['name'].", 性別: ".$anketoData['gender'].", 生年月日: ".$anketoData['birthdate'].", 出身地: ".$anketoData['hometown'].", 住所: ".$anketoData['address'].", 血液型: ".$anketoData['blood_type'].", 職業: ".$anketoData['job'].", 趣味: ".$anketoData['hobby']."";
 
            $tableName = app(ChatLogService::class)->getTableName($userId);
    
            // Check if table exists first
            if (!Schema::hasTable($tableName)) {
                return collect(); // Return empty collection if no table exists
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

            $response = $this->client->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->apiKey, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $systemMessage],
                                ['text' => $message]
                            ]
                        ]
                    ]
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
}