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
    private const DESCRIPTIONS = [
        1 => [ // キレイ系 (Elegant)
            "A beautifully composed portrait of an elegant individual, perfectly centered in the frame from the face to the chest. Their posture is poised yet natural, with a slight head tilt and a confident gaze. Their facial features are well-balanced, refined, and androgynous, avoiding strong masculine or feminine extremes. Their luxurious outfit, featuring intricate textures and bold colors, drapes elegantly without emphasizing traditionally gendered silhouettes. The warm, soft lighting highlights their smooth skin, while the softly blurred background enhances their captivating presence, creating a refined and sophisticated look.",
            "An individual with a glowing complexion and wavy, honey-blonde hair. Their eyes are vibrant, enhanced with natural makeup and subtle mascara. They wear a light gray off-the-shoulder cable knit sweater over a black sequined V-neck top. The setting is wintery, with a softly blurred snowy background and gentle, diffused lighting that enhances the warmth of their expression, focused on the protagonist's face and upper chest.",
            "A close-up portrait of an individual from the face to the chest. They have long, curly ginger hair, striking eyes, and light freckles (0.2), with minimal, natural makeup. They wear a black blouse paired with elegant, long dress-style clothing. Seated at the edge of their bed, they radiate warmth with a gorgeous smile. Sunlight streams through sheer curtains, softly diffusing into the room. A large depth of field keeps the focus sharp, creating an intimate and inviting atmosphere, focused on the protagonist's face and upper chest.",
            "A journalistic portrait of a TV reporter engaged in a live broadcast, speaking passionately into a microphone while delivering the news. The portrait is framed from the face to the chest, capturing their professional yet expressive demeanor. The softly blurred cityscape in the background conveys the fast-paced nature of live reporting. Thoughtful lighting and framing highlight the journalis's energy and professionalism while keeping the urban environment subtly present, focused on the protagonist's face and upper chest.",
            "A portrait of a flight attendant confidently posing for a photo, with their hands hidden from view. The image is cropped from the face to the chest, highlighting their impeccably styled uniform in bold shades of blue and orange. The details—its fit, texture, and color scheme—are captured with precision, emphasizing their poised and professional presence. The softly blurred airplane interior in the background enhances the sense of professionalism and hospitality, ensuring that the focus remains solely on the flight attendant while maintaining an elegant yet subtle atmosphere, focused on the protagonist's face and upper chest."
        ],
        2 => [ // 近未来系 (Futuristic)
            "portrait of a beautiful {sex} elf striking a pose, surrounded by a bright aura emanating from their head. The elf is set against a backdrop of a magical golden forest, enhancing the ethereal atmosphere. Craft the glowing aura with a captivating luminosity, and ensure the golden forest background complements the mystical ambiance. Elf with elongated ears and pale skin, focused on the protagonist's face and upper chest.",
            "a celestial depiction of an {sex} angel with ethereal wings and flowing blond hair. Envision the heavenly presence against a backdrop adorned with divine elements, conveying a sense of celestial beauty and grace. Craft the portrait to capture the angelic essence, with an emphasis on the celestial background that radiates the essence of heavenly realms, focused on the protagonist's face and upper chest.",
            "a {sex} model with pink hair, infused with a vaporwave style and retro aesthetic. Embrace the cyberpunk vibe, incorporating vibrant neon colors that evoke the essence of vintage 80s and 90s fashion. The intricate elements of the cyberpunk and vaporwave aesthetics. Mesmerizing fusion of vibrant hues and nostalgic retro styles, capturing the spirit of the bygone eras with a contemporary twist, focused on the protagonist's face and upper chest.",
            "stylish {sex} model adorned in avant-garde Balenciaga fashion. The outfit showcase a sculptural silhouette with oversized and deconstructed elements, creating a modern and bold look. Emphasize a monochromatic palette, possibly incorporating black, white, or gray, to highlight the structural design. The model should be wearing Balenciaga's iconic chunky sneakers, focused on the protagonist's face and upper chest.",
            "{sex} kawaii style {style Detailed body painting beautiful neon operator tanned woman, cyberpunk futuristic neon, reflective puffy coat, decorated with traditional japanese ornaments by ismail inceoglu dragan bibin hans thoma greg rutkowski alexandros pyromallis nekro rene maritte illustrated, perfect face, fine details,} . cute, adorable, brightly colored, cheerful, anime influence, highly detailed, focused on the protagonist's face and upper chest."
        ],
        3 => [ // アニメ系 (Anime)
            "A vibrant cel-shaded anime-style illustration of an energetic and charismatic protagonist, inspired by the main character from Magical Halloween. They have striking orange hair styled in a playful way, with large. The protagonist's eyes are intricately designed with large, well-defined irises, multiple reflections, and a glossy, almost glass-like shine. Their eyelashes are long, sharp, and slightly curved, giving them an expressive and magical gaze. They wear a stylish and fantasy-inspired magical outfit with charming details, including a pointed hat, a frilly ensemble, and enchanting accessories like ribbons and gloves. Their pose is dynamic and confident, exuding charm and energy. The background features a whimsical, Halloween-themed world with glowing pumpkins, floating magical symbols, and a mysterious yet fun atmosphere. The art style is bright, colorful, and crisp, with smooth cel-shading reminiscent of classic Japanese anime, focused on the protagonist's face and upper chest.",
            "A charming cel-shaded anime-style illustration of a cheerful and expressive protagonist, drawn in the adorable, playful style of Ojamajo Doremi. They have short, bouncy orange hair and big.Their eyes are big, round, and adorable, filled with oversized sparkles and layered highlights that create an innocent, lively effect. The eyelashes are short, curved, and slightly exaggerated, enhancing their youthful and expressive appearance.They wear a colorful and stylish magical apprentice outfit, featuring puffed sleeves, a cute hat, and charming accessories like a wand and a beaded bracelet. Their lively and animated pose captures their boundless energy and excitement. The background features a warm and inviting town filled with pastel-colored houses, floating sparkles, and a sense of everyday magic. The art style is soft, colorful, and lighthearted, with gentle linework and expressive character movements that enhance the whimsical atmosphere, focused on the protagonist's face and upper chest.",
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
        // $fullPhotoPath = storage_path("app/public/face_id_photos/{$photoPath}");

        // $descriptions = self::DESCRIPTIONS[$avatarType] ?? self::DESCRIPTIONS[3];
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