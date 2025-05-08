<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class DeepImageController extends Controller
{
    private $apiUrl = 'https://deep-image.ai/rest_api/process_result';
    private $statusUrl = 'https://deep-image.ai/rest_api/result/';
    private $apiKey;

    // 配列として定数の説明を保存 (1 => "キレイ系", 2 => "近未来系", 3 => "アニメ系")
    private const MALE_DESCRIPTIONS = [
        1 => [ // キレイ系 (Elegant)
            "A beautifully composed portrait of a refined gentleman, perfectly centered in the frame from the face to the chest. His posture is poised yet relaxed, with a subtle head tilt and a confident gaze. His facial features are well-defined yet balanced, exuding sophistication without appearing overly rugged. He wears a tailored suit with intricate textures and bold, complementary colors, draping elegantly without an overly structured or rigid silhouette. The warm, soft lighting highlights his smooth complexion, while the softly blurred background enhances his commanding presence, creating a polished and distinguished look.",
            "A man with a glowing complexion and wavy, chestnut-brown hair. His eyes are sharp and expressive, enhanced with natural lighting that accentuates their depth. He wears a dark gray turtleneck sweater layered under a black wool overcoat, exuding effortless winter elegance. The setting is wintery, with a softly blurred snowy background and gentle, diffused lighting that enhances the warmth of his expression, focusing on his face and upper chest.",
            // "A close-up portrait of a man from the face to the chest. He has long, tousled auburn hair, striking eyes, and light freckles (0.2), with a naturally rugged yet polished appearance. He wears a fitted black button-up shirt under an elegant, long coat. Seated at the edge of his bed, he radiates warmth with a confident yet inviting smile. Sunlight streams through sheer curtains, softly diffusing into the room. A large depth of field keeps the focus sharp, creating an intimate and engaging atmosphere, focusing on his face and upper chest.",
            // "A portrait of a flight attendant confidently posing for a photo, with his hands hidden from view. The image is cropped from the face to the chest, highlighting his impeccably styled uniform in bold shades of navy blue and deep crimson. The details—its fit, texture, and color scheme—are captured with precision, emphasizing his poised and professional presence. The softly blurred airplane interior in the background enhances the sense of professionalism and hospitality, ensuring that the focus remains solely on the flight attendant while maintaining an elegant yet subtle atmosphere, focusing on his face and upper chest."
        ],
        2 => [ // 近未来系 (Futuristic)
            "A cel-shaded anime-style illustration of a retro-futuristic male android with a sleek, silvery-white metallic faceplate. His face is smooth and synthetic, with visible seams along the jawline and subtle glowing circuit patterns embedded beneath the surface. His body is constructed with classic brushed aluminum and steel plating, featuring exposed rivets, mechanical joints, and articulated hydraulic tubing, giving him a vintage sci-fi robot aesthetic. His hair is a blend of synthetic fiber and anime-style vibrancy, styled in a sleek yet slightly tousled manner with a soft metallic sheen. He wears a stylish, fantasy-inspired robotic ensemble, integrating small gears, glowing circuits, and intricate mechanical details into a structured yet imposing design. The background is a futuristic, neon-lit cityscape with flickering holograms and faintly glowing steam rising from vents, adding a dynamic, cyberpunk-inspired atmosphere. The cel-shading style is crisp and vibrant, with high-contrast lighting that enhances the reflections on his metallic face and the intensity of his laser vision."
        ],
        3 => [ // アニメ系 (Anime)
            "A vibrant cel-shaded anime-style illustration of an energetic and charismatic male protagonist, inspired by the main character from Magical Halloween. He has striking orange hair styled in a playful yet slightly tousled way. His eyes are large and intricate, featuring well-defined irises with multiple reflections and a glossy, almost glass-like shine. His eyelashes are bold and slightly curved, giving him an expressive and mysterious gaze. He wears a stylish fantasy-inspired magical outfit with charming details, including a high-collared cape, an enchanted emblem, and elegant gloves. His pose is dynamic and confident, exuding charm and energy. The background features a whimsical, Halloween-themed world with glowing pumpkins, floating magical symbols, and a mysterious yet fun atmosphere. The art style is bright, colorful, and crisp, with smooth cel-shading reminiscent of classic Japanese anime, focused on the protagonist's face and upper chest.",
            "A stylish cel-shaded anime-style illustration of a cool and composed male protagonist, drawn in the sleek and dynamic style of SPYxFAMILY. He has sharp, well-styled orange hair, slightly tousled yet refined, adding to his composed and intelligent appearance. His eyes have a sharp, focused shape, with subtle gradient shading and crisp reflections that add depth and mystery. His eyelashes are bold but natural, giving him a mature and confident expression. He wears a sophisticated yet practical outfit, featuring a tailored suit, a long coat, gloves, and polished shoes, embodying the look of a secret agent or undercover operative. His pose is sleek and action-ready, exuding both elegance and mystery. The background features a European-inspired cityscape with tall buildings, a passing train, and a subtle yet suspenseful atmosphere. The art style is polished, clean, and highly expressive, with well-balanced shading, dramatic lighting, and smooth linework that enhances the spy-action theme, focused on the protagonist's face and upper chest.",
            "A powerful cel-shaded anime-style illustration of a courageous and determined male protagonist, drawn in the energetic and dynamic style of Pretty Cure (Precure). He has bold, flowing orange hair that radiates energy. His eyes are large, vibrant, and filled with powerful, radiant highlights, reflecting his heroic spirit. His eyelashes are bold and dramatic, slightly extended outward, creating a dynamic and intense look that matches his action-packed energy. He wears an intricately designed battle-ready magical outfit, featuring elegant frills, a signature emblem, and armor-like accessories such as gloves and boots. His action-packed pose is full of energy, mid-motion as he unleashes a powerful attack or prepares for battle. The background features a dazzling cosmic battlefield filled with swirling magical energy, glowing symbols, and an aura of strength. The art style is vibrant, fluid, and action-oriented, with exaggerated movements, dramatic effects, and intense shading that emphasize his heroic transformation, ensuring striking and expressive eyes, focused on the protagonist's face and upper chest.",
            "A powerful cel-shaded anime-style illustration of a fierce and determined ninja warrior, drawn in the energetic and action-packed style of shonen anime. He has wild, flowing orange hair that moves dynamically with his swift motion. His sharp, intense eyes glow with determination, featuring bold, dramatic eyelashes that enhance his focused and battle-ready expression. He wears an intricately designed ninja outfit, complete with a headband featuring a unique emblem, a high-collared vest, armored gloves, and reinforced shinobi sandals. His stance is action-packed, mid-motion as he unleashes a high-speed jutsu or prepares for a decisive strike. His hands form intricate ninja seals or wield a legendary kunai, radiating an aura of power. The background is an intense battlefield, with swirling dust, shattered rocks, and glowing chakra energy illuminating the scene. Mystical symbols and seals float around him as he channels his inner strength. The art style is vibrant, fluid, and dynamic, with exaggerated speed lines, dramatic lighting, and cel-shaded intensity that highlight his raw energy, ensuring a striking and powerful visual impact, focused on the protagonist's face and upper chest."
        ],
    ];
    
    private const FEMALE_DESCRIPTIONS = [
        1 => [ // キレイ系 (Elegant)
            "A beautifully composed portrait of an elegant woman, perfectly centered in the frame from the face to the chest. Her posture is poised yet effortless, with a slight head tilt and a confident yet graceful gaze. Her facial features are well-balanced and refined, exuding an aura of sophistication. She wears a luxurious outfit featuring intricate textures and bold colors, draping smoothly while maintaining a soft, flowing silhouette. The warm, soft lighting enhances her radiant complexion, while the softly blurred background adds depth to her captivating presence, creating a refined and timeless look.",
            "A woman with a glowing complexion and wavy, honey-blonde hair. Her eyes are vibrant, enhanced with natural makeup and a hint of mascara. She wears a light gray off-the-shoulder cable knit sweater over a black sequined V-neck top, striking the perfect balance between elegance and comfort. The setting is wintery, with a softly blurred snowy background and gentle, diffused lighting that enhances the warmth of her expression, keeping the focus on her face and upper chest.",
            // "A close-up portrait of a woman from the face to the chest. She has long, curly ginger hair, striking eyes, and light freckles (0.2), with minimal yet natural-looking makeup that enhances her soft features. She wears a stylish black blouse paired with an elegant, long dress. Seated at the edge of her bed, she radiates warmth with a gorgeous smile. Sunlight streams through sheer curtains, softly diffusing into the room. A large depth of field keeps the focus sharp, creating an intimate and inviting atmosphere, focusing on her face and upper chest.",
            // "A portrait of a flight attendant confidently posing for a photo, with her hands hidden from view. The image is cropped from the face to the chest, highlighting her impeccably tailored uniform in striking shades of blue and gold. The details—its fit, texture, and color scheme—are captured with precision, emphasizing her poised and professional presence. The softly blurred airplane interior in the background enhances the sense of professionalism and hospitality, ensuring that the focus remains solely on the flight attendant while maintaining an elegant yet subtle atmosphere, focusing on her face and upper chest."
        ],
        2 => [ // 近未来系 (Futuristic)
            "A cel-shaded anime-style illustration of a retro-futuristic female android with a sleek, silvery-white metallic faceplate. Her face is smooth and synthetic, with visible seams along the jawline and subtle glowing circuit patterns embedded beneath the surface. Her body is built with classic brushed aluminum and steel plating, featuring exposed rivets, mechanical joints, and articulated hydraulic tubing, giving her a vintage sci-fi robot aesthetic. Her hair is a blend of synthetic fiber and anime-style vibrancy, flowing with a soft metallic sheen. She wears a stylish, fantasy-inspired robotic ensemble, incorporating small gears, glowing circuits, and intricate mechanical details into a structured yet elegant design. The background is a futuristic, neon-lit cityscape with flickering holograms and faintly glowing steam rising from vents, adding a dynamic, cyberpunk-inspired atmosphere. The cel-shading style is crisp and vibrant, with high-contrast lighting that enhances the reflections on her metallic face and the intensity of her laser vision."
        ],
        3 => [ // アニメ系 (Anime)
            "A vibrant cel-shaded anime-style illustration of an energetic and charismatic protagonist, inspired by the main character from Magical Halloween. They have striking orange hair styled in a playful way, with large. The protagonist's eyes are intricately designed with large, well-defined irises, multiple reflections, and a glossy, almost glass-like shine. Their eyelashes are long, sharp, and slightly curved, giving them an expressive and magical gaze. They wear a stylish and fantasy-inspired magical outfit with charming details, including a pointed hat, a frilly ensemble, and enchanting accessories like ribbons and gloves. Their pose is dynamic and confident, exuding charm and energy. The background features a whimsical, Halloween-themed world with glowing pumpkins, floating magical symbols, and a mysterious yet fun atmosphere. The art style is bright, colorful, and crisp, with smooth cel-shading reminiscent of classic Japanese anime, focused on the protagonist's face and upper chest.",
            "A stylish cel-shaded anime-style illustration of a cool and composed protagonist, drawn in the sleek and dynamic style of SPYxFAMILY. They have striking orange hair styled in a slightly tousled yet refined way, with sharp.The character's eyes have a sharp, focused shape, with subtle gradient shading and crisp reflections that add a sense of depth and intelligence. Their eyelashes are well-defined, with bold upper lashes and a slightly more natural lower lash line, giving them a mature and confident expression.They wear a sophisticated yet practical outfit, featuring a long coat, gloves, and tailored attire suited for a secret agent or undercover operative. Their pose is sleek and action-ready, exuding both elegance and mystery. The background features a European-inspired cityscape with tall buildings, a passing train, and a subtle yet suspenseful atmosphere. The art style is polished, clean, and highly expressive, with well-balanced shading, dramatic lighting, and smooth linework that enhances the spy-action theme, focused on the protagonist's face and upper chest.",
            "A powerful cel-shaded anime-style illustration of a courageous and determined protagonist, drawn in the energetic and dynamic style of Pretty Cure (Precure). They have bold, flowing orange hair. Their eyes are large, vibrant, and filled with powerful, radiant highlights, reflecting their heroic spirit. The eyelashes are bold, dramatic, and slightly extended outward, creating a dynamic and intense look that matches their action-packed energy. They wear an intricately designed battle-ready magical outfit, featuring elegant frills, a signature emblem, and armor-like accessories such as gloves and boots. Their action-packed pose is full of energy, mid-motion as they unleash a powerful attack or prepare for battle. The background features a dazzling cosmic battlefield filled with swirling magical energy, glowing symbols, and an aura of strength. The art style is vibrant, fluid, and action-oriented, with exaggerated movements, dramatic effects, and intense shading that emphasize their heroic transformation, ensuring striking and expressive eyes with well-defined eyelashes, focused on the protagonist's face and upper chest.",
            "A powerful cel-shaded anime-style illustration of a fierce and determined ninja warrior, drawn in the energetic and action-packed style of shonen anime. They have wild, flowing orange hair that moves dynamically with their swift motion. Their sharp, intense eyes glow with determination, featuring bold, dramatic eyelashes that enhance their focused and battle-ready expression.They wear an intricately designed ninja outfit, complete with a headband featuring a unique emblem, a high-collared vest, armored gloves, and reinforced shinobi sandals. Their stance is action-packed, mid-motion as they unleash a high-speed jutsu or prepare for a decisive strike. Their hands form intricate ninja seals or wield a legendary kunai, radiating an aura of power.The background is an intense battlefield, with swirling dust, shattered rocks, and glowing chakra energy illuminating the scene. Mystical symbols and seals float around them as they channel their inner strength. The art style is vibrant, fluid, and dynamic, with exaggerated speed lines, dramatic lighting, and cel-shaded intensity that highlight their raw energy. Their heroic transformation is emphasized with expressive eyes, strong contrast, and motion effects, ensuring a striking and powerful visual impact, focused on the protagonist's face and upper chest."
        ],
    ];

    public function __construct()
    {
        $this->apiKey = Config::get('app.deep_image_api_key');
    }

    public function processImage(Request $request)
    {
        return response()->json([
            'image_url' => "test.jpg",
        ]);
        // $photoPath = $request->input('photoPath');
        // $avatarType = $request->input('avatar_type');
        // $avatarGenderType = $request->input('avatarGenderType');
        // $fullPhotoPath = storage_path("app/public/face_id_photos/{$photoPath}");

        // $descriptions = ($avatarGenderType === 1) 
        //     ? self::FEMALE_DESCRIPTIONS[$avatarType] ?? self::FEMALE_DESCRIPTIONS[3]
        //     : self::MALE_DESCRIPTIONS[$avatarType] ?? self::MALE_DESCRIPTIONS[3];
        // $randomDescription = $descriptions[array_rand($descriptions)];
    
        // $data = [
        //     "width" => 850,
        //     "height" => 1400,
        //     "background" => [
        //         "generate" => [
        //             "description" => $randomDescription,
        //             "adapter_type" => "face",
        //             "face_id" => True
        //         ]
        //     ]
        // ];
    
        // $headers = [
        //     'x-api-key' => $this->apiKey,
        // ];
    
        // $response = Http::withHeaders($headers)
        //     ->attach('image', fopen($fullPhotoPath, 'r'), basename($fullPhotoPath))
        //     ->post($this->apiUrl, ['parameters' => json_encode($data)]);
    
        // if ($response->successful()) {
        //     $responseData = $response->json();
    
        //     if ($responseData['status'] == 'complete') {
        //         $processedImageUrl = $this->downloadAndSaveImage($responseData['result_url']);

        //         return response()->json([
        //             'image_url' => $processedImageUrl,
        //         ]);
        //     }
    
        //     while ($responseData['status'] == 'in_progress') {
        //         sleep(1);
        //         $statusResponse = Http::withHeaders($headers)
        //             ->get($this->statusUrl . $responseData['job']);
    
        //         if ($statusResponse->successful()) {
        //             $responseData = $statusResponse->json();
        //             if ($responseData['status'] == 'complete') {
        //                 $processedImageUrl = $this->downloadAndSaveImage($responseData['result_url']);

        //                 return response()->json([
        //                     'image_url' => $processedImageUrl,
        //                 ]);
        //             }
        //         }
        //     }

        //     return response()->json(['error' => '画像処理ジョブが正常に完了しませんでした。'], 500);
        // }

        // return response()->json(['error' => '画像処理ジョブの開始に失敗しました。'], 500);
    }
    
    private function downloadAndSaveImage($url)
    {
        // URL からファイル名を取得
        $filename = basename($url);
        // 公開ストレージに画像を保存するパスを定義
        $filePath = storage_path('app/public/processed_images/' . $filename);
    
        // 画像をダウンロードして保存
        $imageContent = file_get_contents($url);
        file_put_contents($filePath, $imageContent);
    
        // 保存された画像のファイル名を返す
        return $filename;
    }    
}