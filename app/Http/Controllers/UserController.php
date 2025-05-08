<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Anketo;
use App\Models\User;
use App\Models\Avatar;

use App\Http\Controllers\DeepImageController;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use App\Services\ChatLogService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; 

class UserController extends Controller
{
    protected $questions = [
        'name', 'birthdate', 'gender', 'user_nickname', 'bot_nickname', 'hometown', 'address',
        'blood_type', 'job', 'hobby', 'is_sociable', 'likes_group_activities', 'energetic_at_parties', 'comfortable_public_speaking',
        'helpful_to_others', 'respects_others_opinions', 'avoids_conflicts', 'tries_to_be_kind', 'meets_deadlines', 'plans_ahead',
        'rarely_forgets_things', 'takes_responsibility', 'easily_anxious', 'tends_to_feel_down', 'dwells_on_mistakes', 'sensitive_to_pressure',
        'likes_new_experiences', 'interested_in_arts', 'open_to_new_ideas', 'enjoys_change'
    ];

    public function storeFaceID(Request $request) {
        $request->validate([
            'deviceId' => 'required|string',
            'photo' => 'required|image|mimes:jpeg,png,jpg',
            'avatarType' => 'required|integer',
            'avatarGenderType' => 'required|integer',
            'userEmail' => 'required|string',
            'userPassword' => 'required|string'
        ]);

        // Check if email already exists
        $existingEmailUser = User::where('email', $request->userEmail)->first();
        if ($existingEmailUser) {
            return response()->json([
                'success' => false,
                'message' => 'このメールアドレスは既に使用されています。', // This email is already in use
            ]);
        }
        
        // デバイスIDが既にデータベースに存在するか確認
        $existingUser = User::where('device_id', $request->deviceId)->first();
        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'このユーザーは既に登録されています。',
            ]);  // 重複するデバイスIDの場合、400エラー（不正なリクエスト）を返す
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
            $user->email = $request->userEmail;
            $user->password = bcrypt($request->userPassword); // Hash the password
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

        $user->anketo_status += 1;

        $user->save();

        if ($questionKey == 'birthdate') {
            Anketo::updateOrCreate(
                ['user_id' => $request->user_id],
                ['animal_fortune_telling' => $birthdate_data['animal_fortune_telling_result']]
            );
            Anketo::updateOrCreate(
                ['user_id' => $request->user_id],
                ['animal_fortune_telling_characteristics' => $birthdate_data['animal_fortune_telling_characteristics']]
            );
        }

        Anketo::updateOrCreate(
            ['user_id' => $request->user_id],
            [$questionKey => $request->content]
        );
   
        // if ($questionKey == 'hobby') {
        //     return response()->json([
        //         'success' => true,
        //         'anketo_status' => $user->anketo_status,
        //         'next_question_text' => "色々と教えてくれてありがとう！！😄 私があなた自身のAIだから、これから何でも相談してね！！😊 早速だけど、何か聞きたいことや言いたいことはある？😊"
        //     ]);
        // }
        if ($questionKey == 'enjoys_change') {
            $anketoData = Anketo::where('user_id', $request->user_id)->first();

            $averageExtraversion = ($anketoData->is_sociable + $anketoData->likes_group_activities + $anketoData->energetic_at_parties + $anketoData->comfortable_public_speaking) / 4;
            $averageAgreeableness = ($anketoData->helpful_to_others + $anketoData->respects_others_opinions + $anketoData->avoids_conflicts + $anketoData->tries_to_be_kind) / 4;
            $averageConscientiousness = ($anketoData->meets_deadlines + $anketoData->plans_ahead + $anketoData->rarely_forgets_things + $anketoData->takes_responsibility) / 4;
            $averageNeuroticism = ($anketoData->easily_anxious + $anketoData->tends_to_feel_down + $anketoData->dwells_on_mistakes + $anketoData->sensitive_to_pressure) / 4;
            $averageOpenness = ($anketoData->likes_new_experiences + $anketoData->interested_in_arts + $anketoData->open_to_new_ideas + $anketoData->enjoys_change) / 4;

            $averageExtraversion = round($averageExtraversion, 2);
            $averageAgreeableness = round($averageAgreeableness, 2);
            $averageConscientiousness = round($averageConscientiousness, 2);
            $averageNeuroticism = round($averageNeuroticism, 2);
            $averageOpenness = round($averageOpenness, 2);

            return response()->json([
                'success' => true,
                'anketo_status' => $user->anketo_status,
                'next_question_text' => $averageExtraversion."/".$averageAgreeableness."/".$averageConscientiousness."/".$averageNeuroticism."/".$averageOpenness."/"."色々と教えてくれてありがとう！！😄 私があなた自身のAIだから、これから何でも相談してね！！😊 早速だけど、何か聞きたいことや言いたいことはある？😊"
            ]);
        }

        $questionRequest = new Request(['question_key' => $user->anketo_status]);
        $questionResponse = $this->getQuestion($questionRequest);
        $questionData = json_decode($questionResponse->getContent(), true);

        if ($questionKey == 'birthdate') {
            $next_question_text = $birthdate_data['animal_fortune_telling_result']."/".$birthdate_data['animal_fortune_telling_characteristics']."/".$questionData['question_text'];
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
        $tableName = app(ChatLogService::class)->getTableName($userId);
    
        // Check if table exists first
        if (!Schema::hasTable($tableName)) {
            return collect(); // Return empty collection if no table exists
        }

        $chatLogs = DB::table($tableName)
                    ->orderBy('created_at', 'desc')
                    ->get();

        return $chatLogs->flatMap(function ($chatLog) {
            return [
                ['text' => $chatLog->question, 'sender' => 'user'],
                ['text' => $chatLog->answer, 'sender' => 'bot'],
            ];
        });
    }

    private function getAnimalSign($birthdate)
    {
        // 入力を分割して取得
        list($year, $month, $day) = explode('.', $birthdate);

        if ($year < 1926) {
            return [
                'animal_fortune_telling_result' => "この世に生きていません！",
                'animal_fortune_telling_characteristics' => "個性なし"
            ];
        } else if ($year > 2030) {
            return [
                'animal_fortune_telling_result' => "まだ生まれていません！",
                'animal_fortune_telling_characteristics' => "個性なし"
            ];
        } else {
            // 1. 年月に該当する数値テーブルを用意
            $unmeisuTable = config('fortune_telling.unmeisu_table');
            
            // 2. テーブルから該当の数値を取得
            $baseNumber = $unmeisuTable[$year][$month] ?? null;

            // 動物リスト
            $animals = config('fortune_telling.animals');
            $animal = $animals[$baseNumber % 60];

            // 動物リスト
            $fortuneCharacteristics = config('fortune_telling.fortune_characteristics');
            $fortuneCharacteristic = $fortuneCharacteristics[$baseNumber % 60];

            return [
                'animal_fortune_telling_result' => $animal,
                'animal_fortune_telling_characteristics' => $fortuneCharacteristic
            ];
        }
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

        // Delete the user's chat log table
        $tableName = app(ChatLogService::class)->getTableName($user->id);
        if (Schema::hasTable($tableName)) {
            Schema::drop($tableName);
        }

        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }
}