<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushNotificationService
{
    /**
     * Send push notification for a given alert to the target users.
     *
     * @param  array<int, int>  $userIds
     */
    public function sendAlertNotification(Alert $alert, array $userIds): void
    {
        try {
            $tokens = DeviceToken::query()
                ->whereIn('user_id', $userIds)
                ->pluck('token')
                ->unique()
                ->values()
                ->all();

            if (empty($tokens)) {
                return;
            }

            $title = 'A-RAICE Alert: '.ucfirst($alert->severity->value).' Severity';
            $body = $alert->message;
            $data = [
                'alert_id' => (string) $alert->id,
                'outbreak_id' => (string) ($alert->outbreak_id ?? ''),
                'severity' => $alert->severity->value,
                'type' => 'alert',
            ];

            $fcmKey = config('services.fcm.server_key') ?? env('FCM_SERVER_KEY');

            if (! empty($fcmKey)) {
                foreach (array_chunk($tokens, 500) as $chunk) {
                    Http::withToken($fcmKey)
                        ->post('https://fcm.googleapis.com/fcm/send', [
                            'registration_ids' => $chunk,
                            'notification' => [
                                'title' => $title,
                                'body' => $body,
                                'sound' => 'default',
                            ],
                            'data' => $data,
                        ]);
                }
            } else {
                Log::info('Push notification simulated (no FCM key configured)', [
                    'title' => $title,
                    'tokens_count' => count($tokens),
                    'sample_token' => $tokens[0] ?? null,
                ]);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
