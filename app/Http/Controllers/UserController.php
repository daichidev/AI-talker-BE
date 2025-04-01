<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Anketo;
use App\Models\User;
use App\Models\Avatar;
use App\Models\ChatLog;

use App\Http\Controllers\DeepImageController;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected $questions = [
        'email', 'password', 'name', 'birthdate', 'gender', 'user_nickname', 'bot_nickname', 'hometown', 'address',
        'blood_type', 'job', 'hobby'
    ];

    public function storeFaceID(Request $request) {
        $request->validate([
            'deviceId' => 'required|string',
            'photo' => 'required|image|mimes:jpeg,png,jpg',
            'avatarType' => 'required|integer',
            'avatarGenderType' => 'required|integer'
        ]);

        // デバイスIDが既にデータベースに存在するか確認
        $existingUser = User::where('device_id', $request->deviceId)->first();
        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'このユーザーは既に登録されています。',
            ], 400);  // 重複するデバイスIDの場合、400エラー（不正なリクエスト）を返す
        }

        // 写真をstorage/app/public/face_id_photosに保存
        $photoPath = $request->file('photo')->store('face_id_photos', 'public');

        // ファイル名のみを取得
        $filename = basename($photoPath);

        $deepImageController = new DeepImageController();
        $modifiedRequest = new Request([
            'photoPath' => $filename,
            'avatar_type' => $request->avatarType,
            'avatar_gender_type' => $request->avatarGenderType
        ]);
        $response = $deepImageController->processImage($modifiedRequest);
        $responseData = $response->getData(true);

        if (isset($responseData['image_url'])) {
            $avatarPath = $responseData['image_url'];

             // device IDと写真のパスをデータベースに保存
            $user = new User();
            $user->device_id = $request->deviceId;
            $user->face_photo = $photoPath;
            // $user->face_photo = 'test.jpg';
            $user->save();
    
            $avatar = new Avatar();
            $avatar->avatar_link = $avatarPath;
            $avatar->user_id = $user->id;
            $avatar->save();
    
            return response()->json([
                'success' => true, 
                'userId' => $user->id,
                'avatarPath' => $avatarPath  
            ]);
        } else {
            return response()->json(['success' => false, 'message' => '画像の処理に失敗しました。'], 500);
        }
    }

    public function loginWithFaceId(Request $request)
    {
        $request->validate([
            'deviceId' => 'required|string',
        ]);

        return $this->authenticateUser(['device_id' => $request->deviceId]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        return $this->authenticateUser(['email' => $request->email, 'password' => $request->password]);
    }

    private function authenticateUser(array $credentials)
    {
        if (isset($credentials['device_id'])) {
            $user = User::with('latestAvatar')->where('device_id', $credentials['device_id'])->first();
        } elseif (isset($credentials['email'])) {
            $user = User::with('latestAvatar')->where('email', $credentials['email'])->first();
            
            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return response()->json(['success' => false, 'message' => '登録されたユーザーがいません。']);
            }
        } else {
            return response()->json(['success' => false, 'message' => '無効な認証情報です。']);
        }

        if (!$user) {
            return response()->json(['success' => false, 'message' => '登録されたユーザーがいません。']);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
            'messages' => $this->getChatLogs($user->id),
        ]);
    }

    public function storeAnketo(Request $request) {       
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'question_key' => 'required',
            'content' => 'required|string'
        ]);

        $questionKey = $this->questions[$request->question_key];

        $user = User::find($request->user_id);

        // Handle job selection logic
        if ($questionKey === 'job') {
            $jobCategories = [
                '学生' => ['中学生', '高校生', '大学生', '大学院生', '浪人生', "その他"],
                '会社員' => ['正社員', '契約社員', '派遣社員', '会社役員', '短時間社員', '準社員', '臨時社員', '業務委託', "その他"],
                '経営者' => ['会長', '社長', '取締役', '店長', '個人事業主', "その他"],
                '公務員' => ['国家公務員', '地方公務員', '自衛隊', '警察', '消防', "その他"],
                '主婦' => ['主婦', '主夫', "その他"]
            ];

            $selectedJob = $request->content;

            if (array_key_exists($selectedJob, $jobCategories)) {
                return response()->json([
                    'success' => true,
                    'anketo_status' => $user->anketo_status,
                    'next_question_text' => implode(", ", $jobCategories[$selectedJob])
                ]);
            } else if ($selectedJob === 'その他') {
                return response()->json([
                    'success' => true,
                    'anketo_status' => $user->anketo_status,
                    'next_question_text' => "職業を教えてください！"
                ]);
            }
        }

        // Handle email separately
        if ($questionKey == 'email') {
            $existingUser = User::where('email', $request->content)->first();
            if ($existingUser && $existingUser->id != $user->id) {
                return response()->json([
                    'success' => true,
                    'anketo_status' => $user->anketo_status,
                    'next_question_text' => 'すでに同じメールが存在しています。 別のメールを入力してください。'
                ]);
            }
            $user->email = $request->content;
        }

        if ($questionKey == 'email') {
            $existingUser = User::where('email', $request->content)->first();
            
            if ($existingUser && $existingUser->id != $user->id) {
                return response()->json([
                    'success' => true,
                    'anketo_status' => $user->anketo_status,
                    'next_question_text' => 'すでに同じメールが存在しています。 別のメールを入力してください。'
                ]);
            }

            $user->email = $request->content;
        }

        if ($questionKey == 'birthdate') {          
            if (!preg_match('/^\d{4}\.\d{1,2}\.\d{1,2}$/', $request->content)) {
                return response()->json([
                    'success' => true,
                    'anketo_status' => $user->anketo_status,
                    'next_question_text' => '正確な生年月日形式を入力してください。'
                ]);
            }

            $birthdate_data = $this->getAnimalSign($request->content);
        }

        if ($questionKey == 'password') {
            $user->password = Hash::make($request->content);
        }

        $user->anketo_status += 1;

        $user->save();
        
        if ($questionKey == 'password') {
            return response()->json([
                'success' => true,
                'anketo_status' => $user->anketo_status,
                'next_question_text' => "色々と教えてくれてありがとう！！😄 私があなた自身のAIだから、これから何でも相談してね！！😊 早速だけど、何か聞きたいことや言いたいことはある？😊"
            ]);
        }

        if ($questionKey == 'birthdate') {
            Anketo::updateOrCreate(
                ['user_id' => $request->user_id, 'question_key' => "animal_fortune_telling"],
                ['content' => $birthdate_data['animal_fortune_telling_result']]
            );
            Anketo::updateOrCreate(
                ['user_id' => $request->user_id, 'question_key' => "animal_fortune_telling_characteristics"],
                ['content' => $birthdate_data['animal_fortune_telling_characteristics']]
            );
        }
        
        if ($questionKey !== 'email' && $questionKey !== 'password') {
            Anketo::updateOrCreate(
                ['user_id' => $request->user_id, 'question_key' => $questionKey],
                ['content' => $request->content]
            );
        }

        $questionRequest = new Request(['question_key' => $user->anketo_status]);
        $questionResponse = $this->getQuestion($questionRequest);
        $questionData = json_decode($questionResponse->getContent(), true);

        if ($questionKey == 'birthdate') {
            $next_question_text = $birthdate_data['animal_fortune_telling_characteristics']."/".$questionData['question_text'];
        } else {
            $next_question_text = $questionData['question_text'];
        }
        
        if ($questionData['success']) {
            return response()->json([
                'success' => true,
                'anketo_status' => $user->anketo_status,
                'next_question_text' => $next_question_text
            ]);
        }
    }

    public function getQuestion(Request $request)
    {
        $request->validate([
            'question_key' => 'required',
        ]);

        $questionKey = $this->questions[$request->question_key];

        $questions = config('anketo_question');

        if (!array_key_exists($questionKey, $questions)) {
            return response()->json(['success' => false, 'message' => '質問が見つかりません。'], 404);
        }

        return response()->json([
            'success' => true,
            'question_key' => $request->question_key,
            'question_text' => $questions[$questionKey],
        ]);
    }

    private function getChatLogs($userId)
    {
        return ChatLog::where('user_id', $userId)
            ->get()
            ->flatMap(fn($chatLog) => [
                ['text' => $chatLog->question, 'sender' => 'user'],
                ['text' => $chatLog->answer, 'sender' => 'bot'],
            ]);
    }

    private function getAnimalSign($birthdate)
    {
        // 入力を分割して取得
        list($year, $month, $day) = explode('.', $birthdate);

        // 動物リスト
        $animals = [
            "チーター", "ペガサス", "虎", "狼", "黒ひょう", "猿",
            "ライオン", "象", "ひつじ", "こじか", "たぬき", "鳳凰"
        ];

        // 陰陽の決定（偶数年＝プラス、奇数年＝マイナス）
        $yinYang = ["プラス", "マイナス"];
        $yinYangType = $yinYang[$year % 2];

        // 十二運星リスト
        $twelveFortunes = [
            "胎", "養", "長生", "沐浴", "冠帯", "建禄",
            "帝旺", "衰", "病", "死", "墓", "絶"
        ];

        // 動物の決定
        $baseNumber = ($year + $month + $day) % 12;
        $animal = $animals[$baseNumber];

        // 十二運星の決定
        $fortuneIndex = (($year * 37) + ($month * 13) + ($day * 17)) % 12;
        $fortune = $twelveFortunes[$fortuneIndex];

        // 十二運星に対応する性格特徴
        $twelveFortuneCharacteristics = [
            "胎" => "好奇心旺盛で冒険好き",
            "養" => "優しく育てるタイプ",
            "長生" => "健康的で努力家",
            "沐浴" => "感受性が豊か",
            "冠帯" => "責任感が強いリーダー",
            "建禄" => "冷静沈着で実務能力が高い",
            "帝旺" => "権力志向で成功しやすい",
            "衰" => "落ち着きがあり堅実",
            "病" => "芸術的才能がある",
            "死" => "哲学的でミステリアス",
            "墓" => "慎重で堅実",
            "絶" => "変化を好むタイプ"
        ];

        // 十二運星の特徴取得
        $fortuneCharacteristic = $twelveFortuneCharacteristics[$fortune];

        return [
            'animal_fortune_telling_result' => "{$animal}",
            'animal_fortune_telling_characteristics' => $fortuneCharacteristic
        ];
    }

    public function deleteAccount(Request $request)
    {
        $request->validate([
            'userId' => 'required|exists:users,id',
        ]);

        $user = User::find($request->userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }
}