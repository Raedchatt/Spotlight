<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GenerateImagesController extends Controller
{
    public function generate(Request $request)
    {
        \Log::info('GENERATE START', [
            'titre' => $request->input('titre'),
            'description' => $request->input('description')
        ]);

        $titre = $request->input('titre', '');
        $description = $request->input('description', '');
        $categorie = $request->input('categorie', '');
        $creativeSeed = rand(1, 1000000);

        $prompt = <<<PROMPT

You are an expert AI image generation prompt engineer for a premium event platform called Spotlight. 
Your task is to create 4 distinct, highly detailed, and visually stunning prompts suitable for professional event covers/posters.

Guidelines for each prompt:
- Reflect the event title, description, and category accurately.
- Specify composition, perspective, lighting, and artistic style (e.g., cinematic, digital painting, isometric, minimalist, futuristic).
- Include color palettes or moods if relevant (e.g., vibrant, warm, neon, soft pastels, dramatic contrast).
- Avoid including any text, logos, or watermarks in the image.
- Ensure the image would look appealing as a banner or social media post.
- Creative Variation Seed: {$creativeSeed} (Use this to brainstorm a unique set of artistic interpretations).
Event Details:
Title: {$titre}
Description: {$description}
Category: {$categorie}

Output Requirement:
Reply ONLY with a valid JSON array of 4 strings (prompts). Each string should be a complete, professional AI prompt ready for image generation. Do NOT include markdown, commentary, or extra text.
Example format: ["prompt 1...", "prompt 2...", "prompt 3...", "prompt 4..."]
PROMPT;

        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            return response()->json(['error' => 'API Configuration Error'], 500);
        }

        // Using model from config
        $model = config('services.gemini.model', 'gemini-2.0-flash');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout(30)->post($url, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature' => 1.0,
                    'maxOutputTokens' => 1024,
                ],
            ]);

            if ($response->failed()) {
                \Log::error('Gemini API Error: ' . $response->body());

                // If the primary prompt generation fails, we provide 2 generic prompts including category
                $prompts = [
                    "Professional event cover for '{$titre}' ({$categorie}), cinematic lighting, digital art",
                    "Artistic abstract background for '{$titre}' {$categorie} event, vibrant colors"
                ];
            }
            else {
                $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');

                // Clean JSON response from AI
                $text = preg_replace('/```(?:json)?\s*/i', '', $text);
                $text = preg_replace('/```/', '', $text);
                $text = trim($text);

                $prompts = json_decode($text, true);

                if (!is_array($prompts)) {
                    \Log::error('Gemini JSON Parse Error. Raw: ' . $text);
                    // Fallback prompts if JSON parsing fails
                    $prompts = [
                        "Event cover for '{$titre}' in category {$categorie}, high resolution",
                        "Promotional graphic for '{$titre}' {$categorie} event",
                        "Artistic '{$titre}' {$categorie} poster",
                        "Modern '{$titre}' {$categorie} event background"
                    ];
                }
                
                // SHUFFLE and slice to ensure variety on every click
                shuffle($prompts);
                $prompts = array_slice($prompts, 0, 2);
            }

            // Generate images using Gemini Imagen 3 via Google AI SDK
            $imagenModel = config('services.gemini.imagen_model', 'imagen-3.0-generate-001');
            $imagenUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$imagenModel}:predict?key={$apiKey}";

            $suggestions = [];
            try {
                // Use Http::pool to call the Imagen API for each prompt in parallel
                $responses = Http::pool(fn(\Illuminate\Http\Client\Pool $pool) =>
                collect($prompts)->map(function ($p) use ($pool, $imagenUrl) {
                    return $pool->timeout(15)->post($imagenUrl, [
                    'instances' => [['prompt' => $p]]
                    ]);
                }
                )
                );

                foreach ($prompts as $index => $p) {
                    $res = $responses[$index];

                    if ($res->successful()) {
                        $prediction = data_get($res->json(), 'predictions.0');
                        if ($prediction) {
                            $base64Data = $prediction['bytesBase64Encoded'] ?? null;
                            $mimeType = $prediction['mimeType'] ?? 'image/png';

                            // Validate Base64: non-empty and minimum logical size for an image (~1KB)
                            if ($base64Data && strlen($base64Data) > 1000) {
                                $suggestions[] = [
                                    'prompt' => $p,
                                    'url' => "data:{$mimeType};base64,{$base64Data}"
                                ];
                            }
                            else {
                                if ($base64Data) {
                                    \Log::warning("Gemini Imagen returned too small image (" . strlen($base64Data) . " chars) for prompt: {$p}");
                                }
                                else {
                                    \Log::warning("Gemini Imagen returned null data for prompt: {$p}");
                                }
                            }
                        }
                    }
                    else {
                        \Log::warning("Gemini Imagen Failed for prompt: {$p}. Error: " . $res->body());
                    }
                }
            }
            catch (\Exception $e) {
                \Log::error('Gemini Imagen API Pool Exception: ' . $e->getMessage());
            }

            // Fallback to Pollinations.ai if Gemini Imagen failed or returned nothing
            if (empty($suggestions)) {
                $pollinationResponses = Http::pool(fn(\Illuminate\Http\Client\Pool $pool) =>
                collect($prompts)->map(function ($p) use ($pool) {
                    $encodedPrompt = urlencode($p);
                    $seed = rand(1, 9999999);
                    $url = "https://pollinations.ai/p/{$encodedPrompt}?width=1024&height=768&nologo=true&seed={$seed}&t=" . microtime(true);
                    \Log::debug("AI Generation: Calling Pollinations.ai: {$url}");
                    return $pool->timeout(20)->get($url);
                }
                )
                );

                foreach ($prompts as $index => $p) {
                    $res = $pollinationResponses[$index];
                    if ($res->successful()) {
                        $contentType = $res->header('Content-Type');
                        $body = $res->body();

                        if (str_starts_with($contentType, 'image/') && strlen($body) > 1000) {
                            $base64Data = base64_encode($body);
                            $suggestions[] = [
                                'prompt' => $p,
                                'url' => "data:{$contentType};base64,{$base64Data}"
                            ];
                        }
                        else {
                            \Log::warning("Pollinations returned invalid content-type ({$contentType}) or small body for prompt: {$p}");
                        }
                    }
                }
            }

            // LAST RESORT: Fallback to Unsplash images (fetched and converted to Base64)
            if (empty($suggestions)) {
                \Log::warning('AI Generation: Proceeding to LAST RESORT (Unsplash Base64 fallback)');

                // Categorized high-quality event related photo IDs
                $categorizedPools = [
                    'musicaux' => [
                        '1514525253344-a90236374a1b', '1459749411177-042180ec75ff', 
                        '1470225620780-dba8ba36b745', '1511671782779-c97d3d27a1d4',
                        '1501612722283-a15d73e920d0', '1493225255756-d737996a438b', '1520110120385-c28663212d17'
                    ],
                    'sportifs' => [
                        '1461896602554-ce2a1aa1d5b1', '1541250848-40b9ae3c448d',
                        '1471922638140-3c7e0828d973', '1504450758481-70e673206687',
                        '1517649763962-0ca566ee4a46'
                    ],
                    'culturels' => [
                        '1452274466944-59de5e13d536', '1492684223066-81342ee5ff30',
                        '1533174072545-7a4b6ad7a6c3', '1506157786151-b8491531f063'
                    ],
                    'commerciaux' => [
                        '1527529482837-4698179dc6ce', '1441984232244-3d627b7ad352',
                        '1516280440614-37939bbacd81'
                    ],
                    'scientifiques' => [
                        '1507413245164-cbfd8a2d3293', '1532094349884-55ffda219711',
                        '1518152002722-c1f1ec01c70f'
                    ],
                    'default' => [
                        '1540575467063-178a50c2df87', '1492684223066-81342ee5ff30',
                        '1501281668695-03a69940fd16', '1533174072545-7a4b6ad7a6c3'
                    ]
                ];

                $allPhotoIds = $categorizedPools[$categorie] ?? $categorizedPools['default'];

                // Pick 2 random unique IDs from the pool
                $photoIds = collect($allPhotoIds)->random(min(count($allPhotoIds), 2))->toArray();

                $unsplashResponses = Http::pool(fn(\Illuminate\Http\Client\Pool $pool) =>
                collect($photoIds)->map(function ($id) use ($pool) {
                    $ts = microtime(true);
                    return $pool->timeout(10)->get("https://images.unsplash.com/photo-{$id}?q=50&w=800&auto=format&fit=crop&t={$ts}");
                }
                )
                );

                foreach ($photoIds as $index => $id) {
                    $res = $unsplashResponses[$index];
                    if ($res->successful()) {
                        $body = $res->body();
                        $base64Data = base64_encode($body);
                        $contentType = $res->header('Content-Type') ?? 'image/jpeg';
                        $suggestions[] = [
                            'prompt' => "Diversified Event Cover " . ($index + 1),
                            'url' => "data:{$contentType};base64,{$base64Data}"
                        ];
                    }
                }
            }

            if (empty($suggestions)) {
                \Log::error('AI Generation: ALL attempts failed.');
            }
            else {
                \Log::info('AI Generation: Successfully generated ' . count($suggestions) . ' suggestions.');
            }

            return response()->json(['suggestions' => $suggestions]);

        }
        catch (\Exception $e) {
            \Log::error('AI Generation Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload a Base64 image to Cloudinary and return the secure URL.
     */
    public function uploadSelectedImage(Request $request)
    {
        $request->validate([
            'image_base64' => 'required|string',
            'prompt' => 'nullable|string'
        ]);

        try {
            $base64 = $request->input('image_base64');

            // Upload to Cloudinary
            $uploadResult = cloudinary()->uploadApi()->upload($base64, [
                'folder' => 'events/ai_covers',
                'resource_type' => 'image'
            ]);

            return response()->json([
                'success' => true,
                'secure_url' => $uploadResult['secure_url'],
                'prompt' => $request->input('prompt')
            ]);

        }
        catch (\Exception $e) {
            \Log::error('Cloudinary AI Upload Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to upload selection to Cloudinary'], 500);
        }
    }
}
