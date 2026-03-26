<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GenerateImagesController extends Controller
{
    public function generate(Request $request)
    {
        $titre       = $request->input('titre', '');
        $description = $request->input('description', '');
        $categorie   = $request->input('categorie', '');

        // Default prompts (fallback)
        $prompts = [
            "Cinematic event cover for '{$titre}', {$categorie}, dramatic lighting, ultra HD",
            "Modern vibrant poster for '{$titre}', {$categorie}, colorful, high contrast",
            "Minimal elegant banner for '{$titre}', soft gradients, premium style",
            "Futuristic neon event visual for '{$titre}', dynamic composition",
        ];

        // ─────────────────────────────────────────────
        // STEP 1 — Generate prompts using Gemini
        // ─────────────────────────────────────────────
        try {
            $apiKey = config('services.gemini.key');
            $model  = 'gemini-2.0-flash';

            if ($apiKey) {
                $response = Http::timeout(20)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    [
                        'contents' => [[
                            'parts' => [[
                                'text' => "Generate 4 creative image sets for this event:
Title: {$titre}
Description: {$description}
Category: {$categorie}

For each set, provide:
1. A creative one-sentence visual prompt (for display).
2. A list of 3-5 specific keywords for image search (e.g. 'jazz, stadium, concert, light').

Return ONLY a JSON array of objects with 'prompt' and 'keywords' fields."
                            ]]
                        ]]
                    ]
                );

                $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');

                // Extract JSON safely
                if (preg_match('/\[(.*?)\]/s', $text, $matches)) {
                    $parsed = json_decode($matches[0], true);

                    if (is_array($parsed) && count($parsed) >= 2) {
                        $prompts = array_slice($parsed, 0, 4);
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Gemini Error: ' . $e->getMessage());
        }

        // ─────────────────────────────────────────────
        // STEP 2 — Generate images (Pollinations)
        // ─────────────────────────────────────────────
        $suggestions = [];

        foreach ($prompts as $index => $item) {
            $prompt = is_array($item) ? ($item['prompt'] ?? '') : $item;
            $keywordsList = is_array($item) ? ($item['keywords'] ?? []) : [$categorie];
            
            // Map each keyword for url encoding and then join with literal commas
            $keywordsArr = is_array($keywordsList) ? $keywordsList : explode(',', $keywordsList);
            $cleanKeywords = implode(',', array_map('urlencode', array_filter($keywordsArr)));
            
            if (empty($cleanKeywords)) $cleanKeywords = 'event,party';

            // Pollinations.ai requires an API key. Using LoremFlickr as the free reliable alternative.
            // We use 'lock=true' or just a unique seed to prevent repetition across the 4 suggestions.
            $url = "https://loremflickr.com/1024/768/{$cleanKeywords}?random=" . rand(1, 9999) . $index;

            $suggestions[] = [
                'prompt' => $prompt,
                'url'    => $url
            ];
        }

        return response()->json([
            'suggestions' => $suggestions
        ]);
    }

    // ─────────────────────────────────────────────
    // Upload to Cloudinary
    // ─────────────────────────────────────────────
    public function uploadSelectedImage(Request $request)
    {
        $request->validate([
            'image_url' => 'required|string',
            'prompt'    => 'nullable|string',
        ]);

        try {
            $uploadResult = cloudinary()->uploadApi()->upload(
                $request->input('image_url'),
                [
                    'folder' => 'events/ai_covers'
                ]
            );

            return response()->json([
                'success'    => true,
                'secure_url' => $uploadResult['secure_url'],
                'prompt'     => $request->input('prompt'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Cloudinary Error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Upload failed'
            ], 500);
        }
    }
}