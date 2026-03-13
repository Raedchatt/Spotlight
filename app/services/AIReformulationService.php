<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIReformulationService
{
    private string $apiKey;
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    public function reformuler(string $message): string
    {
        if (empty(trim($message))) {
            return $message;
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '?key=' . $this->apiKey, [
                'system_instruction' => [
                    'parts' => [
                        ['text' => $this->buildSystemPrompt()]
                    ]
                ],
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $message]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature'     => 0.7,
                    'maxOutputTokens' => 1024,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Gemini API success', [
                    'input'  => $message,
                    'output' => $data['candidates'][0]['content']['parts'][0]['text'] ?? 'NULL',
                ]);

                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                return $this->cleanOutput($text, $message);
            }

            Log::error('Gemini API error', [
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return $message;

        } catch (\Exception $e) {
            Log::error('AIReformulationService exception', [
                'error' => $e->getMessage(),
            ]);

            return $message;
        }
    }

    private function buildSystemPrompt(): string
    {
        return implode("\n", [
            'Tu es un assistant de communication professionnelle dans une plateforme d\'événements.',
            '',
            'Règles STRICTES :',
            '- Détecte automatiquement la langue du message (français, arabe, anglais)',
            '- Reformule le message dans LA MÊME langue détectée',
            '- Rends le message professionnel, clair et compréhensible',
            '- Garde le sens original du message',
            '- Réponds UNIQUEMENT avec le message reformulé, rien d\'autre',
            '- Ne rajoute PAS d\'explication, de préambule ou de commentaire',
            '- Ne mets PAS de guillemets autour du résultat',
            '- Ne commence PAS par une étiquette comme "Message reformulé :" ou similaire',
            '- Si le message est déjà professionnel, améliore-le légèrement',
            '- Garde un ton respectueux et courtois',
        ]);
    }

    private function cleanOutput(string $text, string $fallback): string
    {
        if (empty(trim($text))) {
            return $fallback;
        }

        $prefixes = [
            'Message reformulé\s*:\s*',
            'Reformulated message\s*:\s*',
            'رسالة معاد صياغتها\s*:\s*',
            'Voici le message reformulé\s*:\s*',
            'Here is the reformulated message\s*:\s*',
        ];

        foreach ($prefixes as $prefix) {
            $text = preg_replace('/^' . $prefix . '/ui', '', $text);
        }

        $text = trim($text, '"\'«»„"');

        return trim($text) ?: $fallback;
    }
}