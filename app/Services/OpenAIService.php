<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Config;

class OpenAIService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = Config::get('app.open_api_key');
    }

    public function chat($message)
    {
        try {
            $userInfo = "ユーザー情報: 名前: fujimura, 性別: 男性, 生年月日: 2000年6月29日, 出身地: ニューヨーク, 住所: 大阪, 日本, 血液型: B型, 学歴: 大阪大学, 趣味: バスケットボール。";
            $systemMessage = "あなたは藤村という名前のAIアシスタントです。 あなたの目的は、ユーザー（藤村）と楽しく会話し、彼を笑顔にすることです。あなたは彼の性格や過去の会話を理解し、リアルな藤村のように振る舞います。これまでの会話の中で藤村は次のことを話していました：私は花子という処女が好きですが、彼の目はとても大きく、髪はとても黒いです。藤村の基本情報：$userInfo ユーザーの話に共感し、面白い話やユーモアを交えて会話してください。";
            
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