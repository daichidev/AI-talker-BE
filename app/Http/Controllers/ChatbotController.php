<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenAIService;
use App\Models\ChatLog;

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

        $responseData = $this->openAIService->chat($request->user_id, $request->message);

        // ChatLog::create([
        //     'user_id' => $request->user_id,
        //     'question' => $request->message,
        //     'answer' => $responseData['choices'][0]['message']['content'],
        // ]);

        return response()->json([
            'success' => true,
            'message' => $responseData['choices'][0]['message']['content']
        ]);
    }
}