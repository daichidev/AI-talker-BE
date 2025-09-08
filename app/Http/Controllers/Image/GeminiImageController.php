<?php

namespace App\Http\Controllers\Image;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GeminiImageController extends Controller
{
    protected $client;
    protected $apiKey;
    protected $modelId;

    // Avatar type descriptions (same as DeepImageController)
    private const MALE_DESCRIPTIONS = [
        1 => [ // キレイ系 (Elegant)
            "A beautifully composed half-body portrait of a refined gentleman, perfectly centered in the frame from head to chest. His posture is poised yet relaxed, with a subtle head tilt and a confident gaze. His facial features are well-defined yet balanced, exuding sophistication without appearing overly rugged. He wears a tailored suit with intricate textures and bold, complementary colors, draping elegantly over his visible upper body and shoulders. The warm, soft lighting highlights his smooth complexion, while the softly blurred background enhances his commanding presence, creating a polished and distinguished look.",
            "A half-body portrait of a man with a glowing complexion and wavy, chestnut-brown hair. His eyes are sharp and expressive, enhanced with natural lighting that accentuates their depth. He wears a dark gray turtleneck sweater layered under a black wool overcoat, exuding effortless winter elegance. His upper body, including shoulders and chest, is clearly visible. The setting is wintery, with a softly blurred snowy background and gentle, diffused lighting that enhances the warmth of his expression.",
        ],
        2 => [ // 近未来系 (Futuristic)
            "A cel-shaded anime-style half-body illustration of a retro-futuristic male android, framed from head to waist in a medium shot composition. His sleek, silvery-white metallic faceplate is smooth and synthetic, with visible seams along the jawline and subtle glowing circuit patterns beneath the surface. His upper body is fully visible, constructed with classic brushed aluminum and steel plating, featuring exposed rivets, mechanical joints, and articulated hydraulic tubing for a vintage sci-fi robot aesthetic. His hair, a blend of synthetic fiber and anime-style vibrancy, is styled in a sleek yet slightly tousled manner with a soft metallic sheen. He wears a stylish, fantasy-inspired robotic ensemble that integrates small gears, glowing circuits, and intricate mechanical details into a structured, imposing design. His pose shows his torso and arms, exuding strength and futuristic elegance. The background is a neon-lit cityscape with flickering holograms and glowing steam vents, adding a dynamic, cyberpunk-inspired atmosphere. The cel-shading is crisp and vibrant, with high-contrast lighting that enhances the metallic reflections and the glow of his laser vision."
        ],
        3 => [ // アニメ系 (Anime)
            "A vibrant cel-shaded anime-style half-body illustration of an energetic and charismatic male protagonist, visible from head to waist. The camera is pulled back to show the upper body, capturing his confident and dynamic pose. He has striking orange hair styled playfully and large, expressive, glossy eyes. He wears a stylish, fantasy-inspired magical outfit with charming details, including a high-collared cape, an enchanted emblem, and elegant gloves. The background features a whimsical, Halloween-themed world with glowing pumpkins, floating magical symbols, and a mysterious yet fun atmosphere. The composition is similar to a mid-shot anime scene, rendered in bright, colorful, crisp cel-shading reminiscent of classic Japanese anime.",
            "A stylish cel-shaded anime-style half-body illustration of a cool and composed male protagonist, in the sleek and dynamic style of SPYxFAMILY. He is framed from head to waist, with his upper body clearly visible. The camera is pulled back to show his pose and outfit, not just his face. He has sharp, well-styled orange hair, slightly tousled yet refined, adding to his intelligent appearance. His eyes are sharp and focused with crisp reflections, and his expression is mature and confident. He wears a tailored suit, long coat, gloves, and polished shoes — the look of an undercover agent. His upper-body pose is sleek and action-ready, exuding elegance and mystery. The background shows a European cityscape with tall buildings and a passing train. The art style is clean and expressive, with polished cel-shading, dramatic lighting, and smooth linework, capturing the essence of a spy-action anime.",
        ],
    ];
    
    private const FEMALE_DESCRIPTIONS = [
        1 => [ // キレイ系 (Elegant)
            "A softly lit portrait of a 20-year-old woman in the elegant and clean style of CityEdgeMix. She has a gentle, composed expression and clear, delicate skin. Her hairstyle is neat and natural, with smooth, well-kept hair that frames her face. She wears a refined and modest outfit, such as a blouse or a simple dress in soft pastel or neutral tones. The background is minimalistic and softly blurred, with natural lighting that gives a serene and graceful ambiance. The overall visual tone is clean, airy, and sophisticated, evoking a sense of quiet elegance, sincerity, and urban purity."
        ],
        2 => [ // 近未来系 (Futuristic)
            "A futuristic portrait of a 20-year-old woman in a high-tech sci-fi setting. She has short, sleek silver-gray hair and large, expressive eyes with subtle mechanical details near her face. Her outfit is a streamlined, form-fitting cybernetic suit with glossy white and black surfaces, featuring digital interfaces and circuitry patterns. The background is a dark, high-tech environment with glowing red and white lines and futuristic panels. The overall visual style is hyper-realistic with cinematic lighting, soft reflections, and a clean, minimalistic sci-fi aesthetic, emphasizing a serene and intelligent expression."
        ],
        3 => [ // アニメ系 (Anime)
            "A medium shot illustration of a 15-year-old girl in the gentle, hand-painted style of Studio Ghibli. The artwork features soft lighting, warm and earthy colors, and subtle textures that evoke a nostalgic, peaceful atmosphere. The girl has a calm, thoughtful expression, captured in a natural pose that reflects everyday life. The background is detailed with everyday objects and interiors, all painted with the watercolor-like brushwork typical of Ghibli films. The overall tone is serene and emotionally expressive, emphasizing the quiet beauty of ordinary moments."
        ],
    ];

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = Config::get('app.gemini_api_key');
        $this->modelId = 'gemini-2.5-flash-image-preview';
    }

    /**
     * Generate avatar using user's image and Gemini API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateAvatar(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'photoPath' => 'required|string',
            'avatar_type' => 'required|integer|in:1,2,3',
            'avatar_gender_type' => 'required|integer|in:0,1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $photoPath = $request->input('photoPath');
            $avatarType = $request->input('avatar_type');
            $avatarGenderType = $request->input('avatar_gender_type');
            $fullPhotoPath = storage_path("app/public/face_id_photos/{$photoPath}");

            // Check if the image file exists
            if (!file_exists($fullPhotoPath)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Image file not found'
                ], 404);
            }

            // Get the appropriate description based on gender and avatar type
            $descriptions = ((int)$avatarGenderType === 1) 
                ? self::FEMALE_DESCRIPTIONS[$avatarType] ?? self::FEMALE_DESCRIPTIONS[3]
                : self::MALE_DESCRIPTIONS[$avatarType] ?? self::MALE_DESCRIPTIONS[3];
            $randomDescription = $descriptions[array_rand($descriptions)];

            Log::info("Starting avatar generation with Gemini API");
            Log::info("Using image: {$fullPhotoPath}");
            Log::info("Avatar type: {$avatarType}, Gender: {$avatarGenderType}");
            Log::info("Using prompt: {$randomDescription}");

            // Read the image file and convert to base64
            $imageData = file_get_contents($fullPhotoPath);
            $imageBase64 = base64_encode($imageData);
            $imageMimeType = mime_content_type($fullPhotoPath);

            // Prepare the request data for Gemini API
            $requestData = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => $randomDescription
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => $imageMimeType,
                                    'data' => $imageBase64
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseModalities' => ['IMAGE']
                ]
            ];

            // Make the API request to Gemini
            $response = $this->client->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->modelId}:generateContent?key={$this->apiKey}",
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $requestData,
                ]
            );

            $responseData = json_decode($response->getBody(), true);
            Log::info("Gemini API response received");

            // Process the response and extract the generated image
            if (isset($responseData['candidates'][0]['content']['parts'])) {
                foreach ($responseData['candidates'][0]['content']['parts'] as $part) {
                    // Check for both camelCase and snake_case formats
                    if (isset($part['inlineData'])) {
                        // Save the generated image
                        $generatedImageFilename = $this->saveGeneratedImage($part['inlineData']['data']);
                        
                        return response()->json([
                            'success' => true,
                            'image_url' => $generatedImageFilename,
                            'message' => 'Avatar generated successfully'
                        ]);
                    } elseif (isset($part['inline_data'])) {
                        // Fallback for snake_case format
                        $generatedImageFilename = $this->saveGeneratedImage($part['inline_data']['data']);
                        
                        return response()->json([
                            'success' => true,
                            'image_url' => $generatedImageFilename,
                            'message' => 'Avatar generated successfully'
                        ]);
                    }
                }
            }

            // If no image was found in the response
            return response()->json([
                'error' => true,
                'message' => 'No image was generated in the response',
                'debug' => $responseData
            ], 500);

        } catch (RequestException $e) {
            Log::error("Gemini API request failed: " . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Failed to generate avatar: ' . $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error("Unexpected error in avatar generation: " . $e->getMessage());
            Log::error("Traceback: " . $e->getTraceAsString());
            return response()->json([
                'error' => true,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save the generated image from base64 data
     *
     * @param string $base64Data
     * @return string
     */
    private function saveGeneratedImage($base64Data)
    {
        // Decode the base64 image data
        $imageData = base64_decode($base64Data);
        
        // Generate a unique filename
        $filename = 'gemini_avatar_' . Str::random(20) . '.png';
        $filePath = storage_path('app/public/processed_images/' . $filename);
        
        // Ensure the directory exists
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Save the image
        file_put_contents($filePath, $imageData);
        
        Log::info("Generated image saved: {$filename}");
        
        return $filename;
    }
}