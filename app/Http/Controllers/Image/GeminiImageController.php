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
           "A beautifully composed half-body portrait of a refined gentleman, perfectly centered in the frame from head to chest. His posture is poised yet relaxed, with a subtle head tilt and a confident gaze. His facial features are well-defined yet balanced, exuding sophistication without appearing overly rugged. He wears a tailored suit with intricate textures and bold, complementary colors, draping elegantly over his visible upper body and shoulders. The warm, soft lighting highlights his smooth complexion, while the softly blurred background enhances his commanding presence, creating a polished and distinguished look. Generate a realistic portrait that accurately displays the actual face, facial features, skin tone, and hairstyle of the person in the uploaded image. The result should closely resemble the original photo.",
            "A half-body portrait of a man with a glowing complexion and wavy, chestnut-brown hair. His eyes are sharp and expressive, enhanced with natural lighting that accentuates their depth. He wears a dark gray turtleneck sweater layered under a black wool overcoat, exuding effortless winter elegance. His upper body, including shoulders and chest, is clearly visible. The setting is wintery, with a softly blurred snowy background and gentle, diffused lighting that enhances the warmth of his expression. Generate a realistic portrait that accurately displays the actual face, facial features, skin tone, and hairstyle of the person in the uploaded image. The result image's face should closely resemble the original photo's face.",
        ],
        2 => [ // 近未来系 (Futuristic)
            "Please create a male robot human. To make the robot human appear more realistic, it's best to depict a space or battlefield setting. Create a robot with natural yet agile movements. The robot's face must exactly match the uploaded image's actual face. please use skin to create the robot's face. Move the camera forward so that the half-body figure is visible. The robot should have a sleek, futuristic design with metallic and synthetic materials, featuring glowing accents and intricate mechanical details. The background should be a high-tech environment with holographic displays and advanced technology. The overall visual style should be clean, sharp, and highly detailed, emphasizing a calm and intellectual expression. Use cinematic lighting, soft reflections, and a clean, minimalist sci-fi aesthetic. Create a portrait that accurately captures the actual face and features of the subject in the uploaded image. The resulting image's face must be identical to the original photo's face.",
        ],
        3 => [ // アニメ系 (japan anime)
            "Generate a realistic portrait that accurately displays the actual face, facial features, skin tone, and hairstyle of the person in the uploaded image. The result should closely resemble the original photo. It is OK even if you replicate actual face. Actual face is important. The image should be in 4K resolution, highly detailed, and visually stunning. Create only one of four variations of an japan anime-style male protagonist (upper body, head to waist, or cel-shaded). Each variation should follow one of these themes:1. Energetic and charismatic magical hero in a Halloween-themed world with glowing pumpkins and whimsical magical symbols. Outfit: high-collared cape, enchanted emblem, elegant gloves. Bright and colorful cel-shading.2. Cool and composed undercover agent in the sleek SPYxFAMILY style. Outfit: tailored suit, long coat, gloves, polished shoes. Background: japan cityscape with tall buildings. Clean cel-shading and dramatic lighting.3. Fierce ninja warrior in the action-packed shonen japan anime style. Outfit: headband with emblem, high-collared vest, armored gloves, shinobi sandals. Mid-motion pose forming ninja seals, surrounded by glowing chakra and shattered battlefield ruins. Exaggerated speed lines, dramatic shading.4. (Optional) Courageous magical protagonist with glowing eyes, and an intricate battle-ready outfit inspired by Pretty Cure. Background: cosmic battlefield with swirling magical energy and glowing runes. Vibrant and dynamic cel-shading. The resulting image's face must be identical to the original photo's face.",
        ],
    ];
    
    private const FEMALE_DESCRIPTIONS = [
        1 => [ // キレイ系 (Elegant)
             "This portrait of a woman in her 20s is shot under soft lighting in CityEdgeMix's elegant and clean style. She has a soft, calm expression and clear, delicate skin. Her hair is neat and natural, with sleek, tidy locks framing her face. She wears sophisticated and refined clothing. The background, bathed in natural light, creates a calm and elegant atmosphere. The overall visual tone is clean, airy, and sophisticated, evoking a sense of serene elegance, sincerity, and urban innocence. Generate a realistic portrait that accurately displays the actual face, facial features of the person in the uploaded image. The result image's face should closely resemble the original photo's face."
        ],
        2 => [ // 近未来系 (Futuristic)
             "This futuristic portrait of the subject in the photo is set in a cutting-edge science fiction world. Her outfit features a streamlined, form-fitting robot suit, complete with a fully equipped robot hat and equipment, creating a captivating visual experience. The background is a high-tech cityscape highlighted by futuristic panels. The resulting image must be in 3D and high-quality and android style, with a Japanese landscape as the background. The overall visual style is surreal, emphasizing a calm and intellectual expression, utilizing cinematic lighting, soft reflections, and a clean, minimalist sci-fi aesthetic. Create a portrait that accurately captures the actual face and features of the subject in the uploaded image. The resulting image's face must be identical to the original photo's face."
        ],
        3 => [ // アニメ系 (japan anime)
           "Please transform the female character in the uploaded image into an animated female character. You can use various forms, such as a witch's symbol, a gentle and serene 15-year-old woman, or a natural expression or social activity. The resulting image must be in 3D and high-quality, with a natural Japanese landscape as the background. Please create a realistic portrait that accurately depicts the actual face and features of the person in the uploaded image. The final face must be an exact match to the original photo."
        ],
    ];

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = Config::get('app.gemini_api_key');
        $this->modelId = 'gemini-2.5-flash-image';
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
                "https://generativelanguage.googleapis.com/v1/models/{$this->modelId}:generateContent?key={$this->apiKey}",
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