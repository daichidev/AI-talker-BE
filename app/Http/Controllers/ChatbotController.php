<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Services\ChatLogService;
use App\Services\FriendChatLogService;
use App\Services\NSFWDetectionService;

use App\Services\OpenAIService;
use App\Services\GeminiService;

use App\Models\User;

use Carbon\Carbon;

class ChatbotController extends Controller
{
    protected $openAIService;
    protected $geminiService;
    protected $nsfwDetectionService;

    public function __construct(OpenAIService $openAIService, GeminiService $geminiService, NSFWDetectionService $nsfwDetectionService)
    {
        $this->openAIService = $openAIService;
        $this->geminiService = $geminiService;
        $this->nsfwDetectionService = $nsfwDetectionService;
    }

    public function chat(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        // Check if the message is NSFW
        $isNSFW = $this->nsfwDetectionService->detectNSFW($request->message);
        $requestingUser = User::find($request->user_id);
        if ($isNSFW && $requestingUser->boost_mode > 0) {
            $tableName = app(ChatLogService::class)->ensureUserTableExists($request->user_id);
            $responseData = $this->openAIService->chatVenice($request->message, $request->user_id, $tableName);
            $responseContent = $responseData['choices'][0]['message']['content'] ?? '';
            \Log::info("-------------------------");
            \Log::info($responseData);
            \Log::info("-------------------------");
            DB::table($tableName)->insert([
                'question' => $request->message,
                'answer' => $responseContent,
                'is_nsfw' => false
            ]);
            $requestingUser->boost_mode = $requestingUser->boost_mode - 1;
            $requestingUser->save();
            return response()->json([
                'success' => true,
                'message' => $responseContent,
                'is_nsfw' => false
            ]);
        } else {
            $tableName = app(ChatLogService::class)->ensureUserTableExists($request->user_id);

            $responseData = $this->openAIService->chat($request->user_id, $tableName, $request->message);
            
            // Check if the response is also NSFW
            $responseContent = $responseData['choices'][0]['message']['content'] ?? '';
            
            DB::table($tableName)->insert([
                'question' => $request->message,
                'answer' => $responseContent,
                'is_nsfw' => $isNSFW
            ]);

            return response()->json([
                'success' => true,
                'message' => $responseContent,
                'is_trial_used' => $requestingUser->is_trial_used,
                'is_nsfw' => $isNSFW
            ]);
        }
    }

    public function chatVenice(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        $tableName = app(ChatLogService::class)->ensureUserTableExists($request->user_id);

        $responseData = $this->openAIService->chatVenice($request->message, $request->user_id, $tableName);
        
        $responseContent = $responseData['choices'][0]['message']['content'] ?? '';

        \Log::info("-------------------------");
        \Log::info($responseData);
        \Log::info("-------------------------");
        
        // DB::table($tableName)->insert([
        //     'question' => $request->message,
        //     'answer' => $responseContent,
        // ]);

        return response()->json([
            'success' => true,
            'message' => $responseContent
        ]);
    }

    public function chatWithFriend(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'friend_user_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        // Check if the message is NSFW
        $isNSFW = $this->nsfwDetectionService->detectNSFW($request->message);

        $tableName = app(FriendChatLogService::class)->ensureUserTableExists($request->user_id, $request->friend_user_id);

        $responseData = $this->openAIService->chatWithFriend($request->user_id, $request->friend_user_id, $tableName, $request->message);

        // Check if the response is also NSFW
        $responseContent = $responseData['choices'][0]['message']['content'] ?? '';

        $now = Carbon::now();
        
        DB::table($tableName)->insert([
            'question' => $request->message,
            'answer' => $responseContent,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'success' => true,
            'message' => $responseContent,
            'time' => $now->format('Y-m-d H:i:s'),
            'is_nsfw' => $isNSFW
        ]);
    }

    public function chatWithGemini(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        // Check if the message is NSFW
        $isNSFW = $this->nsfwDetectionService->detectNSFW($request->message);

        $tableName = app(ChatLogService::class)->ensureUserTableExists($request->user_id);

        $responseData = $this->geminiService->chat($request->user_id, $request->message);     

        // Check if the response is also NSFW
        $responseContent = $responseData['choices'][0]['message']['content'] ?? '';

         DB::table($tableName)->insert([
            'question' => $request->message,
            'answer' => $responseContent,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $responseContent,
            'is_nsfw' => $isNSFW
        ]);
    }
}