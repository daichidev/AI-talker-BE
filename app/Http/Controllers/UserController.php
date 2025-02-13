<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Anketo;
use App\Models\User;
use App\Models\Avatar;
use App\Http\Controllers\DeepImageController;

class UserController extends Controller
{
    public function signup(Request $request) {
        $psychological_test_1 = '';
        switch ($request->psychological_test_1) {
            case '1':
                $psychological_test_1 = 'あなたは、少し気が弱くリーダーシップをとるタイプではないかもしれません。そのため、優れたナンバー2や実力者のサブ役という立場になれるでしょう。秘書や経理など、技能を活かした仕事一本に絞ることで、かなりの財産を築くことができるでしょう。';
                break;
            case '2':
                $psychological_test_1 = 'あなたは、鋭いインスピレーションに優れており、非常に洞察力があるでしょう。頭の回転の速さと素早い決断力でチャンスをモノにしていけますが、人から妬まれやすいので、人間関係には気をつけたほうが良いでしょう。';
                break;
            case '3':
                $psychological_test_1 = 'あなたは縁の下の力持ちで、どんなシチュエーションでもなくてはならないタイプ。一大出世や目立つポジションにはあまりご縁がないかもしれませんが、穏やかで安定感のある生活を送ることができるでしょう。ひとつのことに対して、真面目にコツコツと向き合うことで何らかのエキスパートになれるケースも多し。';
                break;
            default:
                $psychological_test_1 = 'あなたは、思い立ったことを次から次へと行動に移していく才能を持っています。アカデミックな社会的成功に恵まれるタイプですが、精神面を重視するため、物質的なことには無頓着かもしれません。独創的な趣味を持ちやすく、美的センスにも優れているでしょう。';
                break;
        };

        $psychological_test_2 = '';
        switch ($request->psychological_test_2) {
            case '1':
                $psychological_test_2 = '高い塔は目標や意志の象徴。あなたは自分の決めた目標に向かって、努力する人です。ただ、「頑張らなきゃいけない」という価値観に縛られて、休むことに抵抗を感じているのかも。そんなあなたの裏の顔は、ゆっくりとリラックスしていたいという欲求です。パフォーマンスを高めるには、回復する時間が必要不可欠。オンとオフを切り替えて、オフの時間を楽しむ姿を表現してみましょう。楽しむときと集中するときの区別をつけることができれば、前に進んでいく力になるでしょう。';
                break;
            case '2':
                $psychological_test_2 = '彫刻刀は創造性の象徴。あなたはクリエイティブに物事を考えて、新しいことにチャレンジする人です。ただ、自分の考えや意見を相手に合わせることが苦手な傾向にあるようです。そんなあなたの裏の顔は、「周りにどうに合わせていいかわからない」という戸惑いです。周りの人たちと良い関係を築くには、相手の話に耳を傾けることが大事。話を最後まで聴ききったあとに、自分の伝えたい気持ちを伝えていきましょう。アサーション力を高めれば、あなたの創造力がぐっと高まるでしょう。';
                break;
            case '3':
                $psychological_test_2 = 'ろうそくは未来の不安を表します。あなたは短時間で集中して物事に取り組むタイプです。最初は勢いがあるものの、徐々にペースが落ちてしまう一面も。そんなあなたの裏側には、不安を隠すために前向きな思考を保とうとするネガティブな顔が。成果が得られないことを恐れ、いつも希望を求めているのでは？ 物事を繊細に感じることができるあなたであれば、元気になれる言葉を口に出し続けていけば、徐々に気持ちが安定していくはずです。まずは前向きな発言を意識していきましょう。';
                break;
            default:
                $psychological_test_2 = '逆向きのマイクは自己主張の象徴。あなたはいま、自分の気持ちを誰かに理解してほしいと感じているのかも。ただ、あなたの感じている想いをどんな風に相手に伝えたらいいか悩んでしまうことも多いのでは？ そんなあなたの裏側には、自由にのびのびと素の自分を表現できる社交性の高さが隠れています。あなたの心の内側には、誰かと共に共有し、分かち合いたいという寂しさが潜んでいるはず。その寂しさをあなた自身が理解してあげることがありのままの自分を表現する一歩です。';
                break;
        };

        $content = 'あなたの名前を教えてください！' . $request->name . '。' . '生年月日を教えてください！' . $request->birthday . '。' . '住所を教えてください！' . $request->address . '。' . '血液型を教えてください！' . $request->blood_type . '。' . '性別を教えてください！' . $request->gender . '。' . $psychological_test_1 . $psychological_test_2;

        $anketo = new Anketo();
        $anketo->content = $content;
        $anketo->user_id = rand(1, 1000);
        $anketo->save();

        return response()->json(['message' => 'データが正常に保存されました！', 'anketo' => $anketo], 201);
    }

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

        $deepImageController = new DeepImageController();
        $modifiedRequest = new Request([
            'photoPath' => $photoPath,
            'avatar_type' => $request->avatarType,
        ]);
        $avatarPath = $deepImageController->processImage($modifiedRequest);

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
            'userId' => $user->id,
            'avatarPath' => $avatarPath  
        ]);
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
}
