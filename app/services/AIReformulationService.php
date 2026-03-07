<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; 

class AIReformulationService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private string $model  = 'claude-sonnet-4-20250514';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key');
    }

    public function reformuler(string $message): string
    {
        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->post($this->apiUrl, [
                'model'      => $this->model,
                'max_tokens' => 1024,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => $this->buildPrompt($message),
                    ]
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['content'][0]['text'] ?? $message;
            }

            // Si erreur API → retourner message original
            Log::error('Claude API error', ['response' => $response->json()]);
            return $message;

        } catch (\Exception $e) {
            Log::error('AIReformulationService error', ['error' => $e->getMessage()]);
            return $message;
        }
    }

    private function buildPrompt(string $message): string
    {
        return <<<PROMPT
        Tu es un assistant de communication professionnelle dans une plateforme d'événements.

        Règles STRICTES :
        - Détecte automatiquement la langue du message (français, arabe, anglais)
        - Reformule le message dans LA MÊME langue détectée
        - Rends le message professionnel, clair et compréhensible
        - Garde le sens original du message
        - Ne rajoute PAS d'explication, juste le message reformulé
        - Ne mets PAS de guillemets autour du résultat
        - Si le message est déjà professionnel, améliore-le légèrement
        - Garde un ton respectueux et courtois

        Message original :
        {$message}

        Message reformulé :
        PROMPT;
    }
}