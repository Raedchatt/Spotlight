<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushService
{
    const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * Send push notification to all active device tokens.
     */
    public function sendToAll(string $title, string $body, array $data = [])
    {
        $tokens = DeviceToken::active()->pluck('expo_push_token')->toArray();
        if (empty($tokens)) {
            return;
        }

        $this->sendPushNotifications($tokens, $title, $body, $data);
    }

    /**
     * Send push notification to specific users.
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = [])
    {
        $tokens = DeviceToken::active()->whereIn('user_id', $userIds)->pluck('expo_push_token')->toArray();
        if (empty($tokens)) {
            return;
        }

        $this->sendPushNotifications($tokens, $title, $body, $data);
    }

    /**
     * Send the actual HTTP request to Expo's Push API in chunks.
     */
    private function sendPushNotifications(array $tokens, string $title, string $body, array $data)
    {
        // Expo recommends chunks of up to 100 max
        $chunks = array_chunk($tokens, 100);

        foreach ($chunks as $chunk) {
            $messages = array_map(function ($token) use ($title, $body, $data) {
                return [
                    'to' => $token,
                    'title' => $title,
                    'body' => $body,
                    'data' => empty($data) ? null : $data,
                ];
            }, $chunk);

            try {
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Accept-encoding' => 'gzip, deflate',
                    'Content-Type' => 'application/json',
                ])->post(self::EXPO_PUSH_URL, $messages);

                if ($response->failed()) {
                    Log::error('Expo Push Notification Failed', [
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Expo Push Notification Exception', [
                    'message' => $e->getMessage()
                ]);
            }
        }
    }
}
