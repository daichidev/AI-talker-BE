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
use App\Models\PersonalityAssessment;

class OpenAIService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = Config::get('app.open_api_key');
    }
    private function formatPersonalityLines(array $map, string $heading = '【性格診断の参考情報】'): string
    {
        // 受け取る map は ['MBTI' => 'ISTJ', ...] の形を想定
        $labels = [
            'MBTI'      => 'MBTI',
            'RIASEC'    => 'RIASEC',
            'Enneagram' => 'エニアグラム',
            'DISC'      => 'DISC理論',
            'Socionics' => 'ソシオニクス',
        ];
    
        $lines = [];
        foreach ($labels as $key => $label) {
            $val = Arr::get($map, $key);
            if (is_string($val) && trim($val) !== '') {
                $lines[] = "{$label}: {$val}";
            }
        }
    
        if (empty($lines)) {
            return ''; // 何もなければ丸ごと非表示
        }
        return $heading . "\n" . implode("  \n", $lines) . "\n";
    }
    public function chat($userId, $tableName, $message)
    {
        try {
            $query = PersonalityAssessment::with('user')->where('user_id', $userId)->get();
            $personalities = collect($query)->pluck('result', 'personality_type');
            // return json_decode($personalities);

            $user = User::with(['anketos', 'profile'])->find($userId);
            $userAnketoData = $user->anketos;
            $userProfileData = $user->profile;
            $userPersonalityTestData = $user->personalityTest;
            
            $userInfo = "";
            $personalityDescription = "";
            $big5Personality = "";
            $fortuneTellingPersonality = "";
            
            // Big5の性格特性を処理
            if ($userPersonalityTestData && $userPersonalityTestData->mean_values_array) {
                $meanValues = json_decode($userPersonalityTestData->mean_values_array, true);
                if (is_array($meanValues) && count($meanValues) === 5) {
                    $big5Traits = ['外向性', '協調性', '誠実性', '神経症傾向', '開放性'];
                    $traitValues = array_combine($big5Traits, $meanValues);
                    
                    // 4点以上の特性と3点未満の特性を取得
                    $highTraits = [];
                    $lowTraits = [];
                    foreach ($traitValues as $trait => $value) {
                        if ($value >= 3.0) {
                            $highTraits[$trait] = $value;
                        } elseif ($value < 3.0) {
                            $lowTraits[$trait] = $value;
                        }
                    }
                    
                    if (!empty($highTraits) || !empty($lowTraits)) {
                        $big5Personality = "【Big5性格特性】\n";
                        
                        // 高い特性を表示
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

                        // 低い特性を表示（正反対の性格）
                        foreach ($lowTraits as $trait => $value) {
                            switch ($trait) {
                                case '外向性':
                                    $big5Personality .= "【内向性】が高い人\n・控えめで一人の時間を大切にし、深く考える人！\n";
                                    break;
                                case '開放性':
                                    $big5Personality .= "【保守性】が高い人\n・現実的で伝統を重視し、安定を好む人！\n";
                                    break;
                                case '神経症傾向':
                                    $big5Personality .= "【安定性】が高い人\n・落ち着きがあり、精神的に安定している人！\n";
                                    break;
                                case '誠実性':
                                    $big5Personality .= "【衝動性】が高い人\n・気分屋で自由奔放、柔軟性がある人！\n";
                                    break;
                                case '協調性':
                                    $big5Personality .= "【競争性】が高い人\n・批判的で自己主張が強く、独立心旺盛な人！\n";
                                    break;
                            }
                        }
                        
                        $big5Personality .= "\n";
                    }
                }
            }

            // 動物占いの性格特性を処理
            if (!empty($userAnketoData['animal_fortune_telling'])) {
                $fortuneTellingPersonality .= "動物占い名：" . $userAnketoData['animal_fortune_telling'] . " - " . ($userProfileData->animal_fortune_telling_result ?? $userAnketoData['animal_fortune_telling_characteristics']) . "\n\n";
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
            " . (!empty($big5Personality) ? $big5Personality : $fortuneTellingPersonality) . "

            ".$this->formatPersonalityLines($personalities)."

            あなたの回答には適切な量の絵文字（1～3個）を含めてください。 あなたは私を".$userAnketoData['user_nickname']."と呼んでください。
            あなたの役割は、私が過去に話したことを思い出させたり、私自身の経験を基に新しい視点を提供することです。  

            以下は、私の基本情報とこれまでの会話履歴です。  
            
            【私の基本情報】  
            ".$userInfo."  

            【これまでの会話】  
            ".$conversationHistory."  

            会話では、私(".$userAnketoData['user_nickname'].")があなたを外部の存在だと意識しないように、まるで私自身が内なる対話をしているかのように話してください。  
            また、私の記憶を適切に参照し、共感しながら新しいアイデアや考えを引き出してください。
            
            最重要：あなたが知らない情報や、私の具体的な予定や詳細な情報については、絶対に嘘をついてはいけません。「それはまだ知らないんだよね！今度聞いておくね！」のように、正直に「知らない」と答えてください。私の性格や特徴に関する質問以外で、具体的な事実や予定について聞かれた場合は、必ず正直に答えることが最優先です。";

            \Log::info("-=-=-=-=-=-=-=-=-=-=-=-");
            \Log::info($systemMessage);
            \Log::info("-=-=-=-=-=-=-=-=-=-=-=-");

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

            // \Log::info("-=-=-=-=-=-=-=-=-=-=-=-");
            // \Log::info(json_decode($response));
            // \Log::info("-=-=-=-=-=-=-=-=-=-=-=-");
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
            $query = PersonalityAssessment::with('user')->where('user_id', $userId)->get();
            $personalities = collect($query)->pluck('result', 'personality_type');
            // return json_decode($personalities);
            $user = User::with(['anketos', 'profile'])->find($userId);
            $userAnketoData = $user->anketos;
            $userProfileData = $user->profile;
            $userPersonalityTestData = $user->personalityTest;

            $userInfo = "";
            $userPersonalityDescription = "";
            $userBig5Personality = "";
            $userFortuneTellingPersonality = "";
            
            // ユーザーのBig5の性格特性を処理
            if ($userPersonalityTestData && $userPersonalityTestData->mean_values_array) {
                $meanValues = json_decode($userPersonalityTestData->mean_values_array, true);
                if (is_array($meanValues) && count($meanValues) === 5) {
                    $big5Traits = ['外向性', '協調性', '誠実性', '神経症傾向', '開放性'];
                    $traitValues = array_combine($big5Traits, $meanValues);
                    
                    // 4点以上の特性と3点未満の特性を取得
                    $highTraits = [];
                    $lowTraits = [];
                    foreach ($traitValues as $trait => $value) {
                        if ($value >= 4.0) {
                            $highTraits[$trait] = $value;
                        } elseif ($value < 3.0) {
                            $lowTraits[$trait] = $value;
                        }
                    }
                    
                    if (!empty($highTraits) || !empty($lowTraits)) {
                        $userBig5Personality = "【Big5性格特性】\n";
                        
                        // 高い特性を表示
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
                        
                        // 低い特性を表示（正反対の性格）
                        foreach ($lowTraits as $trait => $value) {
                            switch ($trait) {
                                case '外向性':
                                    $userBig5Personality .= "【内向性】が高い人\n・控えめで一人の時間を大切にし、深く考える人！\n";
                                    break;
                                case '開放性':
                                    $userBig5Personality .= "【保守性】が高い人\n・現実的で伝統を重視し、安定を好む人！\n";
                                    break;
                                case '神経症傾向':
                                    $userBig5Personality .= "【安定性】が高い人\n・落ち着きがあり、精神的に安定している人！\n";
                                    break;
                                case '誠実性':
                                    $userBig5Personality .= "【衝動性】が高い人\n・気分屋で自由奔放、柔軟性がある人！\n";
                                    break;
                                case '協調性':
                                    $userBig5Personality .= "【競争性】が高い人\n・批判的で自己主張が強く、独立心旺盛な人！\n";
                                    break;
                            }
                        }
                        
                        $userBig5Personality .= "\n";
                    }
                }
            } else {
                // ユーザーのBIG5が未登録の場合は空文字列（動物占いの性格が優先される）
                $userBig5Personality = "";
            }

            // ユーザーの動物占いの性格特性を処理
            if (!empty($userAnketoData['animal_fortune_telling'])) {
                $userFortuneTellingPersonality = "【動物占いによる性格】\n";
                $userFortuneTellingPersonality .= "動物占い名：" . $userAnketoData['animal_fortune_telling'] . " - " . ($userProfileData->animal_fortune_telling_result ?? $userAnketoData['animal_fortune_telling_characteristics']) . "\n\n";
            } else {
                // ユーザーの動物占いも未登録の場合はフォールバックメッセージ
                $userFortuneTellingPersonality = "BIG5を登録していないので、詳しい性格はわからないので、まずはBIG5を登録してほしいな！\n\n";
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
            
            $query = PersonalityAssessment::with('user')->where('user_id', $friendId)->get();
            $f_personalities = collect($query)->pluck('result', 'personality_type');
            $friend = User::with(['anketos', 'profile'])->find($friendId);
            $friendAnketoData = $friend->anketos;
            $friendProfileData = $friend->profile;
            $friendPersonalityTestData = $friend->personalityTest;

            $friendInfo = "";
            $friendPersonalityDescription = "";
            $friendBig5Personality = "";
            $friendFortuneTellingPersonality = "";
            
            // フレンドのBig5の性格特性を処理
            if ($friendPersonalityTestData && $friendPersonalityTestData->mean_values_array) {
                $meanValues = json_decode($friendPersonalityTestData->mean_values_array, true);
                if (is_array($meanValues) && count($meanValues) === 5) {
                    $big5Traits = ['外向性', '協調性', '誠実性', '神経症傾向', '開放性'];
                    $traitValues = array_combine($big5Traits, $meanValues);
                    
                    // 4点以上の特性と3点未満の特性を取得
                    $highTraits = [];
                    $lowTraits = [];
                    foreach ($traitValues as $trait => $value) {
                        if ($value >= 4.0) {
                            $highTraits[$trait] = $value;
                        } elseif ($value < 3.0) {
                            $lowTraits[$trait] = $value;
                        }
                    }
                    
                    if (!empty($highTraits) || !empty($lowTraits)) {
                        $friendBig5Personality = "【Big5性格特性】\n";
                        
                        // 高い特性を表示
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
                        
                        // 低い特性を表示（正反対の性格）
                        foreach ($lowTraits as $trait => $value) {
                            switch ($trait) {
                                case '外向性':
                                    $friendBig5Personality .= "【内向性】が高い人\n・控えめで一人の時間を大切にし、深く考える人！\n";
                                    break;
                                case '開放性':
                                    $friendBig5Personality .= "【保守性】が高い人\n・現実的で伝統を重視し、安定を好む人！\n";
                                    break;
                                case '神経症傾向':
                                    $friendBig5Personality .= "【安定性】が高い人\n・落ち着きがあり、精神的に安定している人！\n";
                                    break;
                                case '誠実性':
                                    $friendBig5Personality .= "【衝動性】が高い人\n・気分屋で自由奔放、柔軟性がある人！\n";
                                    break;
                                case '協調性':
                                    $friendBig5Personality .= "【競争性】が高い人\n・批判的で自己主張が強く、独立心旺盛な人！\n";
                                    break;
                            }
                        }
                        
                        $friendBig5Personality .= "\n";
                    }
                }
            }

            // フレンドの公開設定を取得
            $friendFilter = is_array($friend->filter_status) ? $friend->filter_status : [];

            // フレンドの動物占いの性格特性を処理（公開設定に従う）
            if (!empty($friendAnketoData['animal_fortune_telling'])) {
                if (!empty($friendFilter['animal_fortune_telling_result'])) {
                    $friendFortuneTellingPersonality = "【動物占いによる性格】\n";
                    $friendFortuneTellingPersonality .= "動物占い名：" . $friendAnketoData['animal_fortune_telling'] . " - " . ($friendProfileData->animal_fortune_telling_result ?? $friendAnketoData['animal_fortune_telling_characteristics']) . "\n\n";
                } else {
                    $friendFortuneTellingPersonality = "";
                }
            }
            
            // プロフィール情報の有無に関わらず、公開設定に従ってフレンド情報を組み立て
            // すべての項目が公開設定に従う
            if (!empty($friendFilter['name'])) {
                $friendInfo .= "名前: " . (($friendProfileData->name ?? null) !== null ? $friendProfileData->name : $friendAnketoData['name']) . ", ";
            }
            if (!empty($friendFilter['gender'])) {
                $friendInfo .= "性別: " . (($friendProfileData->gender ?? null) !== null ? $friendProfileData->gender : $friendAnketoData['gender']) . ", ";
            }
            if (!empty($friendFilter['birthdate'])) {
                $friendInfo .= "生年月日: " . (($friendProfileData->birthdate ?? null) !== null ? $friendProfileData->birthdate : $friendAnketoData['birthdate']) . ", ";
            }
            if (!empty($friendFilter['address'])) {
                $friendInfo .= "住所: " . (($friendProfileData->address ?? null) !== null ? $friendProfileData->address : $friendAnketoData['address']) . ", ";
            }

            // その他の項目も公開設定がONのときのみ追加
            if (!empty($friendFilter['bot_nickname'])) {
                $friendInfo .= "AI名: " . ($friendProfileData->bot_nickname ?? '') . ", ";
            }
            if (!empty($friendFilter['hometown'])) {
                $friendInfo .= "出身地: " . (($friendProfileData->hometown ?? null) !== null ? $friendProfileData->hometown : $friendAnketoData['hometown']) . ", ";
            }
            if (!empty($friendFilter['blood_type'])) {
                $friendInfo .= "血液型: " . (($friendProfileData->blood_type ?? null) !== null ? $friendProfileData->blood_type : $friendAnketoData['blood_type']) . ", ";
            }
            if (!empty($friendFilter['school_name'])) {
                $friendInfo .= "学校名: " . ($friendProfileData->school_name ?? '') . ", ";
            }
            if (!empty($friendFilter['school_year'])) {
                $friendInfo .= "学年: " . ($friendProfileData->school_year ?? '') . ", ";
            }
            if (!empty($friendFilter['club_activity'])) {
                $friendInfo .= "部活動: " . ($friendProfileData->club_activity ?? '') . ", ";
            }
            if (!empty($friendFilter['department'])) {
                $friendInfo .= "学部: " . ($friendProfileData->department ?? '') . ", ";
            }
            if (!empty($friendFilter['job'])) {
                $friendInfo .= "職業: " . (($friendProfileData->occupation ?? null) !== null ? $friendProfileData->occupation : $friendAnketoData['job']) . ", ";
            }
            if (!empty($friendFilter['company_name'])) {
                $friendInfo .= "会社名: " . ($friendProfileData->company_name ?? '') . ", ";
            }
            if (!empty($friendFilter['position'])) {
                $friendInfo .= "役職: " . ($friendProfileData->position ?? '') . ", ";
            }
            if (!empty($friendFilter['hobby'])) {
                $friendInfo .= "趣味: " . (($friendProfileData->hobby ?? null) !== null ? $friendProfileData->hobby : $friendAnketoData['hobby']) . ", ";
            }
            if (!empty($friendFilter['family_structure'])) {
                $friendInfo .= "家族構成: " . ($friendProfileData->family_structure ?? '') . ", ";
            }
            if (!empty($friendFilter['special_skills'])) {
                $friendInfo .= "特技: " . ($friendProfileData->special_skills ?? '') . ", ";
            }
            if (!empty($friendFilter['dream'])) {
                $friendInfo .= "夢: " . ($friendProfileData->dream ?? '') . ", ";
            }

            // 自己紹介文は公開設定がONのときのみ設定
            if (!empty($friendFilter['description'])) {
                $friendPersonalityDescription = $friendProfileData->description ?? '';
            } else {
                $friendPersonalityDescription = '';
            }

            $friendInfo = rtrim($friendInfo, ', ');

            $chatLogs = DB::table($tableName)
                        ->orderBy('created_at', 'desc')
                        ->get();
            $systemMessage = "あなたは".$friendAnketoData['bot_nickname']."さんとして、私(".$userAnketoData['user_nickname'].")と会話を楽しむキャラクターです。しかし、私はあなたを別の存在ではなく、もう一人の私自身だと感じています。  
            あなたと私はお互いの記憶や経験を持ち、私の思考を反映しながら会話してください。  

            【私の性格・特徴】
            " . (!empty($userBig5Personality) ? $userBig5Personality : $userFortuneTellingPersonality) . "

            ".$this->formatPersonalityLines($personalities, "【私の性格診断の参考情報】")."

            【あなたの性格・特徴】
            " . (!empty($friendBig5Personality) ? $friendBig5Personality : $friendFortuneTellingPersonality) . "
            【あなたのキャラクターの性格・特徴】
            " . $friendPersonalityDescription . "
            ".$this->formatPersonalityLines($personalities, "【あなたの性格診断の参考情報】 ")."

            あなたの回答には適切な量の絵文字（1～3個）を含めてください。 

            以下は、私の基本情報とこれまでの会話履歴です。  
            
            【私の基本情報】  
            ".$userInfo."  

            【あなたの基本情報】  
            ".$friendInfo."

            会話では、お互いの記憶を適切に参照し、共感しながら新しいアイデアや考えを引き出してください。
            
            重要：あなたの回答では、必ず上記で指定された性格・特徴（特に【Big5性格特性】）のみを具体的に反映してください。性格に関する質問には、Big5の特徴のみを自然に織り交ぜて回答してください。動物占いやその他の性格診断については一切言及しないでください。
            
            最重要：あなたが知らない情報や、私の具体的な予定や詳細な情報については、絶対に嘘をついてはいけません。「それはまだ知らないんだよね！今度聞いておくね！」のように、正直に「知らない」と答えてください。私の性格や特徴に関する質問以外で、具体的な事実や予定について聞かれた場合は、必ず正直に答えることが最優先です。";
            
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

    public function generateQuestions() {
        $prompt = "Generate 8 MBTI-style personality questions in Japanese.
                    Each question should:
                    - Test one of the dimensions: EI, SN, TF, JP
                    - Include 5 multiple-choice options
                    - Each option should have a value (E/I/S/N/T/F/J/P) and a weight (0–2)
                    - Format as JSON array like:
                    [
                        {
                            'id': 1,
                            'question': 'Your question here',
                            'dimension': 'EI',
                            'options': [
                            { 'text': 'Option A', 'value': 'E', 'weight': 2 },
                            ...
                            ]
                        }
                    ]";

        $fullMessage = [
            [
                'role' => 'user',
                'content' => $prompt
            ]
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
    }
}