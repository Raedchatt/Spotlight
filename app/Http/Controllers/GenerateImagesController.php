<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GenerateImagesController extends Controller
{
    public function generate(Request $request)
    {
        \Log::info('GENERATE START', [
            'titre'       => $request->input('titre'),
            'description' => $request->input('description'),
            'categorie'   => $request->input('categorie'),
        ]);

        $titre        = $request->input('titre', '');
        $description  = $request->input('description', '');
        $categorie    = $request->input('categorie', '');
        $creativeSeed = rand(1, 1000000);

        // ─────────────────────────────────────────────────────────────────
        // STEP 1 — Ask Gemini to generate 4 creative image prompts
        // ─────────────────────────────────────────────────────────────────
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
            \Log::error('AI Generation: Gemini API key is missing.');
            return response()->json(['error' => 'API Configuration Error'], 500);
        }

        $model = config('services.gemini.model', 'gemini-2.0-flash');
        $url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        // Default fallback prompts in case Gemini prompt generation also fails
        $prompts = [
            "Professional cinematic event cover for '{$titre}', category: {$categorie}, dramatic lighting, ultra HD",
            "Vibrant artistic poster for '{$titre}' {$categorie} event, bold colors, modern design",
            "Minimalist elegant banner for '{$titre}', {$categorie} theme, soft gradients, premium feel",
            "Dynamic futuristic background for '{$titre}' {$categorie}, neon accents, high contrast",
        ];

        try {
            $geminiResponse = Http::timeout(30)->post($url, [
                'contents'         => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature'     => 1.0,
                    'maxOutputTokens' => 1024,
                ],
            ]);

            if ($geminiResponse->failed()) {
                \Log::error('Gemini Prompt Generation Failed: ' . $geminiResponse->body());
                // $prompts already holds fallback values, continue
            } else {
                $text = data_get($geminiResponse->json(), 'candidates.0.content.parts.0.text', '');

                // Strip possible markdown code fences
                $text = preg_replace('/```(?:json)?\s*/i', '', $text);
                $text = preg_replace('/```/', '', $text);
                $text = trim($text);

                $parsed = json_decode($text, true);

                if (is_array($parsed) && count($parsed) >= 2) {
                    // FIX 1 — Keep ALL 4 prompts instead of cutting to 2
                    shuffle($parsed);
                    $prompts = array_slice($parsed, 0, 4);
                    \Log::info('AI Generation: Gemini produced ' . count($prompts) . ' prompts.');
                } else {
                    \Log::error('Gemini JSON Parse Error. Raw text: ' . $text);
                    // $prompts already holds the title-aware fallback values above
                }
            }
        } catch (\Exception $e) {
            \Log::error('Gemini Prompt Generation Exception: ' . $e->getMessage());
            // $prompts already holds fallback values, continue
        }

        $suggestions = [];

        // ─────────────────────────────────────────────────────────────────
        // STEP 2 — Try Gemini Imagen 3 (requires allowlist access)
        // ─────────────────────────────────────────────────────────────────
        $imagenModel = config('services.gemini.imagen_model', 'imagen-3.0-generate-001');
        $imagenUrl   = "https://generativelanguage.googleapis.com/v1beta/models/{$imagenModel}:predict?key={$apiKey}";

        try {
            $imagenResponses = Http::pool(fn(\Illuminate\Http\Client\Pool $pool) =>
                collect($prompts)->map(fn($p) =>
                    $pool->timeout(20)->post($imagenUrl, [
                        'instances' => [['prompt' => $p]],
                    ])
                )
            );

            foreach ($prompts as $index => $p) {
                $res = $imagenResponses[$index];

                if ($res->successful()) {
                    $prediction = data_get($res->json(), 'predictions.0');
                    $base64Data = $prediction['bytesBase64Encoded'] ?? null;
                    $mimeType   = $prediction['mimeType'] ?? 'image/png';

                    if ($base64Data && strlen($base64Data) > 1000) {
                        $suggestions[] = [
                            'prompt' => $p,
                            'url'    => "data:{$mimeType};base64,{$base64Data}",
                        ];
                        \Log::info("Gemini Imagen: success for prompt index {$index}");
                    } else {
                        \Log::warning("Gemini Imagen: empty/small image for prompt index {$index}");
                    }
                } else {
                    \Log::warning("Gemini Imagen: request failed for prompt index {$index}. Body: " . $res->body());
                }
            }
        } catch (\Exception $e) {
            \Log::error('Gemini Imagen Pool Exception: ' . $e->getMessage());
        }

        // ─────────────────────────────────────────────────────────────────
        // STEP 3 — Fallback: Pollinations.ai
        // FIX 2 — Correct domain (image.pollinations.ai) + clean URL (no float)
        // ─────────────────────────────────────────────────────────────────
        if (empty($suggestions)) {
            \Log::info('AI Generation: Gemini Imagen unavailable, trying Pollinations.ai...');

            try {
                $pollinationsResponses = Http::pool(fn(\Illuminate\Http\Client\Pool $pool) =>
                    collect($prompts)->map(function ($p) use ($pool) {
                        $encodedPrompt = urlencode($p);
                        $seed          = rand(1, 9999999);

                        // FIX 2a — Use correct domain: image.pollinations.ai
                        // FIX 2b — Use intval(microtime(true)) to avoid float dot in URL
                        $ts  = intval(microtime(true));
                        $url = "https://image.pollinations.ai/prompt/{$encodedPrompt}"
                             . "?width=1024&height=768&nologo=true&seed={$seed}&nofeed=true&t={$ts}";

                        \Log::debug("Pollinations URL: {$url}");
                        return $pool->timeout(30)->get($url);
                    })
                );

                foreach ($prompts as $index => $p) {
                    $res         = $pollinationsResponses[$index];
                    $contentType = $res->header('Content-Type') ?? '';
                    $body        = $res->body();

                    if ($res->successful() && str_starts_with($contentType, 'image/') && strlen($body) > 1000) {
                        $suggestions[] = [
                            'prompt' => $p,
                            'url'    => 'data:' . $contentType . ';base64,' . base64_encode($body),
                        ];
                        \Log::info("Pollinations: success for prompt index {$index}");
                    } else {
                        \Log::warning(
                            "Pollinations: failed for prompt index {$index}. "
                            . "Content-Type: {$contentType}, Body length: " . strlen($body)
                        );
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Pollinations Pool Exception: ' . $e->getMessage());
            }
        }

        // ─────────────────────────────────────────────────────────────────
        // STEP 4 — Last resort: Unsplash dynamic search
        // FIX 3 — Use event title + category as search query (not hardcoded IDs)
        // ─────────────────────────────────────────────────────────────────
        if (empty($suggestions)) {
            \Log::warning('AI Generation: Pollinations failed, trying dynamic Unsplash search...');

            // Build search terms from actual event data
            $searchTerms = array_filter([$titre, $description, $categorie]);
            $baseQuery   = implode(' ', array_slice($searchTerms, 0, 3));

            // 4 slightly varied queries to get different images for the same event
            $queries = [
                urlencode($baseQuery . ' event'),
                urlencode($baseQuery . ' background'),
                urlencode($titre . ' ' . $categorie),
                urlencode($categorie . ' event professional'),
            ];

            try {
                $unsplashResponses = Http::pool(fn(\Illuminate\Http\Client\Pool $pool) =>
                    collect($queries)->map(function ($q) use ($pool) {
                        $sig = rand(1, 9999999);
                        // Dynamic search URL — images vary based on event title/category
                        $url = "https://source.unsplash.com/1024x768/?{$q}&sig={$sig}";
                        \Log::debug("Unsplash URL: {$url}");
                        return $pool->timeout(15)->get($url);
                    })
                );

                foreach ($queries as $index => $q) {
                    $res         = $unsplashResponses[$index];
                    $contentType = $res->header('Content-Type') ?? '';
                    $body        = $res->body();

                    if ($res->successful() && str_starts_with($contentType, 'image/') && strlen($body) > 1000) {
                        $suggestions[] = [
                            'prompt' => "Event cover for: {$titre} ({$categorie})",
                            'url'    => 'data:' . $contentType . ';base64,' . base64_encode($body),
                        ];
                        \Log::info("Unsplash: success for query index {$index}");
                    } else {
                        \Log::warning("Unsplash: failed for query index {$index}. Content-Type: {$contentType}");
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Unsplash Pool Exception: ' . $e->getMessage());
            }
        }

        // ─────────────────────────────────────────────────────────────────
        // Final response
        // ─────────────────────────────────────────────────────────────────
        if (empty($suggestions)) {
            \Log::error('AI Generation: ALL fallbacks exhausted. No images generated.');
        } else {
            \Log::info('AI Generation: Returning ' . count($suggestions) . ' image(s).');
        }

        return response()->json(['suggestions' => $suggestions]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Upload a Base64 image to Cloudinary and return the secure URL
    // ─────────────────────────────────────────────────────────────────────
    public function uploadSelectedImage(Request $request)
    {
        $request->validate([
            'image_base64' => 'required|string',
            'prompt'       => 'nullable|string',
        ]);

        try {
            $base64 = $request->input('image_base64');

            $uploadResult = cloudinary()->uploadApi()->upload($base64, [
                'folder'        => 'events/ai_covers',
                'resource_type' => 'image',
            ]);

            return response()->json([
                'success'    => true,
                'secure_url' => $uploadResult['secure_url'],
                'prompt'     => $request->input('prompt'),
            ]);
        } catch (\Exception $e) {
            \Log::error('Cloudinary AI Upload Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to upload selection to Cloudinary'], 500);
        }
    }
}