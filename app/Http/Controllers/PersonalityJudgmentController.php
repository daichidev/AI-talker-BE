<?php

namespace App\Http\Controllers;
use App\Services\OpenAIService;

class PersonalityJudgmentController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService, GeminiService $geminiService)
    {
        $this->openAIService = $openAIService;
    }

    public function fetchMBTIQuestions()
    {
        $responseData = $this->openAIService->chat();
        return response()->json([
            'success' => true,
            'message' => $responseData['choices'][0]['message']['content']
        ]);
    }
}