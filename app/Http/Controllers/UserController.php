<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Anketo;
use App\Models\User;
use App\Models\Avatar;
use App\Models\PersonalityTest;
use App\Models\Profile;
use App\Models\Syncro;
use App\Models\Report;

// use App\Http\Controllers\Image\DeepImageController;
use App\Http\Controllers\Image\GeminiImageController;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use App\Services\ChatLogService;
use App\Services\FriendChatLogService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; 
use Carbon\Carbon;

class UserController extends Controller
{
    protected $questions = [
        'name', 'birthdate', 'gender', 'user_nickname', 'bot_nickname', 'hometown', 'address',
        'blood_type', 'job', 'hobby'
    ];

    public function storeFaceID(Request $request) {
        $request->validate([
            'deviceId' => 'required|string',
            'fcmDeviceToken' => 'required|string',
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
                'message' => 'このメールアドレスは既に使用中です。'
            ]);
        }
        
        // デバイスIDが既にデータベースに存在するか確認
        $existingUser = User::where('device_id', $request->deviceId)->first();
        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'このデバイスIDは既に使用中です。'
            ]);  // 重複するデバイスIDの場合、400エラー（不正なリクエスト）を返す
        }

        // 写真をstorage/app/public/face_id_photosに保存
        $photoPath = $request->file('photo')->store('face_id_photos', 'public');

        // ファイル名のみを取得
        $filename = basename($photoPath);

        // $deepImageController = new DeepImageController();
        $geminiImageController = new GeminiImageController();
        $modifiedRequest = new Request([
            'photoPath' => $filename,
            'avatar_type' => $request->avatarType,
            'avatar_gender_type' => $request->avatarGenderType
        ]);
        // $response = $deepImageController->processImage($modifiedRequest);
        $response = $geminiImageController->generateAvatar($modifiedRequest);
        $responseData = $response->getData(true);

        if (isset($responseData['image_url'])) {
            $avatarPath = $responseData['image_url'];
            
             // device IDと写真のパスをデータベースに保存
            $user = new User();
            $user->fcm_device_token = $request->fcmDeviceToken;
            $user->device_id = $request->deviceId;
            $user->face_photo = $photoPath;
            // $user->face_photo = 'test.jpg';
            $user->email = $request->userEmail;
            $user->password = bcrypt($request->userPassword); // Hash the password
            $user->first_login_datetime_on_today = Carbon::now();
            $user->save();
    
            $avatar = new Avatar();
            $avatar->avatar_link = $avatarPath;
            $avatar->user_id = $user->id;
            $avatar->save();
            
            $syncro = new Syncro();
            $syncro->user_id = $user->id;
            $syncro->score_login = 1;
            $syncro->save();

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

        $user = User::where('device_id', $request->deviceId)->first();

        $today = Carbon::today();
        $firstLoginToday = $user->first_login_datetime_on_today ? Carbon::parse($user->first_login_datetime_on_today)->isSameDay($today) : false;
        
        if (!$firstLoginToday) {
            $user->first_login_datetime_on_today = Carbon::now();
            $user->save();
            
            $syncro = Syncro::firstOrCreate(
                ['user_id' => $user->id],
                ['score_login' => 0]
            );
            $syncro->score_login += 1;
            $syncro->save();
        }
    
        $this->updateFCMDeviceToken($user->id, $request->fcm_device_token);

        return $this->authenticateUser(['device_id' => $request->deviceId]);
    }

    public function updateFCMDeviceToken($id, $token) {
        $user = User::find($id);
        $user->fcm_device_token = $token;
        $user->save();
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
        $user = User::where('email', $request->email)->first();

        $today = Carbon::today();
        $firstLoginToday = $user->first_login_datetime_on_today ? Carbon::parse($user->first_login_datetime_on_today)->isSameDay($today) : false;
        
        if (!$firstLoginToday) {
            $user->first_login_datetime_on_today = Carbon::now();
            $user->save();
            
            $syncro = Syncro::firstOrCreate(
                ['user_id' => $user->id],
                ['score_login' => 0]
            );
            $syncro->score_login += 1;
            $syncro->save();
        }
        
        $this->updateFCMDeviceToken($user->id, $request->fcm_device_token);
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
        $jobCategories = [
            '学生' => ['中学生', '高校生', '大学生', '大学院生', '浪人生', "その他"],
            '会社員' => ['正社員', '契約社員', '派遣社員', '会社役員', '短時間社員', '準社員', '臨時社員', '業務委託', "その他"],
            '経営者' => ['会長', '社長', '取締役', '店長', '個人事業主', "その他"],
            '公務員' => ['国家公務員', '地方公務員', '自衛隊', '警察', '消防', "その他"],
            '主婦' => ['主婦', '主夫', "その他"]
        ];

        $bigJobCategories = [
            "学生",
            "会社員",
            "経営者",
            "公務員",
            "パート／アルバイト",
            "主婦",
            "無職",
        ];

        $selectedJob = $request->content;

        if (in_array($selectedJob, $bigJobCategories)) {
            Profile::updateOrCreate(
                ['user_id' => $request->user_id],
                ['job' => $request->content]
            );
        }

        if (array_key_exists($selectedJob, $jobCategories)) {
            return response()->json([
                'success' => true,
                'anketo_status' => $user->anketo_status,
                'next_question_text' => implode(", ", $jobCategories[$selectedJob])
            ]);
        } else if ($questionKey === 'job' && $selectedJob === 'その他') {
            return response()->json([
                'success' => true,
                'anketo_status' => $user->anketo_status,
                'next_question_text' => "職業を教えてください！"
            ]);
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

            $syncro = Syncro::where('user_id', $request->user_id)->first();
            $syncro->done_animal_fortune = true;
            $syncro->save();
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
            
            Profile::updateOrCreate(
                ['user_id' => $request->user_id],
                ['animal_fortune_telling_result' => $birthdate_data['animal_fortune_telling_result']]
            );
        }
        
        Anketo::updateOrCreate(
            ['user_id' => $request->user_id],
            [$questionKey => $request->content]
        );
        
        if ($questionKey === 'job') {
            $profile = Profile::where('user_id', $request->user_id)->first();
            $isJobNull = is_null($profile?->job);

            if (!in_array($selectedJob, $bigJobCategories) && !$isJobNull) {
                Profile::updateOrCreate(
                    ['user_id' => $request->user_id],
                    ['position' => $request->content]
                );
            } else {
                Profile::updateOrCreate(
                    ['user_id' => $request->user_id],
                    ['job' => $request->content]
                );
            }
        } else if ($questionKey !== 'user_nickname') {
            Profile::updateOrCreate(
                ['user_id' => $request->user_id],
                [$questionKey => $request->content]
            );
        }

        if ($questionKey == 'hobby') {
            $nowUser = User::with(['anketos', 'profile'])->find($request->user_id);
            $anketoData = $nowUser->anketos;
            $profileData = $nowUser->profile;
            $animal_fortune_telling_result = Anketo::select('animal_fortune_telling_characteristics')
                ->where('user_id', '=', $request->user_id)
                ->first(); 

            return response()->json([
                'success' => true,
                'anketo_status' => $user->anketo_status,
                'next_question_text' => "色々教えてくれてありがとう！私が " . ($profileData->name ?? $anketoData['name'])  . " の分身のAIです。今の性格は【" . ($animal_fortune_telling_result ? $animal_fortune_telling_result->animal_fortune_telling_characteristics : '不明') . "】です。\n合ってますか？\nさらにプロフィールを記入したり、性格判断をして、会話を重ねるともっと " . ($profileData->name ?? $anketoData['name'])  . " の分身に成長するよ。" . ($profileData->name ?? $anketoData['name'])  . " の事を理解してるAIになるので悩みとか色々相談してね"
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
    
        if (!Schema::hasTable($tableName)) {
            return collect();
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

    public function getAnimalSign($birthdate)
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
            $unmeisuTable = config('fortune_telling.unmeisuTable');
            
            // 2. テーブルから該当の数値を取得
            $convertMonth = (int)$month;
            $baseNumber = $unmeisuTable[$year][$convertMonth] ?? null;
            $convertDay = (int)$day;
            $totalNumber = ($baseNumber + $convertDay) > 60 ? ($baseNumber + $convertDay) - 60 : $baseNumber + $convertDay;

            // 動物リスト
            $animals = config('fortune_telling.animals');
            $animal = $animals[$totalNumber];

            // 動物リスト
            $fortuneCharacteristics = config('fortune_telling.fortuneCharacteristics');
            $fortuneCharacteristic = $fortuneCharacteristics[$totalNumber];

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

        $tableName = app(ChatLogService::class)->getTableName($user->id);
        if (Schema::hasTable($tableName)) {
            Schema::drop($tableName);
        }

        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }

    public function personalityTest(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'personality_answers' => 'required|array|size:20',
            'personality_answers.*' => 'integer',
        ]);

        // Calculate averages
        $averageExtraversion = ($request->personality_answers[0] + $request->personality_answers[1] + $request->personality_answers[2] + $request->personality_answers[3]) / 4;
        $averageAgreeableness = ($request->personality_answers[4] + $request->personality_answers[5] + $request->personality_answers[6] + $request->personality_answers[7]) / 4;
        $averageConscientiousness = ($request->personality_answers[8] + $request->personality_answers[9] + $request->personality_answers[10] + $request->personality_answers[11]) / 4;
        $averageNeuroticism = ($request->personality_answers[12] + $request->personality_answers[13] + $request->personality_answers[14] + $request->personality_answers[15]) / 4;
        $averageOpenness = ($request->personality_answers[16] + $request->personality_answers[17] + $request->personality_answers[18] + $request->personality_answers[19]) / 4;

        $averageExtraversion = round($averageExtraversion, 2);
        $averageAgreeableness = round($averageAgreeableness, 2);
        $averageConscientiousness = round($averageConscientiousness, 2);
        $averageNeuroticism = round($averageNeuroticism, 2);
        $averageOpenness = round($averageOpenness, 2);

        // Store the personality answers and mean values in the personality_tests table
        $personalityTest = PersonalityTest::where('user_id', $request->user_id)->first();
        if (!$personalityTest) {
            $personalityTest = new PersonalityTest();
            $personalityTest->user_id = $request->user_id;
        }
        $personalityTest->personality_answers_array = json_encode($request->personality_answers);
        $personalityTest->mean_values_array = json_encode([ $averageExtraversion, $averageAgreeableness, $averageConscientiousness, $averageNeuroticism, $averageOpenness ]);
        $personalityTest->save();

        $syncro = Syncro::where('user_id', $request->user_id)->first();
        $syncro->done_big5_analysis = true;
        $syncro->save();

        return response()->json([
            'success' => true,
            'personality_test' => $personalityTest,
            'message' => '性格診断の結果を保存しました。',
        ]);
    }

    public function getPersonalityTest($id)
    {
        $personalityTest = PersonalityTest::where('user_id', $id)->first();

        if (!$personalityTest) {
            return response()->json([
                'success' => false,
                'message' => '性格診断の結果が見つかりません。'
            ]);
        }

        return response()->json([
            'success' => true,
            'personality_test' => $personalityTest
        ]);
    }

    public function postReport($id, Request $request)
    {
        $request->validate([
            'reportType' => 'required|integer',
            'reportText' => 'required|string',
        ]);

        $report = new Report();
        $report->user_id = $id;
        $report->report_type = $request->reportType;
        $report->report_text = $request->reportText;
        $report->save();

        return response()->json(['success' => true, 'message' => 'レポートを保存しました。']);
    }

    public function resetAvatar(Request $request) {
        $request->validate([
            'userId' => 'required|integer',
            'photo' => 'required|image|mimes:jpeg,png,jpg',
            'avatarType' => 'required|integer',
            'avatarGenderType' => 'required|integer',
        ]);

        // 写真をstorage/app/public/face_id_photosに保存
        $photoPath = $request->file('photo')->store('face_id_photos', 'public');

        // ファイル名のみを取得
        $filename = basename($photoPath);

        // $deepImageController = new DeepImageController();
        $geminiImageController = new GeminiImageController();
        $modifiedRequest = new Request([
            'photoPath' => $filename,
            'avatar_type' => $request->avatarType,
            'avatar_gender_type' => $request->avatarGenderType
        ]);
        // $response = $deepImageController->processImage($modifiedRequest);
        $response = $geminiImageController->generateAvatar($modifiedRequest);
        $responseData = $response->getData(true);

        if (isset($responseData['image_url'])) {
            $avatarPath = $responseData['image_url'];
           
            $user = User::find($request->userId);
            $user->face_photo = $photoPath;
            $user->save();

            $avatar = Avatar::where('user_id', $request->userId)->first();
            $avatar->avatar_link = $avatarPath;
            $avatar->save();

            return response()->json([
                'success' => true, 
                'avatarPath' => $avatarPath  
            ]);
        } else {
            return response()->json(['success' => false, 'message' => '画像の処理に失敗しました。'], 500);
        }
    }

    public function getDeleteAccount()
    {
        return view('user.delete-account');
    }

    public function postDeleteAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'delete_reason' => 'required|string',
            'delete_reason_detail' => 'nullable|string',
            'confirm_profile' => 'required|accepted',
            'confirm_avatar' => 'required|accepted',
            'confirm_chat' => 'required|accepted',
            'confirm_matching' => 'required|accepted',
            'confirm_irreversible' => 'required|accepted',
        ]);

        // Find user by email
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return redirect()->back()->with('error', '指定されたメールアドレスのアカウントが見つかりません。');
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return redirect()->back()->with('error', 'パスワードが正しくありません。');
        }

        try {
            // Start database transaction
            DB::beginTransaction();

            // Delete user's personal chat logs table
            $chatLogService = app(ChatLogService::class);
            $personalTableDropped = $chatLogService->dropUserTable($user->id);

            // Delete friend chat logs tables where this user is involved
            $friendChatLogService = app(FriendChatLogService::class);
            
            // Get all friend IDs from the user's friend list
            $friendIds = json_decode($user->friend_users, true) ?: [];
            
            // Drop friend chat tables where this user is the primary user
            $droppedFriendTables = [];
            foreach ($friendIds as $friendId) {
                if ($friendChatLogService->dropFriendTable($user->id, $friendId)) {
                    $droppedFriendTables[] = "chat_logs_{$user->id}_{$friendId}";
                }
            }
            
            // Drop friend chat tables where this user is the friend
            $droppedInverseTables = $friendChatLogService->dropAllTablesForUser($user->id);

            // Delete related data
            Avatar::where('user_id', $user->id)->delete();
            Anketo::where('user_id', $user->id)->delete();
            Profile::where('user_id', $user->id)->delete();
            PersonalityTest::where('user_id', $user->id)->delete();
            Syncro::where('user_id', $user->id)->delete();
            Report::where('user_id', $user->id)->delete();

            // Delete user's personal access tokens
            $user->tokens()->delete();

            // Delete the user
            $user->delete();

            // Commit transaction
            DB::commit();

            return redirect()->back()->with('success', 'アカウントと関連データが正常に削除されました。ご利用いただき、ありがとうございました。');
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            return redirect()->back()->with('error', 'アカウント削除中にエラーが発生しました。しばらく時間をおいて再度お試しください。');
        }
    }

    public function handleInvite(Request $request){
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'friend_id' => 'required|integer'
        ]);
    
        // 両方のユーザーを取得
        $user = User::find($request->user_id);
        $friend = User::find($request->friend_id);
        
        // 既にフレンドかどうかをチェック（双方向チェック）
        $user_friends = json_decode($user->friend_users, true) ?: [];
        $friend_friends = json_decode($friend->friend_users, true) ?: [];
        
        // 既にフレンドかどうかをチェック
        if (in_array((int)$request->friend_id, $user_friends) && 
            in_array((int)$request->user_id, $friend_friends)) {
            return response()->json(['success' => false, 'message' => '既にフレンドです。']);
        }
        
        // ユーザーのフレンドリストにfriend_idがまだない場合は追加
        if (!in_array((int)$request->friend_id, $user_friends)) {
            $user_friends[] = (int)$request->friend_id;
            $user->friend_users = json_encode(array_values($user_friends));
            $user->save();
        }
        
        // フレンドのフレンドリストにuser_idがまだない場合は追加
        if (!in_array((int)$request->user_id, $friend_friends)) {
            $friend_friends[] = (int)$request->user_id;
            $friend->friend_users = json_encode(array_values($friend_friends));
            $friend->save();
        }
        
        // 同期スコアを更新
        $syncro = Syncro::firstOrCreate(
            ['user_id' => $request->user_id],
            ['score_friend_invite_sent' => 0]
        );
        $syncro->score_friend_invite_sent += 1;
        $syncro->save();
    
        $syncro = Syncro::firstOrCreate(
            ['user_id' => $request->friend_id],
            ['score_friend_invite_received' => 0]
        );
        $syncro->score_friend_invite_received += 1;
        $syncro->save();
    
        return response()->json(['success' => true, 'message' => '招待を受け入れました。']);
    }

    public function useTrialBoostMode(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        if ($user->is_trial_used) {
            return response()->json([
                'success' => false,
                'message' => '既に無料トライアルのブーストモードを使用しています。'
            ]);
        }

        $user->boost_mode = 10;
        $user->is_trial_used = true;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => '無料トライアルのブーストモードを使用しました。',
            'new_boost_mode' => $user->boost_mode
        ]);
    }

    public function purchaseBoostMode(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'boost_count' => 'required|integer|min:1'
        ]);

        $user = User::find($request->user_id);
        $user->boost_mode = $user->boost_mode + $request->boost_count;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'ブーストモードを購入しました。',
            'new_boost_mode' => $user->boost_mode
        ]);
    }
}