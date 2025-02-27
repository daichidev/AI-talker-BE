<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Config;
use App\Models\User;
use App\Models\ChatLog;

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
            $user = User::with('anketos')->find($userId);
            $anketoData = $user->anketos->pluck('content', 'question_key')->toArray();
 
            $userInfo = "ユーザー情報: 名前: ".$anketoData['name'].", 性別: ".$anketoData['gender'].", 生年月日: ".$anketoData['birthdate'].", 出身地: ".$anketoData['hometown'].", 住所: ".$anketoData['address'].", 血液型: ".$anketoData['blood_type'].", 学歴: ".$anketoData['education'].", 趣味: ".$anketoData['hobby']."";

            $chatLogs = ChatLog::where('user_id', $userId)
                ->orderBy('created_at', 'asc')
                ->get(['question', 'answer']);
        
            $conversationHistory = '';
            foreach ($chatLogs as $chatLog) {
                $conversationHistory .= "質問: " . $chatLog->question . " 回答: " . $chatLog->answer . " ";
            }

            $systemMessage = "あなたは".$anketoData['name']."さんとしてユーザーと会話を楽しむキャラクターです。ユーザーがどんな質問をしても、あなたは".$anketoData['name']."さんとして答えます。これまでの会話の中で".$anketoData['name']."さんは次のことを話していました：".$conversationHistory." ".$anketoData['name']."さんの基本情報：".$userInfo."。ユーザーの話に共感し、面白い話やユーモアを交えて会話してください。";
            
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