<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Anketo;
use App\Models\User;
use App\Models\Avatar;
use App\Http\Controllers\DeepImageController;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected $questions = [
        'name', 'user_nickname', 'bot_nickname', 'gender', 'birthdate', 'hometown', 'address',
        'blood_type', 'job', 'hobby', 'email', 'password'
    ];

    public function storeFaceID(Request $request) {
        $request->validate([
            'deviceId' => 'required|string',
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'avatarType' => 'required|integer'
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
        ]);
        $response = $deepImageController->processImage($modifiedRequest);
        $responseData = $response->getData(true);

        if (isset($responseData['image_url'])) {
            $avatarPath = $responseData['image_url'];

             // device IDと写真のパスをデータベースに保存
            $user = new User();
            $user->device_id = $request->deviceId;
            $user->face_photo = $photoPath;
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

        // // deviceIdがusersテーブルに存在することを検証
        $user = User::where('device_id', $request->deviceId)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'デバイスIDが見つからないか、認証に失敗しました。']);
        }

        // 認証が成功した場合
        return response()->json(['success' => true, 'message' => 'Face IDで正常に認証されました。']);
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

        if ($questionKey !== 'email' && $questionKey !== 'password') {
            Anketo::updateOrCreate(
                ['user_id' => $request->user_id, 'question_key' => $questionKey],
                ['content' => $request->content]
            );
        }

        $questionRequest = new Request(['question_key' => $user->anketo_status]);
        $questionResponse = $this->getQuestion($questionRequest);
        $questionData = json_decode($questionResponse->getContent(), true);

        if ($questionData['success']) {
            return response()->json([
                'success' => true,
                'anketo_status' => $user->anketo_status,
                'next_question_text' => $questionData['question_text']
            ]);
        }
    }

    private function getNextQuestion($userId, $currentQuestion) {
        $currentIndex = array_search($currentQuestion, $this->questions);
        if ($currentIndex !== false && isset($this->questions[$currentIndex + 1])) {
            return $this->questions[$currentIndex + 1];
        }
        return null;
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

    public function login(Request $request) 
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
    
        $user = User::with('latestAvatar')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
        ]);
    }
}