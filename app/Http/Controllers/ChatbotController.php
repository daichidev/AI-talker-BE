<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\ChatLogService;
use App\Services\FriendChatLogService;
use App\Services\NSFWDetectionService;
use App\Services\OpenAIService;
use App\Services\GeminiService;
use App\Models\User;
use Carbon\Carbon;

class ChatbotController extends Controller
{
    public function __construct(
        private OpenAIService $openAIService,
        private GeminiService $geminiService,
        private NSFWDetectionService $nsfwDetectionService
    ) {}

    /* =========================
     * Public endpoints
     * ========================= */

    public function chat(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        $userId  = (int) $request->input('user_id');
        $message = (string) $request->input('message');

        /** @var User|null $user */
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        $isVenice = false;
        $isNSFWRequest = (bool) $this->nsfwDetectionService->detectNSFW($message);
        $tableName = app(ChatLogService::class)->ensureUserTableExists($userId);
        if (!Schema::hasColumn($tableName, 'is_nsfw')) {
            DB::statement("ALTER TABLE ".$tableName." ADD COLUMN is_nsfw BOOLEAN DEFAULT FALSE");
        }
        if (!Schema::hasColumn($tableName, 'is_nsfw_content')) {
            DB::statement("ALTER TABLE ".$tableName." ADD COLUMN is_nsfw_content BOOLEAN DEFAULT FALSE");
        }
        $now = Carbon::now();

        // 1) NSFW要求 + ブーストあり → Venice
        if ($isNSFWRequest && $user->boost_mode > 0) {
            $data = $this->openAIService->chatVenice($message, $userId, $tableName);
            $content = $this->extractContent($data);

            $this->insertLog($tableName, $message, $content, false, true, $now);
            $this->consumeBoost($user);

            return response()->json([
                'success' => true,
                'message' => $content,
                'is_nsfw' => false,
            ]);
        }

        // 2) 通常 → OpenAI
        $data = $this->openAIService->chat($userId, $tableName, $message);
        $content = $this->extractContent($data);

        // OpenAI から「false」→ NSFW疑い
        if ($content === 'false') {
            if ($user->boost_mode > 0) {
                // Venice にフォールバック
                $data = $this->openAIService->chatVenice($message, $userId, $tableName);
                $content = $this->extractContent($data);
                $this->consumeBoost($user);
                $isNSFW = 0;
                $isVenice = true;
            } else {
                $content = "申し訳ありませんが、その内容にはお答えできません。別の質問をお願いします。";
                $isNSFW = 1;
            }
        } else {
            $isNSFW = (int) $isNSFWRequest;
        }

        $this->insertLog($tableName, $message, $content, (bool) $isNSFW, $isVenice, $now);

        return response()->json([
            'success'       => true,
            'message'       => $content,
            'is_trial_used' => (bool) $user->is_trial_used,
            'is_nsfw'       => (bool) $isNSFW,
        ]);
    }

    public function chatWithFriend(Request $request)
    {
        $request->validate([
            'user_id'        => 'required|integer',
            'friend_user_id' => 'required|integer',
            'message'        => 'required|string',
        ]);

        $userId   = (int) $request->input('user_id');
        $friendId = (int) $request->input('friend_user_id');
        $message  = (string) $request->input('message');
        $isVenice = false;
        /** @var User|null $user */
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $isNSFWRequest = (bool) $this->nsfwDetectionService->detectNSFW($message);
        $tableName = app(FriendChatLogService::class)->ensureUserTableExists($userId, $friendId); // ← 友達用を常に使用
        $now = Carbon::now();
        if (!Schema::hasColumn($tableName, 'is_nsfw')) {
            DB::statement("ALTER TABLE ".$tableName." ADD COLUMN is_nsfw BOOLEAN DEFAULT FALSE");
        }
        if (!Schema::hasColumn($tableName, 'is_nsfw_content')) {
            DB::statement("ALTER TABLE ".$tableName." ADD COLUMN is_nsfw_content BOOLEAN DEFAULT FALSE");
        }

        // 1) NSFW要求 + ブーストあり → Venice Friend
        if ($isNSFWRequest && $user->boost_mode > 0) {
            $data = $this->openAIService->chatWithVeniceFriend($userId, $friendId, $tableName, $message);
            $content = $this->extractContent($data);

            $this->insertLog($tableName, $message, $content, false, true, $now);
            $this->consumeBoost($user);

            return response()->json([
                'success' => true,
                'message' => $content,
                'time'    => $now->format('Y-m-d H:i:s'),
                'is_nsfw' => false,
            ]);
        }

        // 2) 通常 → OpenAI Friend
        $data = $this->openAIService->chatWithFriend($userId, $friendId, $tableName, $message);
        $content = $this->extractContent($data);

        if ($content === 'false') {
            if ($user->boost_mode > 0) {
                $isVenice = true;
                $data = $this->openAIService->chatWithVeniceFriend($userId, $friendId, $tableName, $message);
                $content = $this->extractContent($data);
                $this->consumeBoost($user);
                $isNSFW = 0;
            } else {
                $content = "申し訳ありませんが、その内容にはお答えできません。別の質問をお願いします。";
                $isNSFW = 1;
            }
        } else {
            $isNSFW = (int) $isNSFWRequest;
        }

        \Log::info('++++++++++++++++++++++++++++++++++++++', ['content' => $content]);
        $this->insertLog($tableName, $message, $content, (bool) $isNSFW, $isVenice, $now);

        return response()->json([
            'success' => true,
            'message' => $content,
            'time'    => $now->format('Y-m-d H:i:s'),
            'is_trial_used' => (bool) $user->is_trial_used,
            'is_nsfw' => (bool) $isNSFW,
        ]);
    }

    public function chatWithGemini(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        $userId  = (int) $request->input('user_id');
        $message = (string) $request->input('message');

        $isNSFWRequest = (bool) $this->nsfwDetectionService->detectNSFW($message);
        $tableName = app(ChatLogService::class)->ensureUserTableExists($userId);
        $now = Carbon::now();

        $data = $this->geminiService->chat($userId, $message);
        $content = $this->extractContent($data);

        $this->insertLog($tableName, $message, $content, $isNSFWRequest, $now);

        return response()->json([
            'success' => true,
            'message' => $content,
            'is_nsfw' => $isNSFWRequest,
        ]);
    }

    /* =========================
     * Helpers
     * ========================= */

    /**
     * LLMレスポンスから content を安全に取り出す
     */
    private function extractContent(array $response): string
    {
        // 失敗時はdetailやmessageを返す
        $content = $response['choices'][0]['message']['content'] ?? null;
        if (is_string($content) && $content !== '') {
            return $content;
        }

        if (!empty($response['error'])) {
            $fallback = $response['detail'] ?? $response['message'] ?? null;
            if (is_string($fallback) && $fallback !== '') {
                return "エラーが発生しました：{$fallback}";
            }
        }

        return 'すみません、うまく答えが作れませんでした。もう一度質問してください。';
    }

    /**
     * チャットログを保存
     */
    private function insertLog(string $tableName, string $question, string $answer, bool $isNSFW, bool $isNSFWContent, Carbon $now): void
    {
        DB::table($tableName)->insert([
            'question'   => $question,
            'answer'     => $answer,
            'is_nsfw'    => $isNSFW,
            'is_nsfw_content'    => $isNSFWContent,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * ブーストを1消費（下限0）
     */
    private function consumeBoost(User $user): void
    {
        $user->boost_mode = max(0, (int)$user->boost_mode - 1);
        $user->save();
    }
}
