<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateImagesController extends Controller
{
    /**
     * Generate High-Quality Event Suggestions
     * Uses LoremFlickr for 100% reliability and professional imagery.
     */
    public function generate(Request $request)
    {
        $categorie = $request->input('categorie', 'sportifs');
        $round     = $request->input('generation_round', 1);

        $suggestions = [];
        
        // Define base themes for each category
        $themes = [
            'sportifs' => [
                ['prompt' => 'Professional football match action', 'keywords' => 'football,stadium,match'],
                ['prompt' => 'Intense soccer game moment', 'keywords' => 'soccer,player,action'],
                ['prompt' => 'Wide stadium crowd view', 'keywords' => 'stadium,fans,crowd'],
                ['prompt' => 'Sports tournament trophy scene', 'keywords' => 'trophy,sports,winners']
            ],
            'musicaux' => [
                ['prompt' => 'Large scale music festival stage', 'keywords' => 'festival,concert,stage'],
                ['prompt' => 'DJ performing for massive crowd', 'keywords' => 'dj,party,lights'],
                ['prompt' => 'Live concert night atmosphere', 'keywords' => 'concert,singer,crowd'],
                ['prompt' => 'Electronic music laser show', 'keywords' => 'lasers,music,festival']
            ],
            'culturels' => [
                ['prompt' => 'Art exhibition gallery view', 'keywords' => 'art,gallery,museum'],
                ['prompt' => 'Traditional cultural performance', 'keywords' => 'culture,theater,dance']
            ],
            'scientifiques' => [
                ['prompt' => 'Modern technology conference', 'keywords' => 'tech,conference,innovation'],
                ['prompt' => 'Scientific laboratory research', 'keywords' => 'science,lab,research']
            ],
            'commerciaux' => [
                ['prompt' => 'Busy business trade fair', 'keywords' => 'business,fair,exhibition'],
                ['prompt' => 'Store grand opening event', 'keywords' => 'shopping,opening,event']
            ]
        ];

        $items = $themes[$categorie] ?? [['prompt' => 'Grand event scene', 'keywords' => 'event,crowd,stage']];

        foreach ($items as $index => $item) {
            $seed = rand(1000, 9999) + ($round * 100) + $index;
            
            // 🎯 LOREMFLICKR: Best-in-class reliability for both frontend and backend
            $url = "https://loremflickr.com/1024/768/" . urlencode($item['keywords']) . "?lock={$seed}";

            $suggestions[] = [
                'prompt'       => $item['prompt'],
                'keywords'     => $item['keywords'],
                'url'          => $url,
                'fallback_url' => $url, // Already using the most stable source
            ];
        }

        return response()->json([
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Upload Selected Image to Cloudinary (Robust)
     */
    public function uploadSelectedImage(Request $request)
    {
        $request->validate([
            'image_url' => 'required|string',
        ]);

        try {
            // 🎯 DOWNLOAD TO TEMP (Ensures Cloudinary gets a clean file)
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0'
            ])->timeout(30)->get($request->input('image_url'));

            if (!$response->successful()) {
                return response()->json(['error' => 'Image fetch failed'], 400);
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'ev_img_');
            $imagePath = $tempPath . '.jpg';
            file_put_contents($imagePath, $response->body());

            // Upload to Cloudinary
            $uploadResult = cloudinary()->uploadApi()->upload(
                $imagePath,
                [
                    'folder' => 'events/ai_covers',
                    'resource_type' => 'image'
                ]
            );

            // Cleanup
            @unlink($imagePath);
            @unlink($tempPath);

            return response()->json([
                'success'    => true,
                'secure_url' => $uploadResult['secure_url'],
            ]);

        } catch (\Exception $e) {
            Log::error('Cloudinary upload error: ' . $e->getMessage());

            if (isset($imagePath) && file_exists($imagePath)) @unlink($imagePath);
            if (isset($tempPath) && file_exists($tempPath)) @unlink($tempPath);

            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }
}