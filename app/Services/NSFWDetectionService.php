<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NSFWDetectionService
{
    private $apiKey;
    private $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->apiUrl = 'https://api.openai.com/v1/moderations';
    }

    /**
     * Detect if the content is NSFW using OpenAI's moderation API
     */
    public function isNSFW(string $content): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'input' => $content
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['results'][0]['flagged'])) {
                    $flagged = $data['results'][0]['flagged'];
                    
                    // Check for sexual content, violence, or other inappropriate content
                    $categories = $data['results'][0]['categories'] ?? [];
                    $scores = $data['results'][0]['category_scores'] ?? [];
                    
                    // Consider content NSFW if flagged or if sexual content score is high
                    if ($flagged || ($scores['sexual'] ?? 0) > 0.7) {
                        Log::info('NSFW content detected', [
                            'content' => substr($content, 0, 100) . '...',
                            'flagged' => $flagged,
                            'scores' => $scores
                        ]);
                        return true;
                    }
                }
            } else {
                Log::error('NSFW detection API failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('NSFW detection error', [
                'message' => $e->getMessage(),
                'content' => substr($content, 0, 100) . '...'
            ]);
        }

        return false;
    }

    /**
     * Simple keyword-based NSFW detection as fallback
     */
    public function isNSFWByKeywords(string $content): bool
    {
        $nsfwKeywords = [
            // Sexual content
            'sex', 'sexual', 'porn', 'pornography', 'nude', 'naked', 'breast', 'penis', 'vagina',
            'fuck', 'fucking', 'fucked', 'shit', 'bitch', 'asshole', 'dick', 'pussy',
            'orgasm', 'masturbation', 'erotic', 'fetish', 'bdsm',
            
            // Violence
            'kill', 'killing', 'murder', 'suicide', 'violence', 'blood', 'gore', 'torture',
            'rape', 'abuse', 'assault', 'weapon', 'gun', 'knife', 'bomb',
            
            // Drugs
            'drug', 'cocaine', 'heroin', 'marijuana', 'weed', 'cannabis', 'alcohol abuse',
            'overdose', 'addiction',
            
            // Other inappropriate content
            'hate speech', 'racist', 'discrimination', 'harassment', 'threat', 'illegal'
        ];

        $content = strtolower($content);
        
        foreach ($nsfwKeywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                Log::info('NSFW keyword detected', [
                    'keyword' => $keyword,
                    'content' => substr($content, 0, 100) . '...'
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Main method to detect NSFW content using both API and keyword detection
     */
    public function detectNSFW(string $content): bool
    {
        // First try API-based detection
        if ($this->isNSFW($content)) {
            return true;
        }

        // Fallback to keyword-based detection
        return $this->isNSFWByKeywords($content);
    }
}
