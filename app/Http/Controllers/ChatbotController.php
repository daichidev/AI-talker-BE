<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Services\OpenAIService;
use App\Services\ChatLogService;

class ChatbotController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function chat(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        $tableName = app(ChatLogService::class)->ensureUserTableExists($request->user_id);

        $responseData = $this->openAIService->chat($request->user_id, $request->message);

         DB::table($tableName)->insert([
            'question' => $request->message,
            'answer' => $responseData['choices'][0]['message']['content'],
        ]);

        return response()->json([
            'success' => true,
            'message' => $responseData['choices'][0]['message']['content']
        ]);
    }
}