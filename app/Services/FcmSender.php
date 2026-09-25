<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Throwable;

/*
 * DDE-Mart Admin — FCM sender (original service).
 * Replaces legacy raw-curl broadcasters (disabled TLS, die() on error).
 * Uses kreait/laravel-firebase (verified TLS). Without FIREBASE_CREDENTIALS it
 * gracefully reports unconfigured instead of sending — callers log the record anyway.
 */
class FcmSender
{
    public function isConfigured(): bool
    {
        $credentials = config('firebase.projects.app.credentials');

        return is_string($credentials) && $credentials !== '' && is_file($credentials);
    }

    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        if (! $this->isConfigured()) {
            Log::info('FCM skipped: no credentials configured', compact('topic', 'title'));

            return false;
        }

        try {
            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(FcmNotification::create($title, $body))
                ->withData(array_merge([
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'status' => 'done',
                ], $data));

            app('firebase.messaging')->send($message);

            return true;
        } catch (Throwable $e) {
            Log::warning('FCM send failed', ['topic' => $topic, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
