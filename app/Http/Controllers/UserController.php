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
        'name', 'gender', 'birthdate', 'hometown', 'address',
        'blood_type', 'education', 'hobby', 'email', 'password'
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
            'question_key' => 'required|in:' . implode(',', $this->questions),
            'content' => 'required|string'
        ]);

        $user = User::find($request->user_id);

        if ($request->question_key == 'email') {
            $existingUser = User::where('email', $request->content)->first();
            
            if ($existingUser && $existingUser->id != $user->id) {
                return response()->json([
                    'success' => true,
                    'next_question_key' => 'email',
                    'next_question_text' => 'すでに同じメールが存在しています。 別のメールを入力してください。'
                ]);
            }

            $user->email = $request->content;
        }

        if ($request->question_key == 'password') {
            $user->password = Hash::make($request->content);
        }

        $user->save();

        if ($request->question_key !== 'email' && $request->question_key !== 'password') {
            Anketo::updateOrCreate(
                ['user_id' => $request->user_id, 'question_key' => $request->question_key],
                ['content' => $request->content]
            );
        }

        $nextQuestionKey = $this->getNextQuestion($request->user_id, $request->question_key);

        if ($nextQuestionKey) {
            $questionRequest = new Request(['question_key' => $nextQuestionKey]);
            $questionResponse = $this->getQuestion($questionRequest);
            $questionData = json_decode($questionResponse->getContent(), true);

            if ($questionData['success']) {
                return response()->json([
                    'success' => true,
                    'next_question_key' => $nextQuestionKey,
                    'next_question_text' => $questionData['question_text']
                ]);
            }
        }
    
        return response()->json([
            'success' => true,
            'next_question_key' => null,
            'next_question_text' => null
        ]);
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
            'question_key' => 'required|string',
        ]);

        $questions = config('anketo_question');

        if (!array_key_exists($request->question_key, $questions)) {
            return response()->json(['success' => false, 'message' => '質問が見つかりません。'], 404);
        }

        return response()->json([
            'success' => true,
            'question_key' => $request->question_key,
            'question_text' => $questions[$request->question_key],
        ]);
    }
}